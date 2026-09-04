---
name: commit-changes
description: Commit and push changes safely — only when explicitly asked, never directly onto main/production, branching as dev/{name}/{branch_name} first. Use whenever a commit or push is involved.
---

# Commit Changes

Safe git workflow for committing and pushing work.

## 1. Only act when told

Never `git commit` or `git push` unless the user explicitly asks for it. Staging,
diffing, and inspecting are fine at any time — but committing and pushing require an
explicit request. Don't bundle a commit into an unrelated task.

## 2. Protect main / production

Before committing, check the current branch:

```bash
git branch --show-current
```

If it is `main` or `production`, do **not** commit there. Create a new branch first
(see below). If already on some other branch, commit there — don't create another.

## 3. Branch naming

New branches follow `dev/{name}/{branch_name}`:

- `{name}` — the developer's handle. Don't hardcode it; resolve it in this order:

  1. Reuse the handle this developer already uses. Check existing branches and match
     against the current git identity:

     ```bash
     git config user.name
     git config user.email
     git branch -a --format='%(refname:short)' | grep '^\(origin/\)\?dev/'
     ```

     e.g. `Jane Doe <janedoe123@example.com>` already owns `dev/jane/*` →
     use `jane`, not `janedoe123`. The handle rarely matches the email verbatim.
  2. No existing branch for them: derive a short, lowercase, kebab-case handle from
     `user.name` / `user.email` — existing handles are single-word first names
     (`jane`, `alex`, `sam`).
- `{branch_name}` — short, kebab-case, describing the work
  (e.g. `product-sales-channel-filter`).

```bash
git checkout -b dev/{name}/{branch_name}
```

## 4. Commit

- Stage only the intended files — exclude local-only files such as
  `.claude/settings.local.json`.
- Write a clear, concise commit message.

## 5. Run tests before pushing

After committing and **before** pushing, run the `test-changes` skill to keep the
suite in sync with what you just committed. If it produces fixes or new/updated
tests, commit those too (amend or add a follow-up commit) until the suite is green.
Only then proceed to push.

## 6. Push

Push, using `-u` to set upstream on a new branch:

```bash
git push -u origin dev/{name}/{branch_name}
```

> Git runs directly on the host. Any artisan/composer/pint step goes through Docker —
> `docker compose exec app <cmd>`.
