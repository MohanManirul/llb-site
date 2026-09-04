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
  rides on it today: the staff password-reset mail
  (`AdminResetPassword` `implements ShouldQueue`). With `database` and no
  worker those would be written to the `jobs` table and never run — "forgot
  password" would appear to succeed and send nothing — so the worker is not
  optional.
- **The scheduler** runs whatever `routes/console.php` schedules. Nothing is
  scheduled there today, so it is idle — keep it wired up anyway, since the
  first task added expects it. There are two ways to run it — see
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
removing one is a migration's job; only a migration reaches a deployed database.

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

`routes/console.php` carries no scheduled tasks at the moment, so the scheduler
has nothing to fire. Leave it configured regardless: the first task added starts
running without a deploy change.

Nothing in the web container runs it. Production needs one of:

- a scheduled task (Coolify's own, or a crontab) running
  `php artisan schedule:run` **every minute**, or
- a second resource off the same image running `php artisan schedule:work`,
  which is what `docker-compose.local.yml` does locally.

Pick one, not both. `withoutOverlapping()` does hold across hosts — the lock
lives in the database cache — but a duplicate scheduler would double every task
that forgets the guard.

## Rollback

The old container stays up until the new one is healthy, so a build that boots
broken fails the deploy and leaves the running version alone. A release that
boots fine but behaves badly is rolled back by deploying the previous commit,
which is again rolling.
