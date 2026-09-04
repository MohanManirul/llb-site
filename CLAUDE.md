# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A CRM built as a **Laravel 13 + Inertia + React 19** app that talks to its own REST API.
One Laravel app serves both the Inertia pages and the versioned `/v1/*` JSON endpoints; a
mobile client uses the same API with Sanctum Bearer tokens.

The API is versioned by URL prefix, mounted from the `using:` closure in `bootstrap/app.php`.
Two files feed the one `/v1` mount (names `v1.*`): `routes/admin-v1.php` for the staff API
and `routes/client-v1.php` for the portal's. The `web` side is `routes/web.php` plus
`routes/web-admin.php` at `/admin`.

There is no unversioned `/api/*`, and **no `/v2`** — `route:list` returns zero routes under
either prefix. When a v2 becomes necessary, mount it beside these; do not assume it exists.

## Conventions: target vs. current

`docs/code-structure/*.md` describe the **target** structure. Parts of the repo have not been
migrated yet. Before writing a file, check which state its area is in and say which one
you are following.

| | Target (the docs) | Current code |
|---|---|---|
| Frontend language | TypeScript (`.tsx`, `types.ts`) | ✅ **done** — all TypeScript, zero `.js`/`.jsx` in `resources/js` |
| Page path | `js/pages/{feature}/page.tsx` | ✅ mostly — `js/pages/admin/{feature}/{index,create,edit}/page.tsx`, plus a per-feature `types.ts` |
| Components | `js/components/ui` + `index.ts` barrel, `cn()` from `lib/utils` | `js/components/ui` **with** the `index.ts` barrel; still no `cn()`, no `clsx`/`tailwind-merge` |
| API layer | per-feature `api.ts` | 🟡 both — `js/lib/api-client.ts` is the shared axios instance and 37 files import it directly. Only `payment/api.ts` and `projects/api.ts` are per-feature layers, and `projects/api.ts` wraps a single endpoint while the rest of that feature still calls the shared client. Follow the surrounding file, not the folder |
| Controllers | `Http/Controllers/{Feature}/` | `Http/Controllers/V1/{Admin\|Client}/{Feature}/` |
| Services | `Services/{Feature}/Service.php` | ✅ **done** — 16 per-feature folders; only `Services/ApiResponseService.php` is still flat |
| Comments | none | existing PHP is heavily commented, often in Bengali/Banglish |

Rules for working in this gap:

- Frontend files are TypeScript — type new props, state and API payloads rather than
  reaching for `any`. Run `npx tsc --noEmit` before calling frontend work done. Note
  `tsconfig.json` sets `strict: false`, so a clean run proves shapes line up, not that
  nulls are handled — it is a floor, not a guarantee.
- Import shared inputs from the `@/components/ui` barrel (`TextInput`, `SelectInput`,
  `SearchableSelect`, `Button`, …). Do **not** invent `cn()` — it does not exist here.
- Follow the target docs for **new** feature folders; follow surrounding code when editing
  existing ones.
- "No comments inside code" applies to code you write. Do not strip existing comments —
  they explain *why* and are load-bearing.

- [`Backend Structure`](docs/code-structure/backend-structure.md) — layering, flow, rules
- [`Frontend Structure`](docs/code-structure/frontend-structure.md) — layering, flow, rules

## Commands

**Ask whether the environment is local or Docker before running anything** — both are in
use and the commands are not interchangeable.

| | Local (Laragon/native) | Docker |
|---|---|---|
| Run everything | `composer run dev` | `docker compose up -d --build` |
| Tests | `composer run test` | `docker compose exec app php artisan test` |
| Artisan | `php artisan <cmd>` | `docker compose exec app php artisan <cmd>` |

```bash
php artisan test --filter=ApiCompanyIndexTest        # single class
php artisan test --filter=test_it_searches_by_name   # single method
vendor/bin/pint                                      # formatter, Laravel preset
```

Docker ports: app `:8030`, Vite HMR `:8031`, Postgres `:8032`, Mailpit SMTP `:8033`,
Mailpit dashboard `:8034`.
**Never run `docker compose down -v`** — it deletes the local database.

## Architecture

**Pages are render-only.** The `web` routes never fetch page data — a thin page controller
renders a React page and passes an id (`['projectId' => $project]`), at most alongside a
cheap authorisation flag (`ProjectPageController::reports` passes `canSubmitReports`). The
only other props are request-derived values the page cannot read for itself: the
reset-password pages pass the `token` and `email` off the URL. Never domain data. The page
loads its own data from `/v1/*`
via `resources/js/lib/api-client.ts` (its `baseURL` is `/v1`, so callers write
`api.get('/projects')`). All logic lives in `app/Http/Controllers/V1/`, split into
`Admin/` for the staff API and `Client/` for the portal's own endpoints.

**Flow:** Route → Controller (request/response only) → FormRequest (validation) → Service
(business logic) → Model → Resource (formatting). Multi-step domain operations live in
`app/Actions/`, triggered from `app/Observers/`.

## Database

- Eloquent only. Query Builder is allowed for performance; **no raw SQL**.
- Follow ACID principle, FK constraints enforced; wrap multi-write Service methods in transactions.

# Note

- Do not commit or push without approval or if told you.
- Do not add any comments in code.
