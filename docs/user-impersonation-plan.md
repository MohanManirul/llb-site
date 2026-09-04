# Login as this user

> Full detail (with file:line): [`user-impersonation-plan-details.md`](user-impersonation-plan-details.md)
> The decisions are made — this is ready to build.

## What we're building

A button on every row of the Users list → confirm → the admin is **genuinely
logged in as that user**. Exactly what `AuthController::login` does, with the
rules below in place of the Password check.

```
[Login as] → confirm → POST → check the rules → login($target) → /admin/dashboard
                                     ↓
                       banner: "Signed in as X   [Return]"
```

**Nothing is blocked once you're in** — everything that account could do, you can do.

## Three safety rules

| | |
|---|---|
| **A new permission** `impersonate users`, `super-admin` only | Today every `admin` and every `staff` user holds `view users` and `edit users` — put the button on either and any staff member walks into anyone's account. Must be added to **both** `ADMIN_EXCLUDED` and `STAFF_EXCLUDED` |
| **No impersonating a super-admin** | `Gate::before` Passes them every gate — if an admin can become a super-admin, they are one. The rule goes in `ImpersonationService`, **not in a Policy** (Gate::before short-circuits policies). Along with: not yourself, no nesting, and nobody holding more permissions than you |
| **The real name in the audit** | `resolveCauser()` will keep returning the target. Needs an `activity_logs.impersonator_id` column plus a log row on the way in and on the way out |

Leaving everything open is safe **because** the permission is super-admin-only —
they can already call every endpoint under their own name today. The day `admin`
or a supervisor is given it, that decision has to be reopened.

## Two traps (miss either and the feature falls over)

**Sanctum breaks it in 20 seconds.** `AuthenticateSession` compares the session's
Password hash; after the swap it no longer matches → session flush.
NotificationBell polls the API every 20 seconds, so you're back on the login page
within 20 seconds.
→ `session()->forget('Password_hash_web')` after the swap.

**Don't call `logout()` on the way back.** It rotates the *target's*
`remember_token` — that person is kicked off every one of their devices. Call
`login($actor)` directly.

Two smaller ones: never `session()->invalidate()` (it erases the way back), and
make the button **POST** (a GET could sign you in on browser prefetch).

## The way back

While impersonating, the header shows **"Return to my account"** in place of
Logout, plus the banner above. The stop route carries **no permission** — if the
target doesn't hold it, the door back is shut. No limit of its own;
`SESSION_LIFETIME=120` is the ceiling.

## The work

| New | `Services/Auth/ImpersonationService`, `Controllers/Auth/ImpersonationController`, ALTER migration, banner, test |
|---|---|
| Changed | permission config + `UserSeeder`, `RoleService`, 2 routes, `ActivityLog` (support + model + service + resource + screen), `UserResource`, Users page, `DashboardLayout`, `SiteHeader`, `HandleInertiaRequests`, forbidden page, 2 type files, `DEPLOY.md` |

## Decisions

| | |
|---|---|
| Migration | separate ALTER migration (the original has already shipped to production) |
| Time limit | none |
| Permission | `super-admin` only |

## Not in this round

Mobile/token impersonation (a token outlives the session).
