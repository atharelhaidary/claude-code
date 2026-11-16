# Maintenance API (Laravel 13, PHP 8.3)

Training API: dispatchers take customer requests, assign technicians, technicians file reports.
Auth: Sanctum bearer tokens. All routes under `/api/v1` in `routes/api.php`.

## Commands
- Setup: `composer install && cp .env.example .env && php artisan key:generate`
- DB: `touch database/database.sqlite && php artisan migrate --seed`
- Test all: `php artisan test` (some tests fail ON PURPOSE, see docs/bugs.md)
- Test one: `php artisan test --filter=ClassOrMethodName`
- Format: `vendor/bin/pint --dirty` (run before finishing any PHP change)
- Serve: `php artisan serve`

## Domain (verified)
- Model `MaintenanceRequest`, route param `{maintenanceRequest}`, policy `MaintenanceRequestPolicy`
  (abilities: create, assign, updateStatus, report). Also `Customer`, `RequestReport`, `User`.
- Roles are strings on `User`: use `User::ROLE_*`, `isOffice()` (admin/dispatcher), `isTechnician()`.
- Statuses: `new, assigned, in_progress, done, cancelled` (plain strings, `MaintenanceRequest::STATUSES`). No enum.
- Priorities: `low, medium, high` (`MaintenanceRequest::PRIORITIES`).

## Layout
- `app/Http/Controllers/Api/V1/` thin controllers: call an Action, return a Resource
- `app/Actions/*Action.php` business logic, method `execute()`
- `app/Http/Requests/` flat folder, one Form Request per endpoint
- `app/Http/Resources/` JSON shapes. `app/Policies/` who may do what
- `tests/Feature/` PHPUnit (NOT Pest)

## Rules
- Validation and authorization: use the `form-request` skill before creating or editing any Form Request.
- `authorize()` calls a Policy. `return true;` only for public endpoints (login), with a comment.
- Controllers type-hint the Form Request and use `$request->validated()`, never `$request->all()`.
- State rules (closed request, assigned technician) go in the Request `after()` AND in the Action.
- User-facing messages use `__()` with identical keys in `lang/ar` and `lang/en`. No hardcoded English strings.
- Input dates: local `Y-m-d H:i` (Asia/Riyadh), stored UTC. Output dates: ISO 8601 via the Resource.
- Every validated field must be persisted: check `$fillable` and the migration (bug B1).
- Endpoints without a Form Request (e.g. `show`) still need a Policy check. Base Controller has no `authorize()`: use `Gate::authorize()` (bug B5).
- Assignment checks must be race-safe: transaction + `lockForUpdate()` in the Action (bug B7).
- Every behaviour change or bug fix gets a feature test, including 403 and 422 cases.

## Never
- Never guess class, model, route-param or column names. Read `app/Models`, `routes/api.php`, `database/migrations` first.
- Never read or print `.env`; use `.env.example`.
- Never edit `composer.lock`, `vendor/`, or existing migrations (add a new migration).
- Never edit `docs/bugs.md`, and never change a failing test to match the bug.
- Never open PRs against the upstream training repo; work on the fork.
- Never add a package without asking.

## Workflow
1. Read the relevant code and the `docs/bugs.md` entry.
2. Write or update the failing test first.
3. Fix, run `php artisan test --filter=...`, then `vendor/bin/pint --dirty`.
4. Report what changed and which tests still fail.

## Known gaps (docs/bugs.md B1-B8, causes seen in code)
- B1: `priority` validated but not in `$fillable`. B5: `show` has no Policy check; Policy has no `view`.
- B2/B6: `index` uses `get()` (no pagination) and Resource lazy-loads customer/technician (N+1).
- B8: `store` converts local to UTC, Resource prints `scheduled_at` in UTC (3h off). B7: assignment check not locked.
- B3/B4: look handled in the code now (ISO UTC in Resource; Request rejects done/cancelled). Reproduce before touching.
- Status endpoint has no transition check (`done` can go back to `in_progress`).
- No `lang/` folder yet: run `php artisan lang:publish`, add `ar/` and `en/requests.php`.
