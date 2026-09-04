---
name: test-changes
description: Create or update test cases for new features/changes, then run the suite and fix failures until all tests Pass. Use after adding a feature or changing behavior.
---

# Test Changes

Keep the test suite in sync with recent work: figure out what changed, write or
update only the tests that are genuinely needed, run them, and fix failures until
everything is green.

## 1. Identify what changed

Look at the working tree and recent history to find changed/added controllers,
models, services, actions, and routes:

```bash
git status
git diff
git log --oneline -10
```

Map each change to the test that should cover it. Everything lives flat in
`tests/Feature/` — `tests/Unit/` currently holds only `ExampleTest.php`, so put
tests there only for genuinely pure logic with no framework/database involvement.

## 2. Only touch tests that are actually needed

Do **not** churn the suite. Be conservative:

- **Add** a new test only when a change introduces behavior that nothing currently covers.
- **Update** an existing test only when a change alters behavior its assertions depend on.
- If a change is already covered, or is non-behavioral (formatting, comments,
  renames where tests still Pass), write/update nothing — just run the suite to confirm.

## 3. Follow the repo's test conventions

Read a neighbouring test before writing one — `ApiCompanyIndexTest` (API) and
`ProjectPagesTest` (Inertia page shells) are the two shapes almost everything follows.

- Namespace `Tests\Feature`, extend `Tests\TestCase`, `use RefreshDatabase`.
  Tests run on sqlite `:memory:` (see `phpunit.xml`).
- Name the file after what it covers, flat in `tests/Feature/`: `Api{Feature}...Test.php`
  for API endpoints, `{Feature}PagesTest.php` for route/page shells.
- Sign in with `$this->actingAs(User::factory()->create())`. `Tests\TestCase` overrides
  `actingAs` so a user with no roles is auto-granted `super-admin` — assign a role
  **before** `actingAs` when the test is about permission gating.
- `UserFactory` is the only factory in `database/factories`. For every other model,
  create rows directly (`Company::create([...])`), usually via a small private
  `makeX()` helper on the test class.
- Hit API routes with `getJson`/`postJson`/`deleteJson`, either by literal path
  (`/api/companies`) or by name (`route('api.companies.search')`) — route names are
  the `api.*` prefix, there is no `v1.*`.
- There is **no** response envelope. Assert on the payload as returned: paginators
  via `assertJsonStructure(['data' => [[...]], 'total', 'current_page', ...])`, plus
  `assertJsonCount`, `assertJsonPath`, `assertJsonFragment`.
- Page tests assert the shell renders (`->get('/admin/projects')->assertOk()`) and that
  guests are redirected (`->assertRedirect('/admin/login')`) — pages carry no server data.
- Method names read as sentences: `test_it_*`, `test_can_*` / `test_cannot_*`.
- Per `CLAUDE.md`, don't add comments to the tests you write.

## 4. Run the tests

The environment is Docker (`COMPOSE_FILE=docker-compose.local.yml` in `.env`, so the
plain `docker compose` form works). Scope tight first, then widen:

```bash
docker compose exec app php artisan test tests/Feature/<File>.php   # the file you touched
docker compose exec app php artisan test --filter=<method>          # a single method
docker compose exec app php artisan test                            # full suite
```

## 5. Fix failures and loop

- Read each failure. Decide whether the **test** or the **code** is wrong, fix it, re-run.
- Repeat until the targeted file Passes, then run the full suite and repeat until
  **all tests Pass**.
- Run `docker compose exec app ./vendor/bin/pint` on any files you touched.
