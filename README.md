# llb-pdf

A CRM built as a **Laravel 13 + Inertia + React 19** app that talks to its own REST API.
One Laravel application serves both the Inertia pages and the versioned `/v1/*` JSON
endpoints; a mobile client uses the same API with Sanctum bearer tokens.

## Getting started

Docker is the supported path.

```bash
cp .env.docker.example .env     # not .env.example — that one targets Laragon/Windows
# then edit: UID/GID from `id -u` / `id -g` (macOS is usually 501/20, the file ships 1000)

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

Seeding gives you a super-admin: **`admin@gmail.com` / `password`**.

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
`https://llb-pdf.test` and sqlite) and `composer run dev`. It is not the supported
route and nothing above applies to it.

## Commands

Everything runs through the container.

```bash
docker compose exec app php artisan test                      # the whole suite
docker compose exec app php artisan test --filter=User        # one area
docker compose exec app php artisan test --filter=test_it_searches_by_name
docker compose exec app ./vendor/bin/pint                     # formatter, Laravel preset
docker compose exec app npx tsc --noEmit                      # the TypeScript check
```

`pint` and `tsc --noEmit` both have to be clean before frontend or backend work is done.
Note that `tsconfig.json` sets `strict: false`, so a clean `tsc` is a weaker guarantee than
it looks — it catches shape errors, not missing null checks.

The suite runs on a sqlite `:memory:` connection (`phpunit.xml`) standing in for the CRM's
Postgres.

## Architecture

**Pages are render-only.** The `web` routes never fetch data. They render a React page
through a thin page controller that passes an id and nothing else — see
`UserPageController` (`['userId' => $user]`). The page then loads its own data from
`/v1/*` through `resources/js/lib/api-client.ts`, whose `baseURL` is `/v1`, so callers
write `api.get('/users')`.

Ids and request-derived values only — never domain data.

**Flow:** Route → Controller (request/response only) → FormRequest (validation) → Service
(business logic) → Model → Resource (formatting).

Routes are mounted from the `using:` closure in `bootstrap/app.php`:

| File | Prefix | Middleware |
|---|---|---|
| `routes/admin-v1.php` | `/v1` (names `v1.*`) | `api` |
| `routes/web.php` | — | `web` |
| `routes/web-admin.php` | `/admin` | `web` |

There is no unversioned `/api/*`. Controllers live under `app/Http/Controllers/V1/Admin/`.

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

## Conventions

- No comments in new code. Existing comments explain *why* and are load-bearing — do not
  strip them.
- Commit and push only when asked.
