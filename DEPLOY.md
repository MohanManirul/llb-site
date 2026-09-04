# Deploying the CRM

Coolify builds this repo's `Dockerfile` directly (**Dockerfile build pack**).
There is no compose file in the deploy path any more.

That is the whole reason for the change. Coolify treats the two build packs
completely differently:

- **Dockerfile build pack: rolling.** It starts the new container, waits for its
  healthcheck to pass, and only then removes the old one. Traefik keeps serving
  from the old container the whole time. No downtime.
- **Docker Compose build pack: stop, then start.** It stops the container first
  and starts the new one after. Measured on `production-crm` deployment 104: 33
  seconds with nothing serving, on every deploy.

Production deploys as one container from the `Dockerfile`, so nothing needed a
compose file there. Deleting it and pointing Coolify at the `Dockerfile` is the
entire fix. (Staging still deploys `docker-compose.staging.yml`, which builds
the same `Dockerfile` into three services.)

Beside it run **a queue worker and a scheduler**, from the same image:

```
php artisan queue:work --tries=1 --timeout=900 --sleep=1
php artisan schedule:work
```

Both are load-bearing, and `docker-compose.local.yml` and
`docker-compose.staging.yml` run exactly the same two as their `queue` and
`scheduler` services.

- **The queue** is on `QUEUE_CONNECTION=database` in every environment — never
  `sync`. Cache and sessions are on the database too; there is no Redis. What
  rides on it today: the password-reset mail for both guards
  (`AdminResetPassword` and `ClientResetPassword` both `implements ShouldQueue`)
  and the client CSV import (`ImportClientsChunkJob`). With `database` and no
  worker those would be written to the `jobs` table and never run — "forgot
  password" would appear to succeed and send nothing — so the worker is not
  optional.
- **The scheduler** runs `call-center:reconcile` every ten minutes
  (`routes/console.php`). That is what releases a pick whose order the merchant
  changed underneath the agent; without it those picks sit in agents' lists
  indefinitely, eating their pick limit. There are two ways to run it — see
  [The scheduler](#the-scheduler) below, and pick one, not both.

One trap when adding either as a Coolify application: give it the
`pgrep -f queue:work` healthcheck that `docker-compose.staging.yml` uses. The
image's own `HEALTHCHECK` curls `/up`, and there is no web server in those
containers to answer it, so they would sit unhealthy and every deploy would be
called a failure.

## Deploying a release

**Production deploys are manual and stay manual.** Auto-deploy is off on
`production-crm`, so nothing reaches production on a push; the deploy is queued
by hand in Coolify. Staging (`staging-crm`) deploys from `main`.

The old container serves every request until the new one passes its healthcheck,
so there is no window to plan around and no maintenance page.

## Migrations

`AUTORUN_LARAVEL_MIGRATION` is `true`, so migrations run when the container
starts. With a rolling deploy that now happens **while the old container is
still serving the site**. Every migration therefore has to work against the code
that is already running: add columns nullable or with a default, add the code
that writes to them in the same release, and drop or rename anything only in a
later one. Set the variable to `false` and run `php artisan migrate --force` by
hand if a release cannot be written that way.

## Permissions

Migrations bring the schema; a permission arrives as a **row**, and one command
creates it:

```sh
php artisan db:seed --class=PermissionSeeder --force
```

That is the whole of what it does — every name in `config/admin-permissions.php`
gets its row, and nothing else. It grants nothing and revokes nothing, which is
why it is **the one seeder safe to run against a live database**. Run it in any
release that adds a permission to that config, or the feature behind it is a
check nobody in the system can pass.

**`UserSeeder` must never be run in production.** It walks the same config with
`syncPermissions()`, which would replace whatever an admin had granted a role by
hand, and it creates staff accounts besides.

**Creating the row and granting it are two different things.** Straight after
seeding nobody holds the new permission; grant it from Admin → Roles to whoever
should. The exception is anything only `super-admin` is meant to hold — they
pass every check through `Gate::before` and need no grant — which is what makes
an ungranted permission the safe default rather than a broken deploy.

The seeder is additive. A permission dropped from the config keeps its row, so
removing one is a migration's job, as `drop_product_details_table` shows; only a
migration reaches a deployed database.

## The `boneek` connection (call center)

The call center module reads and writes the **inventory app's** database over a
second connection. It needs these on the application, alongside the `DB_*` keys:

| Variable | Notes |
| --- | --- |
| `BONEEK_DB_HOST` / `BONEEK_DB_PORT` | The inventory Postgres. |
| `BONEEK_DB_DATABASE` | The inventory database name. |
| `BONEEK_DB_USERNAME` / `BONEEK_DB_PASSWORD` | **Not** inventory's own app user — see below. |
| `BONEEK_DB_SSLMODE` | `require` in production. |
| `BONEEK_APP_URL` | Inventory's own base url. **Leave it empty and every picture stored as a relative path falls back to the placeholder icon** — there is no error, the images just never appear. (A product whose `products.image` already holds an absolute url still shows, which is what makes it look intermittent.) It is the usual reason a fresh environment looks broken. |
| `BONEEK_STORAGE_PATH` | Path segment between that url and `product_images.path`, `storage` by default. |

`CALL_CENTER_PICK_LIMIT`, `CALL_CENTER_ALLOW_UNPICK`, `CALL_CENTER_LOG_ACTOR`
and `CALL_CENTER_SERVICE_CHARGE` all have working defaults and only need
setting to change them. The last one is the only variable in the module that
sets a money amount — the service charge stamped on `orders.call_center_charge`
when an agent approves an order, `20` by default, which inventory then carries
into that order's settlement — so check it against the agreed rate before a
release instead of inheriting the default. `0` stops the stamping and leaves the
rest of the module running.

**Never migrate this connection.** The inventory project owns that schema;
`migrate --database=boneek` is always a mistake.

### Give it its own restricted database user

The call center edits a merchant's live orders, so the connection is granted
exactly what the module uses and nothing else. Anything the CRM has no business
touching — `payments`, `transactions`, every balance column of `accounts` — is
then out of reach of a bug, not merely out of reach of the code that exists
today.

These grants are the deployment-side mirror of the allowed-writes table in
`CLAUDE.md`. **If a change adds a table or a column there, it has to be added
here in the same release**, or the feature will fail in production with a
Postgres `permission denied` inside a transaction that then rolls back. Nothing
catches `42501` — `bootstrap/app.php` maps only `23503` — so the request ends as
a plain 500 and the user gets a generic "Server Error". The edit really is
undone, but the error says nothing about permissions: only the application log
names the table and column that were refused. Read the log before guessing.

```sql
CREATE USER crm_call_center WITH PASSWORD '...';

GRANT CONNECT ON DATABASE inventory TO crm_call_center;
GRANT USAGE    ON SCHEMA   public   TO crm_call_center;

-- Read -----------------------------------------------------------------------
GRANT SELECT ON
    businesses, business_settings, branches,
    orders, order_items, order_shipping_addresses, order_attribution,
    order_incompletes,
    customers, customer_tags,
    products, product_images, product_stocks, skus, units,
    variations, variation_options, variation_option_sku,
    delivery_partners,
    countries, divisions, districts, areas
TO crm_call_center;

-- The customer's ledger account, keyed only. Renaming a customer renames their
-- account, and Postgres needs SELECT on the columns an UPDATE's WHERE reads --
-- but nothing here may read a balance.
GRANT SELECT (id) ON accounts TO crm_call_center;

-- Write ----------------------------------------------------------------------
GRANT UPDATE (
    status, previous_status, type,
    sub_total, discount_type, discount_value, shipping_charge,
    total_vat_amount, total_amount, due_amount, payment_status,
    delivery_partner_id, additional_note,
    call_center_charge,
    branch_id, sold_at, source, identifier_number,
    updated_at
) ON orders TO crm_call_center;

-- Send to Supplier opens the supplier's own order, as inventory's
-- ImportDropshippingOrderAction::createSupplierOrder does. Column-scoped, so the
-- CRM can build that row but cannot set anything outside its remit -- customer_id
-- is absent here exactly as it is from the UPDATE list above.
GRANT INSERT (
    id, business_id, parent_id, type, branch_id,
    invoice_no, identifier_number, status, sold_at, source,
    additional_note, shipping_charge,
    sub_total, total_vat_amount, total_amount, due_amount, payment_status,
    created_at, updated_at
) ON orders TO crm_call_center;

-- Every column is the CRM's to set, so these are whole-table.
GRANT INSERT, UPDATE, DELETE ON order_items TO crm_call_center;

-- updateOrCreate: an order that reached the CRM without an address row gets one.
GRANT INSERT ON order_shipping_addresses TO crm_call_center;
GRANT UPDATE (
    contact_name, contact_phone, address,
    country_id, division_id, district_id, area_id,
    updated_at
) ON order_shipping_addresses TO crm_call_center;

GRANT UPDATE (
    name, phone, alternative_phone, email, customer_tag_id, updated_at
) ON customers TO crm_call_center;

GRANT UPDATE (name, updated_at) ON accounts TO crm_call_center;

-- Insert-only, deliberately: see below.
GRANT INSERT ON order_logs TO crm_call_center;

-- The supplier's bell. A supplier order arriving with no row here is one the
-- supplier is never told about, so this pair travels with the orders INSERT
-- above: grant one without the other and Send to Supplier rolls the whole
-- thing back.
GRANT INSERT ON business_notifications TO crm_call_center;
GRANT INSERT (id, business_id, key, value, created_at, updated_at)
    ON business_settings TO crm_call_center;
GRANT UPDATE (value, updated_at) ON business_settings TO crm_call_center;

-- Insert as well as update: the deduction path opens a stock row where the
-- branch has none, which is what inventory's own lockOrMakeStock does.
GRANT INSERT ON product_stocks TO crm_call_center;
GRANT UPDATE (quantity, updated_at) ON product_stocks TO crm_call_center;
```

Six things about that script are easy to get wrong:

- **`orders` now has INSERT, and it is the only table the CRM creates a row in
  for another business.** Send to Supplier writes an order into the *supplier's*
  panel and takes stock off their shelf. The four columns added to the UPDATE
  list -- `branch_id`, `sold_at`, `source`, `identifier_number` -- are there for
  the rebuild path, which re-stamps an existing supplier order. Miss any of them
  and the failure is invisible outside production: the local `.env` connects as
  `inventory`, the schema owner, and the test suite runs on sqlite, which has no
  grants at all. Both are green while production silently rolls the whole edit
  back.

- **`order_logs` has INSERT and no SELECT, on purpose.** The CRM writes the
  merchant-facing log and never reads it back — merchant order logs carry
  merchant staff names and notes, and the CRM deliberately never surfaces them
  (`CallCenterHidesMerchantOrderLogsTest`). Withholding SELECT moves that
  decision from a convention into something the database enforces.
- **`orders`, `order_items` and `product_stocks` must keep UPDATE even where a
  path only reads.** Those three are locked with `SELECT … FOR UPDATE`, and
  Postgres requires UPDATE privilege *in addition to* SELECT for a row lock.
  Tightening any of them to SELECT-only breaks every item edit.
- **No sequence grants are needed.** Every boneek key is a uuid v7 generated in
  PHP (`BoneekModel::booted`), so there is no serial to `GRANT USAGE ON
  SEQUENCE`.
- **`accounts` is column-scoped both ways.** `SELECT (id)` and
  `UPDATE (name, updated_at)` — a customer rename has to rename their ledger
  account, which inventory does in an observer that never fires for a CRM write.
  A whole-table SELECT here would hand the CRM every merchant's balances.

- **`business_settings` is column-scoped and holds exactly one key.** The CRM
  writes `has_notification`, and nothing in the code path can name another key
  -- but the grant cannot express that, so the column list is the whole of the
  enforcement here. Reviewers should treat any second key written through this
  connection as a bug.

A useful side effect: the exclusions `CLAUDE.md` describes as deliberate stop
being conventions and become things the database refuses. With these grants the
CRM user cannot insert a `customers` row, cannot move an order to a different
customer (`orders.customer_id` is absent from the UPDATE list), cannot update or
delete an `order_logs` row, and cannot reach `payments` or `transactions` at
all.

What the CRM still cannot do for a supplier order is reach anything outside the
database. Inventory's `BusinessNotificationCreated` also broadcasts on Reverb
and forgets a `Cache::rememberForever` key on its own redis; the CRM runs its
own broadcaster and its own cache, so neither carries.

Both database writes land, and the effect of each was measured rather than
assumed:

- **The notification is served.** `GET /v1/notifications` in the supplier's own
  panel queries `business_notifications` directly, and returns the row the CRM
  wrote with its `notifiable` resolved to the supplier order.
- **The bell lags.** `GET /v1/business/settings` answers `has_notification`
  from `Cache::rememberForever`, so it keeps returning `false` even though the
  column now reads `true`. The dot lights when something on inventory's own
  side next writes the setting -- the next order through their own flow does,
  because `updateSetting` forgets the key.

Closing that last gap needs the CRM to invalidate a key in **inventory's**
redis, which means a second cache store and a network path to it. That is an
infrastructure decision, in the same family as the queue and broadcast splits
above, and is deliberately not taken here.

To check a deployed user, read the grants back:

```sql
SELECT table_name, privilege_type
FROM information_schema.table_privileges
WHERE grantee = 'crm_call_center'
ORDER BY table_name, privilege_type;

SELECT table_name, column_name, privilege_type
FROM information_schema.column_privileges
WHERE grantee = 'crm_call_center'
ORDER BY table_name, column_name;
```

`BoneekSchemaContractTest` skips when no real boneek connection is configured.
Point it at a live inventory database in CI to catch upstream schema drift
before it reaches production — but note it checks that columns *exist*, not that
this user may touch them, so it will pass against an under-granted user.

## What the application needs in Coolify

The container gets no compose file, so everything the compose `environment`
block used to inject has to be on the application itself. On top of the `APP_*`,
`DB_*`, `LOG_*`, `SESSION_*` and mail keys that are already in the environment
tab, these five have no other source:

| Variable | Value | Why |
| --- | --- | --- |
| `PHP_OPCACHE_ENABLE` | `1` | Without it every request recompiles the app. |
| `AUTORUN_ENABLED` | `true` | Runs `config:cache`, `route:cache` and `view:cache` at container start. |
| `AUTORUN_LARAVEL_MIGRATION` | `true` | Keeps today's behaviour. See above before changing it. |
| `AUTORUN_LARAVEL_STORAGE_LINK` | `true` | Keeps today's behaviour. |
| `SSL_MODE` | `off` | TLS terminates at Traefik; the container serves plain HTTP on 8080. |

Other application settings that matter:

- **Port**: 8080.
- **Healthcheck**: the `HEALTHCHECK` in the `Dockerfile` is what Coolify waits
  on. It probes `/up`. Leave it in place; the rolling wait is only as good as
  that line, and the base image's own healthcheck probes `/healthcheck`, which
  this app does not serve.
- **Persistent storage**: mount the two volumes the compose project left behind,
  `<app-uuid>_storage-app` at `/var/www/html/storage/app` and
  `<app-uuid>_storage-logs` at `/var/www/html/storage/logs`, keeping the exact
  names so no uploaded file or log history is orphaned. The Log Viewer at
  `/admin/log-viewer` reads the second one.
- **Do not** set a host port mapping, a consistent container name or a custom
  internal name. Coolify turns the rolling update off when any of those is
  present and falls back to stop-then-start.

## The scheduler

`routes/console.php` runs `call-center:reconcile` every ten minutes. It is what
closes picks whose order was changed or deleted in boneek — without it those
orders sit in an agent's list forever, eating their pick limit, and the Picked
Orders screen slowly fills with rows inventory no longer has.

Nothing in the web container runs it. Production needs one of:

- a scheduled task (Coolify's own, or a crontab) running
  `php artisan schedule:run` **every minute**, or
- a second resource off the same image running `php artisan schedule:work`,
  which is what `docker-compose.local.yml` does locally.

Pick one, not both. `withoutOverlapping()` does hold across hosts — the lock
lives in the database cache — so a duplicate scheduler would not double-run the
reconciler, but it would double every task added later that forgets the guard.

## Rollback

The old container stays up until the new one is healthy, so a build that boots
broken fails the deploy and leaves the running version alone. A release that
boots fine but behaves badly is rolled back by deploying the previous commit,
which is again rolling.
