# Laravel Backend Folder Structure

> **This is the target, not a description of the repo.** Where it differs today:
> `app/Helpers` holds a single `helpers.php`, not the three helper classes below; API
> controllers live at `app/Http/Controllers/V1/Admin/{Feature}/`, with the
> web-side controllers — Inertia page renders plus the login and password form
> handlers — under `app/Http/Controllers/{Admin,Auth}/`. Follow
> this for **new** feature folders; follow the surrounding code when editing an
> existing one. `CLAUDE.md` holds the target-vs-current table.

```bash
app
├── Helpers
│   ├── ApiResponseHelper.php
│   ├── FileUploadHelper.php
│   └── SlugHelper.php
│
├── Http
│   ├── Controllers
│   │   ├── Auth
│   │   │   └── AuthController.php
│   │   │
│   │   ├── User
│   │   │   └── UserController.php
│   │   │
│   │   └── Product
│   │       └── ProductController.php
│   │
│   ├── Middleware
│   │
│   ├── Requests
│   │   ├── Auth
│   │   │   ├── LoginRequest.php
│   │   │   └── RegisterRequest.php
│   │   │
│   │   ├── User
│   │   │   ├── StoreUserRequest.php
│   │   │   └── UpdateUserRequest.php
│   │   │
│   │   └── Product
│   │       ├── StoreProductRequest.php
│   │       └── UpdateProductRequest.php
│   │
│   └── Resources
│       ├── Auth
│       │   └── AuthResource.php
│       │
│       ├── User
│       │   └── UserResource.php
│       │
│       └── Product
│           └── ProductResource.php
│
├── Models
│   ├── User.php
│   ├── Product.php
│   └── Order.php
│
├── Providers
│
├── Services
│   ├── Auth
│   │   └── AuthService.php
│   │
│   ├── User
│   │   └── UserService.php
│   │
│   └── Product
│       └── ProductService.php
│
```

# Architecture Flow

```text
Route
  ↓
Feature Controller
  ↓
Request Validation
  ↓
Feature Service
  ↓
Model (Eloquent ORM)
  ↓
Feature Resource
```

# Auth Guards

One audience (staff in `users`) with a session guard for browser pages and a
token guard for the API. Defined in `config/auth.php`.

| Audience | Provider | Browser pages (session cookie) | API (Bearer token) |
|---|---|---|---|
| Staff | `users` | `web` | `sanctum` |

Pick by **how the request authenticates**, not by who is calling:

* `routes/web-admin.php` renders Inertia pages, so it uses the session guard.
  A page navigation sends a cookie, never an `Authorization` header, so a token
  guard rejects every one of those requests.
* `statefulApi()` lets the same `auth:sanctum` endpoint serve the mobile app's
  token and the browser's session cookie.

Staff live under `/admin`, mounted in `bootstrap/app.php`.

# Rules

* Feature-wise folder structure
* Controllers handle request and response only
* Validation stays inside Requests
* Business logic stays inside Services
* Response formatting stays inside Resources
* Reusable utilities stay inside Helpers
* Use Eloquent ORM only, But For Performance & Simplicity Can Use Query Builder
* No Query Builder or Raw SQL
* Keep code clean, minimal, and scalable
* Avoid unnecessary methods and files
* No comments inside code

# Transactions

A Service method wraps in `DB::transaction()` when it performs **more than one
write**. Count writes per method, not per file: a service whose `create`,
`update` and `delete` each issue a single statement needs no transaction, because
one statement is already atomic.

Count the writes you cannot see. A single `Model::create()` is a multi-write when
an observer hangs off it and that observer writes rows of its own. Without a
transaction a failure in the observer leaves the parent row committed on its own.

Nested `DB::transaction()` is fine. An `Actions/*` class opens its own, and
Laravel turns the inner one into a savepoint, so an action stays correct whether
it is called on its own or from inside a wrapped service method.

Side effects that a rollback cannot undo stay out of the transaction body:

* Events and notifications go through `DB::afterCommit()`, so nothing fires for
  work that never landed.
* Queued jobs are covered globally by `'after_commit' => true` in
  `config/queue.php` — a worker can otherwise pick up a job before its rows commit.
* Filesystem writes happen outside the transaction and are compensated on failure
  (see the orphan-file cleanup in `UserService`), because a
  rollback will not delete an uploaded file.
