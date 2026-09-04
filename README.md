# Boneek CRM

A CRM built as a **Laravel 13 + Inertia + React 19** app that talks to its own REST API.
One Laravel application serves both the Inertia pages and the versioned `/v1/*` JSON
endpoints; a mobile client uses the same API with Sanctum bearer tokens.

Its call center module also reads and writes a **second database** — the inventory
(boneek) app's own Postgres. Read [The `boneek` connection](#the-boneek-connection)
before touching anything under `app/Services/CallCenter` or `app/Models/Boneek`.

## Getting started

Docker is the supported path.

```bash
cp .env.docker.example .env     # not .env.example — that one targets Laragon/Windows
# then edit: UID/GID from `id -u` / `id -g` (macOS is usually 501/20, the file ships 1000)
#            BONEEK_DB_USERNAME, BONEEK_DB_PASSWORD, BONEEK_APP_URL — all ship empty

docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

The order matters. Copy the `.env` **first**: the `pgsql` service interpolates
`${DB_DATABASE}` and `${DB_USERNAME}` with no fallback, so without it Postgres initialises
empty. And `composer install` can only run **after** `up`, because nothing — PHP, Composer,
Node, npm — is installed on the host; the container is the toolchain. The `queue` and
`scheduler` containers restart-loop until that install finishes, which is expected.

`COMPOSE_FILE=docker-compose.local.yml` in `.env` is what lets you type
`docker compose up -d` rather than naming the file every time. Seven services come up:
`app`, `nginx`, `queue`, `scheduler`, `vite`, `pgsql`, `mailpit`. There is no Redis —
cache, sessions and the queue all run on the database.

Seeding gives you a super-admin: **`admin@gmail.com` / `password`**. `DemoDataSeeder` is
*not* wired into `DatabaseSeeder`; run it by name when you want demo rows.

**Do not run `npm install` on the host.** The `vite` container installs its own
`node_modules` on first boot, and it has to: the bundler ships per-platform native
binaries (`@rolldown/binding-linux-arm64-*` here), so a host install swaps them for the
macOS build and the container stops booting.

**Never run `docker compose down -v`** — it deletes the local database.

| | |
|---|---|
| App | http://localhost:8030 |
| Vite (HMR) | http://localhost:8031 |
| Postgres 17 | `localhost:8032` |
| Mailpit (SMTP) | `localhost:8033` |
| Mailpit (dashboard) | http://localhost:8034 |

Every service and the reasoning behind it is documented in `docker-compose.local.yml`.

There is a second, Laragon/Windows path built on `.env.example` (which targets
`https://boneek-crm.test` and sqlite) and `composer run dev`. It is not the supported
route and nothing above applies to it.

## Commands

Everything runs through the container.

```bash
docker compose exec app php artisan test                      # the whole suite
docker compose exec app php artisan test --filter=CallCenter  # one area
docker compose exec app php artisan test --filter=test_it_searches_by_name
docker compose exec app ./vendor/bin/pint                     # formatter, Laravel preset
docker compose exec app npx tsc --noEmit                      # the TypeScript check
```

`pint` and `tsc --noEmit` both have to be clean before frontend or backend work is done.
Note that `tsconfig.json` sets `strict: false`, so a clean `tsc` is a weaker guarantee than
it looks — it catches shape errors, not missing null checks.

The suite runs on **two sqlite `:memory:` connections** (`phpunit.xml`) — one standing in
for the CRM's Postgres and one for the boneek database, whose schema is hand-maintained in
`tests/database/boneek/`. That copy can drift from the real thing;
`BoneekSchemaContractTest` is the tripwire, and it skips when no real boneek connection is
reachable.

## Architecture

**Pages are render-only.** The `web` routes never fetch data. They render a React page
through a thin page controller that passes an id and nothing else — see
`CallCenterPageController` (`'orderId' => $orderId`). The page then loads its own data from
`/v1/*` through `resources/js/lib/api-client.ts`, whose `baseURL` is `/v1`, so callers
write `api.get('/projects')`.

The one thing that may travel beside the id is a cheap authorisation flag
(`ProjectPageController::reports` passes `canSubmitReports`). Ids and auth flags — never
domain data.

**Flow:** Route → Controller (request/response only) → FormRequest (validation) → Service
(business logic) → Model → Resource (formatting). Multi-step domain operations live in
`app/Actions/`, triggered from `app/Observers/`.

Routes are mounted from the `using:` closure in `bootstrap/app.php`:

| File | Prefix | Middleware |
|---|---|---|
| `routes/admin-v1.php` | `/v1` (names `v1.*`) | `api` |
| `routes/client-v1.php` | `/v1` (names `v1.*`) | `api` |
| `routes/web.php` | — | `web` |
| `routes/web-admin.php` | `/admin` | `web` |

There is no unversioned `/api/*`. Controllers live under `app/Http/Controllers/V1/`, split
into `Admin/` for the staff API and `Client/` for the portal's own endpoints.

Frontend is TypeScript throughout — type new props, state and payloads rather than
reaching for `any`, and import shared inputs from the `@/components/ui` barrel.

- [Backend structure](docs/code-structure/backend-structure.md) — layering, flow, rules
- [Frontend structure](docs/code-structure/frontend-structure.md) — layering, flow, rules

Those two describe the **target** structure, and parts of the repo have not been migrated
yet. `CLAUDE.md` has a table of target vs. current; check which state an area is in before
writing a file in it.

## Database

Eloquent only. Query Builder is allowed for performance; **no raw SQL**. FK constraints are
enforced, and multi-write Service methods are wrapped in transactions.

Schema changes are made **in the original `create_*_table` migration**, not in a new ALTER
migration — the exception being a table already released to production, which only a
migration can reach.

### The `boneek` connection

The call center module reads and writes the inventory app's own Postgres through a second
connection named `boneek`. Two applications, one database.

- **Never migrate it.** `php artisan migrate --database=boneek` is always wrong; the
  inventory project owns that schema.
- **Transactions must name the connection**: `DB::connection('boneek')->transaction(...)`.
  A bare `DB::transaction()` opens one on the *CRM* database, so the boneek writes inside
  it silently never roll back.
- **Inventory's observers, events and queued jobs do not fire for a CRM write.** The two
  apps are separate processes on one database, so anything inventory does in an observer
  has to be done explicitly here or it simply does not happen. Check the observer before
  porting any write.
- The two databases cannot be joined, so cross-database matching is done by pulling id
  lists into PHP.
- Product pictures live on inventory's disk. A url only exists once **`BONEEK_APP_URL`** is
  set — leave it empty and every product silently falls back to the placeholder icon,
  which is the usual reason a new environment shows no pictures.

The CRM writes a deliberately short list of tables there — orders and their items,
shipping addresses, customers, stock, logs, and the supplier notification. **The
exhaustive list, and the reasoning behind every inclusion and exclusion, is the
allowed-writes table in [`CLAUDE.md`](CLAUDE.md).** Do not add a write without adding it
there.

### Before you deploy

That table is also the specification for the connection's database GRANTs.
[`DEPLOY.md`](DEPLOY.md) turns it into the `GRANT` statements the restricted `boneek` user
needs, and the two must change in the same release.

> A write with no matching grant raises Postgres `42501` **inside** the transaction, so
> the whole edit rolls back. Nothing catches it, so it ends as a plain 500 and the user
> gets a generic "Server Error" — the failure is loud but says nothing about permissions,
> and only the application log names what was refused. It cannot be caught before
> production either: locally the connection uses the schema owner, and the test suite runs
> on sqlite, which has no grants at all. **Green locally, green in CI, broken only in
> production.**

## The call center

Agents pick a merchant's pending orders out of a queue, phone the customer, correct the
order, and record an outcome — approved, cancelled or follow-up. On a dropshipping order
they can also forward it to the supplier, which opens a real order in the supplier's own
business and takes the goods off their shelf.

Much of it is a **port** of logic that also lives in the inventory app — stock rules, order
money math, the supplier-order creation. If those change upstream they must change here in
the same release, or the two systems' stock counts and totals diverge. `CLAUDE.md` records
every place the CRM deliberately differs from inventory's own order screen; each is a
decision, not drift.

- [Plan summary](docs/call-center-plan-summary.md) — what was built and why
- [Manual test guide](docs/call-center-manual-test-guide.md) — click-by-click, against the
  seeded demo data

Both of those, and the other `docs/call-center-*.md` planning documents, are written in
Bengali.

## Conventions

- No comments in new code. Existing comments explain *why* and are load-bearing — do not
  strip them.
- Commit and push only when asked.
