# "Login as this user" from the Users list — the detailed plan

> For the short version: [`user-impersonation-plan.md`](user-impersonation-plan.md).
> **This file is the one to build from** — every claim carries its file:line.
> References verified against the codebase on 20 August 2026.

A button on every row of Settings → Users. An admin presses it, a confirmation
appears, and then they are **genuinely logged in as that user** — exactly as if
that user had signed in with their own email and password — landing in that
user's own panel.

This document says how, and **where it is dangerous**. The second half is the
real work: impersonation itself is not hard, what is hard is closing off the
things it can break before they break.

---

## 1 — The flow at a glance

```
Users list (row) → [Login as] → ConfirmationModal
      → POST /admin/users/{user}/impersonate
      → ImpersonationService::start($actor, $target)   ← every rule lives here
      → store the real admin's id in the session
      → Auth::guard('web')->login($target)
      → session()->regenerate()
      → activity log: "Started impersonating …"
      → redirect('/admin/dashboard')
```

The way back:

```
banner at the top of any page → [Return to my account]
      → POST /admin/impersonate/stop
      → Auth::guard('web')->login($actor)  ← the session must not be invalidated
      → activity log: "Stopped impersonating …"
      → redirect('/admin/users')
```

There is exactly one difference from `AuthController::login` — no credential
check, and §3 and §4's rules in its place. The rest is identical: `login()`,
`regenerate()`, `activity()`, `redirect()`.

---

## 2 — 🔴 First: without a permission of its own, this is a staff → super-admin door

`config/admin-permissions.php` holds `view users` (line 76) and `edit users`
(line 78). `UserSeeder::ADMIN_EXCLUDED` (line 21) withholds only two things
from `admin` — `delete activity logs`, `view system monitoring` — and
`UserSeeder::STAFF_EXCLUDED` (line 26) only four: `manage access`,
`view activity logs`, `delete activity logs`, `view system monitoring`.

Which means **every `admin` and every `staff` user holds both `view users` and
`edit users` today**.

So if the button sits on either of those, any staff member can walk into
anyone's account — super-admin included. This is not a theoretical risk; it
happens with the roles as they are seeded right now.

### Hence a permission of its own

| | |
|---|---|
| Name | `impersonate users` |
| Added to | `config/admin-permissions.php`, in the users block |
| **And to** | `UserSeeder::ADMIN_EXCLUDED` **and** `STAFF_EXCLUDED` — both |
| Result | out of the box only `super-admin` holds it |
| Group on the Roles screen | `RoleService::GROUP_ALIASES` gets `'impersonate users' => 'users'` |

Adding it to **both** EXCLUDED lists is mandatory. `UserSeeder` hands `admin`
and `staff` everything through `array_diff($permissions, self::X_EXCLUDED)`
(lines 137 and 141), so the moment the permission lands in the config, both
roles get it unless the lists name it — and §2's door swings open again.

The `GROUP_ALIASES` entry is not cosmetic either. `RoleService::GROUP_STRIP`
(line 14) only strips the prefixes `view `, `create `, `edit `, `delete `,
`manage `; `impersonate ` is not among them, so without the alias the Roles
screen grows a stray one-item module called *impersonate users* sitting apart
from the users block.

Note also that `super-admin` is not permission-less: `UserSeeder` line 134
syncs it with the **full** config list, so `getAllPermissions()` on a seeded
super-admin really does return everything — which §3's subset rule depends on.

In production the permission arrives through `PermissionSeeder` (`#67`, `#68`):

```sh
php artisan db:seed --class=PermissionSeeder --force
```

Then it has to be granted from Admin → Roles to whoever should have it.
Creating the row and granting it are two different things.

---

## 3 — 🔴 Who may become whom

The most important rule in this feature. Get it wrong and the permission system
stops meaning anything.

| Rule | Why |
|---|---|
| Nobody holding the `super-admin` role may be impersonated | `AppServiceProvider.php:17-18` — `Gate::before` returns `true` for that role on every gate. If an admin can become a super-admin, that admin *is* a super-admin |
| The target's effective permissions must be a **subset** of the actor's | Nobody gets to borrow more power than they have by wearing someone else's account. Compared through `getAllPermissions()`, not by role name |
| No impersonating yourself | `$actor->id === $target->id` |
| No impersonating while impersonating (nesting) | refuse if the session already carries the marker |
| The target must still exist | there is no *inactive* state to check: `User` carries no `SoftDeletes` trait and the `users` table has no `deleted_at` or `status` column — deletion is permanent. Route-model binding already 404s a missing id, so this rule is about not writing code for a state that does not exist |

For a super-admin actor the subset check can short-circuit to *pass* — they
clear every gate through `Gate::before` regardless. Doing it explicitly keeps
the rule correct even if the `super-admin` role's permission rows ever drift
from the config list.

### 🔴 This rule cannot live in a Policy

`Gate::before` short-circuits gates for super-admins. So a
`UserPolicy::impersonate()` would **never run** for them — the
super-admin → super-admin case would pass silently, and that is precisely the
case being guarded against.

The rules belong in **`App\Services\Auth\ImpersonationService::start()`**,
**before** the guard is touched, throwing `AccessDeniedHttpException`. The
controller does request/response and nothing else. The route still carries
`permission:impersonate users` middleware — but that is the second layer, not
the first.

The controller is `App\Http\Controllers\Auth\ImpersonationController`, beside
`AuthController` — not in `Http/Controllers/Admin/`, which holds render-only
`*PageController` classes. This one does the same shape of work `AuthController`
does: POST, swap the guard, write an activity row, redirect.

> Related, though outside this feature: anyone holding `edit users` can today
> assign the `super-admin` role to anyone. That is already true and this plan
> does not change it — but it is worth knowing, because §3's subset rule does
> not close that path.

---

## 4 — How the session changes

The same path as `AuthController::login`, with §3 where the credential check
would be.

```php
session()->put('impersonator_id', $actor->id);
session()->put('impersonated_at', now()->timestamp);

Auth::guard('web')->login($target);

session()->regenerate();   // optional — see the first bullet
```

- **`regenerate()` does not need to be called separately.**
  `SessionGuard::updateSession()` already does `$this->session->regenerate(true)`
  (`vendor/laravel/framework/…/Auth/SessionGuard.php:584-589`), so `login()`
  itself rotates the session id and issues a fresh `_token`.
  `AuthController::login:24` calls it again as belt and braces — harmless, but
  not required.
- **Never `invalidate()`.** It flushes the attribute bag, so `impersonator_id`
  disappears and the admin is trapped inside. `AuthController::logout` does
  exactly that (`AuthController.php:37`) — see §6.
- **The `web` guard only.** The Users list holds only `web` users. The client
  portal's `client-web` guard is out of scope this round (§14).
- **No Sanctum token.** Impersonation lives in the browser session only.
  Minting a token in the target's name would outlive the session — a permanent
  key nobody can take back.
- **No `remember me` cookie.** `login($target, remember: false)`. A remember
  cookie would bring the impersonation back after the browser is closed and
  reopened.
- **No time limit of its own** (decided). No `ExpireImpersonation` middleware
  is being built. That does not mean "forever" — `SESSION_LIFETIME=120`, so it
  expires after 120 minutes of inactivity like any other session.
  `impersonated_at` still goes into the session, because the banner shows
  "since when" and because the data is worth having if a limit is added later.

### 🔴 The trap that stops the feature working if you don't know it: Sanctum's `AuthenticateSession`

`config/sanctum.php:82` registers
`'authenticate_session' => AuthenticateSession::class`, and `.env` carries
`SANCTUM_STATEFUL_DOMAINS=boneek-crm.localhost,localhost,127.0.0.1` — meaning
every `/v1/*` XHR the panel fires goes through that middleware.

What it does
(`vendor/laravel/sanctum/…/Http/Middleware/AuthenticateSession.php:43-58`):

```php
$request->session()->get('password_hash_'.$driver)   // what the session holds
$request->user()->getAuthPassword()                  // who is logged in now
// on a mismatch:
$shouldLogout->each->logoutCurrentDevice();
$request->session()->flush();
throw new AuthenticationException(...);
```

After impersonating, the session still holds the **admin's** `password_hash_web`
while `$request->user()` is now the **target** — two different hashes. So the
very next `/v1/*` call **flushes** the session.

And that call will not be long coming: `NotificationBell.tsx:7,69` —
`POLL_INTERVAL_MS = 20000`, `setInterval(load, …)`. So **within 20 seconds** the
impersonation tears itself down, `impersonator_id` and all, and the admin is
bounced to the login page.

**The fix is one line**, after the swap:

```php
session()->forget('password_hash_web');
```

The middleware then re-stamps it for the new identity by itself. Needed in both
places — entering and leaving.

---

## 5 — Where it lands

`redirect()->to('/admin/dashboard')` — **not** `intended()`. `intended()` sends
the user to a url left in the session, which may be a leftover from the admin's
own journey, and the target may not have permission to open it.

No need to branch by role: `/admin/dashboard` opens for everyone, and the
sidebar (`resources/js/config/sidebarNav.ts`, filtered in
`Sidebar.tsx:26,41`) sifts itself by permission — so impersonating a
call-center-agent shows that agent's menu.

---

## 6 — 🔴 The way back, and the Logout trap

The header's ordinary **Logout** button posts to `/admin/logout` →
`AuthController::logout` → `session()->invalidate()` (line 37). Pressing it
while impersonating wipes the whole session including `impersonator_id` — the
admin is thrown out to the login page and has to sign in again with their own
password.

So while impersonating:

- the header's user menu shows "Return to my account" **in place of** Logout
  (replacing it, not sitting beside it). Today that item is
  `SiteHeader.tsx:23-24,82-86`.
- `POST /admin/impersonate/stop` carries **no permission middleware**, because
  the target may not hold `impersonate users` and that would slam the door
  shut. Only `auth:sanctum` (the group `routes/web-admin.php:35` already uses)
  plus a check that the session carries the marker.
- on return, `redirect('/admin/users')`.
- **if the actor is gone** — someone deleted their account while the
  impersonation was running, and users are deleted for good (§3) —
  `login($actor)` will not work.
  Clear the marker, log out normally, and send them to `/admin/login`. Silently
  leaving them sitting there as the target is the worst possible outcome.

### 🔴 Do not call `logout()` on the way back

It feels natural — log the target out, log the admin in. But
`SessionGuard::logout()` (`…/Auth/SessionGuard.php:650-658`) does this:

```php
if (! is_null($this->user) && ! empty($user->getRememberToken())) {
    $this->cycleRememberToken($user);
}
```

That is, it **rotates the target's own `remember_token`**. If that person is
signed in on their laptop and phone with "remember me", the moment an admin
leaves an impersonation they are kicked off every device — and nothing anywhere
says why.

Call `Auth::guard('web')->login($actor)` directly. `login()` replaces the
session's auth id by itself; nothing extra is needed to clear the old identity.
And `session()->forget('password_hash_web')` here too (§4).

---

## 7 — 🔴 Audit: who actually did it

`app/Support/ActivityLog.php:51-73` — `resolveCauser()` returns
`app('request')->user()`. While impersonating that is the **target**, not the
actor. So turning impersonation on as things stand would write every activity
row under the wrong person's name, with nothing anywhere recording who it
really was.

Two things are needed:

1. **`impersonator_id` on `activity_logs`** (nullable). `resolveCauser()` keeps
   returning the effective user — because the work genuinely happened under
   that identity — but the real person's id sits beside it. A badge on the
   Activity Log screen: *"via Admin Name"*.
2. **A row at the start and a row at the end** — `"Started impersonating {name}."`
   (causer = actor) and `"Stopped impersonating {name}."`. Without these the
   timeline shows only that person's activity, never the entering and leaving.

**Where the column gets filled:** in `ActivityLog::log()` (line 39), reading
`session('impersonator_id')`. But that class is also called from the console
and from queues — the `runningInConsole()` guard inside `resolveCauser()`
(line 53) is the proof — and there is no session there, so it has to check
`app()->bound('session')` first or seeders and the scheduler will blow up.

**Surfacing it takes three more edits** the earlier draft missed:

- `app/Models/ActivityLog.php` — an `impersonator()` relation, a plain
  `belongsTo(User::class)`. **Not `withTrashed()`**: `causer()` needs it
  (line 17) because `Client`, `Employee`, `Company` and `Project` are
  soft-deletable, but `User` is not. Which is precisely why `nullOnDelete()`
  below is load-bearing — a deleted admin's row is gone for good, and a
  restricting FK would block the delete outright.
- `app/Services/ActivityLog/ActivityLogService.php:19` — currently
  `->with('causer')`; the new relation needs eager-loading beside it or the
  list issues a query per row.
- `app/Http/Resources/ActivityLog/ActivityLogResource.php:21` — `causer` is
  exposed as a flat name string; `impersonator` needs the same treatment, and
  `resources/js/pages/admin/activity-logs/page.tsx:102-104` (the "Performer"
  column) renders the badge.

> **Decision: a separate ALTER migration**
> (`..._add_impersonator_id_to_activity_logs_table`). This repo's usual rule is
> to make schema changes in the original `create_*_table`, but
> `2026_08_03_000000_create_activity_logs_table.php` has **already shipped to
> production**, and a migration that has run does not run again.
> `2026_08_18_090000_drop_product_details_table.php` is a separate migration for
> exactly this reason — *"deployed databases still carry the table and only a
> migration reaches them"*. Same here.
> The column is `nullable`, FK to users, `nullOnDelete()` — if the
> impersonator's account is deleted the log row survives, only the name goes.

---

## 8 — 🔴 The sharpest danger: call center

Every piece of call center work is written against `request->user()` —
`call_center_picks.user_id`, `call_center_activities.user_id`. While
impersonating that is **the agent's name**, and there is no column anywhere
saying "it was actually the supervisor".

What follows from that:

| What happens | Why it can't be undone |
|---|---|
| `AgentPerformanceService` counts the work on that agent's scorecard | The whole Performance menu (`#66`) rests on "what the agent did themselves" — that foundation becomes a lie |
| `orders.call_center_charge` is stamped once | CLAUDE.md: stamped once, never written again, deducted from the dropshipper's payout. A real merchant gets billed |
| Send to Supplier opens an order in another business's name | The boneek connection has no DELETE grant — there is no path back |
| `order_logs` is insert-only | The merchant sees that line and it cannot be removed |
| Approving closes the pick | The real agent can then no longer open their own order (`guardPicked`) |

`AgentPerformanceService`'s own docblock and CLAUDE.md say the same thing:
**nobody may be credited with a call they did not make.** Impersonation is a
machine for doing exactly that.

### Decision: nothing is blocked — full impersonation

The list above has not stopped being true, but the decision is that **logging in
as another account means being able to do everything that account could do**.
Call center writes are not fenced off separately: pick, unpick, status change,
item edit, comment, Send to Supplier — all open.

The reasoning rests on two things:

1. **Only `super-admin` gets the permission** (§2). A super-admin can already
   call every call center endpoint directly today on the strength of
   `Gate::before` — impersonation gives them no new power, only a different
   pair of eyes.
2. **Half-open impersonation is the worst of both.** A supervisor who can see
   the problem on the agent's screen but cannot fix it gets no support value out
   of the feature, and will go around it and do the same work from their own
   account — at which point not just the attribution but the context is lost too.

**The price, stated plainly:** every consequence in the table above stands.
`call_center_picks.user_id` and `call_center_activities.user_id` will carry the
**agent's** id, there is no impersonator column there, and
`AgentPerformanceService` will count the work on the agent's scorecard.
`call_center_charge` and the supplier order are both irreversible.

**The answer is not schema, it is the bracket.** §7's "Started/Stopped
impersonating" rows fence the whole window, so "from 10:05 to 10:20 admin X was
wearing agent Y's account" can always be stated, and anything written inside
that window can be traced back to them. Separate columns on
`call_center_picks`/`call_center_activities` are **not** being added: that would
mean touching every query in `AgentPerformanceService` and the foundation of the
Performance menu, which is a bigger job than this feature.

> ⚠️ **So the start/stop log rows are not optional** — they are now the only
> audit there is. And because there is no time limit (§4), an admin who closes
> the browser and walks away never writes the "Stopped" row; the window then has
> to be assumed open until the session expires.

---

## 9 — What is blocked while impersonating: nothing

An earlier draft had a `DenyWhileImpersonating` middleware here. After §8's
decision it is **not being built** — impersonating means being able to do
everything that account could do, so no routes are fenced off.

The things that were considered for blocking, and why they are open too:

| What was going to be blocked | Why it isn't |
|---|---|
| Every call center write | §8 |
| `PATCH /v1/profile` (password / email) | Anyone holding `edit users` can already set someone else's password today through `UserService.php:100-101`, from the Users → Edit screen. Blocking it while impersonating would protect nothing new, only cripple the feature |
| Writes on the Users / Roles / Access screens | Same argument. The permission is super-admin-only, and they can open those screens under their own name |
| notification delete / mark-all-read | That person's own notifications, which they could delete themselves |

**One exception, and it is mechanical rather than security:** you cannot
impersonate while impersonating. The session has one slot for
`impersonator_id`, and nesting loses the way back. This is not middleware — it
is one of the rules inside `ImpersonationService::start()` (§3), next to the
others.

**`/admin/impersonate/stop` is naturally always open** (§6).

---

## 10 — UI

### Button

In the Actions column of
`resources/js/pages/admin/users/index/page.tsx` (lines 77-90), to the right of
Edit. That column currently holds a single Edit `Link` and nothing else. There
is no kebab-menu component in this repo, and
`resources/js/pages/admin/call-center/agents/columns.tsx:64,73` already
demonstrates the pattern of two `size="sm"` Buttons in a row — follow that.

- Gate: `usePermissions().can('impersonate users')`
  (`resources/js/hooks/usePermissions.ts:11`; `can()` returns `true` for
  super-admins at line 21).
- On a row that cannot be entered (§3), show `—` instead of the button.
- **The server decides** which rows are available: a `can_impersonate` boolean
  on `UserResource`. The frontend's own reckoning is courtesy, not security.
- ⚠️ **Watch the cost.** `UserResource` already calls `getAllPermissions()` on
  every row (line 23), i.e. a query per user in the list. §3's subset rule wants
  exactly that same thing. So the actor's own permission set must be resolved
  **once** and held for the request, with the comparison done in PHP — asking
  the actor again per row would make the list heavy.

### Confirmation

`ConfirmationModal` (`@/components/ui`), `variant="danger"`. Title *"Sign in as
{name}?"*. Three things in the body: (1) what you do next will be recorded under
that user's name, (2) all of it goes to the audit log, (3) the way back is in
the banner.

### 🔴 POST, not GET

Inertia's `Link` sends GET, and browsers may prefetch it. Which would mean
merely hovering could sign someone in. **`POST` + CSRF**, from a `Button`'s
`onClick`.

### Banner

The **first child** of the root column in
`resources/js/components/common/DashboardLayout.tsx` (line 17, above the
`SiteHeader` at line 18) — not `fixed`, or it will sit on top of the header.
Full width, amber, warning icon, the name, and **Return to my account** on the
right.

An `impersonation: { name, since }` entry in the Inertia shared props
(`HandleInertiaRequests::share`, lines 32-43, which today shares `auth`,
`portal` and `flash`).

> **Note — no banner on the 403 page.** `bootstrap/app.php`'s
> `$exceptions->respond()` renders Inertia pages for 403/404 —
> `resources/js/pages/admin/errors/forbidden/page.tsx` and `not-found/page.tsx`.
> (There are no blade error pages in this app, so there is **no** need to write
> a `403.blade.php`.) But those pages do not use `DashboardLayout` — they are
> full-screen in their own right — so there is no banner and no Return button on
> them. After §9 none of our own middleware returns 403 any more, but hitting a
> 403 on some page because the target holds fewer permissions is entirely
> normal — it is the single most common thing that happens during an
> impersonation.
> It is not a dead end (the "Back to dashboard" link is there at
> `forbidden/page.tsx:28-33`, and the banner is back as soon as you follow it),
> but having `forbidden/page.tsx` read the shared `impersonation` prop and show
> a Return button shortens the path by a step.

---

## 11 — File list

**New**

| File | What |
|---|---|
| `app/Services/Auth/ImpersonationService.php` | every rule from §3, start/stop |
| `app/Http/Controllers/Auth/ImpersonationController.php` | two methods, request/response only |
| `resources/js/components/common/ImpersonationBanner.tsx` | §10 |
| `tests/Feature/ImpersonationTest.php` | §12 |
| `tests/Feature/ImpersonationCallCenterTest.php` | §12's call center case, which needs the boneek fixtures |
| `database/migrations/..._add_impersonator_id_to_activity_logs_table.php` | §7 |

**Changed**

| File | What |
|---|---|
| `config/admin-permissions.php` | `impersonate users` |
| `database/seeders/UserSeeder.php` | both EXCLUDED lists |
| `app/Services/Role/RoleService.php` | `GROUP_ALIASES` |
| `app/Providers/AppServiceProvider.php` | binds `ImpersonationService` as a singleton, so the actor's permission set is memoized once per request rather than per Users row (§10) |
| `routes/web-admin.php` | two POST routes |
| `app/Support/ActivityLog.php` | `impersonator_id` |
| `app/Models/ActivityLog.php` | `impersonator()` relation |
| `app/Services/ActivityLog/ActivityLogService.php` | eager-load the relation (§7) |
| `app/Http/Resources/ActivityLog/ActivityLogResource.php` | expose the impersonator (§7) |
| `resources/js/pages/admin/activity-logs/page.tsx` | *"via …"* badge on the Performer column (§7) |
| `app/Http/Middleware/HandleInertiaRequests.php` | shared `impersonation` |
| `app/Http/Resources/User/UserResource.php` | `can_impersonate` |
| `resources/js/pages/admin/users/index/page.tsx` | button + modal |
| `resources/js/pages/admin/errors/forbidden/page.tsx` | Return button (§10) |
| `resources/js/components/common/DashboardLayout.tsx` | banner |
| `resources/js/components/common/SiteHeader.tsx` | Logout → Return |
| `resources/js/types/inertia.d.ts` | the shared `impersonation` prop |
| `resources/js/pages/admin/users/types.ts` | `can_impersonate` |
| `DEPLOY.md` | the permission step (§13) |

Both type files are mandatory, not tidiness: CLAUDE.md requires a clean
`npx tsc --noEmit`, and `usePage().props.impersonation` / `row.can_impersonate`
do not exist on `PageProps` (`inertia.d.ts:10,13`) or on `User`
(`users/types.ts`) until they are declared.

### Two wiring details

- **The impersonate route cannot go inside the users group.**
  `routes/web-admin.php:44-51` is `Route::controller(UserPageController::class)
  ->prefix('users')`, so anything registered in there binds to
  `UserPageController`. Register both POST routes outside it, with explicit
  controller references.
- **Permission middleware goes in the controller, not the route file.**
  `routes/admin-v1.php` contains no `permission:` at all — the convention here is
  `HasMiddleware::middleware()` inside the controller, as
  `UserController.php:20-29` does. So:
  `new Middleware('permission:impersonate users', only: ['start'])`, which also
  leaves `stop` unguarded exactly as §6 requires. (The web users routes carry no
  permission middleware whatsoever — the page renders and the API enforces —
  so this route must bring its own.)

---

## 12 — Tests

`tests/Feature/ImpersonationTest.php`, at minimum:

- `403` without the permission
- **`admin` → `super-admin` refused** (§3's central rule)
- refuse anyone holding more permissions than the actor
- refuse yourself, refuse nesting
- on success `Auth::id()` changes and `impersonator_id` is in the session
- stopping brings the real admin back
- the stop route works without the permission
- two activity rows are written, and work done in between carries `impersonator_id`
- **`password_hash_web` is absent from the session after the swap** (§4's trap) —
  without this one assertion the feature stays green in tests and dies in the
  browser in 20 seconds
- the target's `remember_token` is **unchanged** after stopping (§6's trap)
- **a call center pick succeeds while impersonating**, and
  `call_center_picks.user_id` holds the target's id — §8's decision is
  deliberate, not an accident; without a test someone will later mistake it for
  a bug and block it

> ⚠️ `TestCase::actingAs` (`tests/TestCase.php:20-27`) turns a role-less user
> into a super-admin — which is why the call center tests wrote their own
> `scopedAgent()` helper (e.g.
> `tests/Feature/ApiCallCenterOrderDetailsAccessTest.php:96`). Every role here
> must be assigned explicitly too, or every assertion is meaningless.

---

## 13 — Deploy

1. Deploy the release (`php artisan migrate --force` brings §7's ALTER migration)
2. `php artisan db:seed --class=PermissionSeeder --force` — creates the
   permission **row**
3. No grant needed: `impersonate users` is super-admin-only, and they get it
   through `Gate::before`. To give it to anyone else later, Admin → Roles

`DEPLOY.md` currently documents no seeder step at all — its "Deploying a
release" (line 52) and "Migrations" (line 61) sections cover `migrate --force`
only. So this goes in as a **new `## Permissions` section** rather than an edit
to existing text, written for every future permission and not just this one:
`PermissionSeeder` creates rows and grants nothing, which is why it is the one
seeder safe to run against a live database, while `UserSeeder` must never be —
its `syncPermissions()` would replace whatever an admin granted by hand.

---

## 14 — Not in this round

| | Why |
|---|---|
| Client portal (`client-web`) impersonation | They are not in the Users list, and admins already have a separate mechanism for clients — `AccessController::grantClientLogin` (`app/Http/Controllers/V1/Admin/Access/AccessController.php:32`). Two guards can be authenticated in one session at once, so this is not a matter of changing a parameter; it is its own round |
| Mobile / Sanctum token impersonation | §4 — a token outlives the session |
| A marker in the browser tab title | the banner is enough |
| Cancelling a running impersonation session remotely | there is no limit either (§4), so the only ways back right now are that browser's Return button or the session's 120 minutes running out. Assumed sufficient this round, because the permission is super-admin-only |

---

## 15 — The decisions (already made — 20 August 2026)

| | Decision | Consequence |
|---|---|---|
| Migration (§7) | **a separate ALTER migration** | `create_activity_logs_table` untouched; deployed databases get the column |
| Call center writes (§8) | **nothing blocked** — logging in as an account means doing everything that account could | `DenyWhileImpersonating` middleware dropped; the audit rests on the start/stop bracket |
| Time limit (§4) | **no limit** | `ExpireImpersonation` middleware dropped; `SESSION_LIFETIME=120` is the ceiling |
| Permission (§2) | **`super-admin` only** | added to `ADMIN_EXCLUDED` **and** `STAFF_EXCLUDED` |

Two of these have to be read together: **everything open** is safe only
**because** the permission is super-admin-only. The day it is given to `admin`
or `call-center-supervisor`, §8 has to be reopened — at that point that person
could use impersonation to do things they could not do under their own name,
which is precisely what §3's subset rule exists to prevent.
