---
name: form-request
description: Use when creating, editing, or reviewing a Laravel Form Request, or when adding validation, authorization, or validation error messages to an endpoint in this project (requests, customers, assignment, status, report). Also use when an endpoint must reject input because of the current state of a MaintenanceRequest (e.g. done or cancelled), or must normalize input (Arabic digits, trimming, dates). Covers naming, authorize(), Arabic and English messages, and prepareForValidation().
---

# Form Request rules (maintenance-api)

Every endpoint that writes data goes through a Form Request in `app/Http/Requests/` (flat folder, no subfolders).
No `$request->validate()` and no `Validator::make()` in controllers.

## Project facts (verified against the code)
- Model: `App\Models\MaintenanceRequest`. Route param: `{maintenanceRequest}`. Statuses are plain strings
  (`MaintenanceRequest::STATUSES`), priorities `MaintenanceRequest::PRIORITIES`. There is no enum.
- Roles are strings on `User` with constants and helpers: `User::ROLE_TECHNICIAN`, `isOffice()` (admin or dispatcher), `isTechnician()`.
- Policy `MaintenanceRequestPolicy` abilities: `create`, `assign`, `updateStatus`, `report`. `CustomerPolicy`: `viewAny`, `create`.
- Business logic: `app/Actions/*Action.php` with an `execute()` method.
- Existing Requests: `AssignTechnicianRequest`, `StoreMaintenanceRequestRequest`, `StoreCustomerRequest`,
  `StoreRequestReportRequest`, `UpdateRequestStatusRequest`, `LoginRequest`.
- Base `App\Http\Controllers\Controller` is empty (Laravel 11+): there is no `$this->authorize()`. Use `Gate::authorize('ability', $model)`.
- `lang/` does not exist yet (create with `php artisan lang:publish`, then add `ar/requests.php` and `en/requests.php`). No locale middleware seen; ask before adding one.
- Local-to-UTC conversion of `scheduled_at` happens in `MaintenanceRequestController@store` (Carbon + `config('app.local_timezone')`).
Re-read the code if anything above may have changed. Never guess names that are not listed here.

## 1. Naming
- `{Action}{Entity}Request` in `app/Http/Requests/`. Follow the existing names above
  (`StoreMaintenanceRequestRequest` is the real, intentional name).
- Actions: `Store`, `Update`, `Assign`, `Index`. One Request per endpoint, never shared.

## 2. authorize()
- Delegate to a Policy: `$this->user()->can('assign', $this->route('maintenanceRequest'))`.
  Add a Policy method if none fits; do not inline role checks in the Request.
- `return true;` is allowed ONLY for public endpoints (`LoginRequest`), with a one-line comment saying why.
- 403 = user not allowed (authorize). 422 = invalid data or record state (validation).
- Use the route-bound model, never query it again.

## 3. rules() vs after()
- `rules()`: shape, type, `in`, `exists`, `date_format:Y-m-d H:i`.
- `after()`: anything depending on the record's current state (status, assigned technician).
  Existing code uses `withValidator()`; when you touch such a Request, move it to `after()`.
- Repeat the same state guard inside the Action (defense in depth). Action errors use the same lang keys.
- Technician ids: `Rule::exists('users', 'id')->where('role', User::ROLE_TECHNICIAN)`.

## 4. Messages: Arabic and English
- No hardcoded strings in Requests or Actions. Use `__()`; keys in `lang/ar/requests.php` and `lang/en/requests.php`, identical keys. If `lang/` is missing run `php artisan lang:publish`.
- Key pattern: `requests.{request_snake}.{field}.{rule}`, or `requests.errors.{reason}` for non-field errors.
- Implement both `messages()` and `attributes()`.
- Message states the problem and what to do. No class names or SQL words.

## 5. prepareForValidation()
Cleaning only: no queries, no business logic.
- Convert Arabic-Indic digits to Latin (ids, phones, dates).
- `trim` strings; empty string to `null`.
- Dates arrive as local `Y-m-d H:i` (Asia/Riyadh). Validate the format; do NOT convert to UTC here (the controller does it).

## 6. Controller side
`$request->validated()` only, return a Resource. Type-hint the Request in the controller method, otherwise none of the above runs. Tests: success, 403, 422.

## Real example: B4, assigning a technician to a closed request
Bug: a dispatcher assigned a technician to a request closed the day before.
Route: `POST /api/v1/requests/{maintenanceRequest}/assign` (`AssignmentController@store`).

The existing `AssignTechnicianRequest` already rejects `done`/`cancelled`, and `AssignmentController@store` already type-hints it.
What is left: a hardcoded English message, `exists:users,id` accepting any user, and no guard in `AssignTechnicianAction`
(any other caller bypasses the Request). Run the B4 repro before changing anything. Target version:

```php
namespace App\Http\Requests;

use App\Models\MaintenanceRequest;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignTechnicianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('assign', $this->route('maintenanceRequest'));
    }

    protected function prepareForValidation(): void
    {
        $id = $this->input('technician_id');

        if (is_string($id)) {
            $this->merge(['technician_id' => strtr(trim($id), '٠١٢٣٤٥٦٧٨٩', '0123456789')]);
        }
    }

    public function rules(): array
    {
        return [
            'technician_id' => [
                'required', 'integer',
                Rule::exists('users', 'id')->where('role', User::ROLE_TECHNICIAN),
            ],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            /** @var MaintenanceRequest|null $maintenanceRequest */
            $maintenanceRequest = $this->route('maintenanceRequest');

            if ($maintenanceRequest && in_array($maintenanceRequest->status, ['done', 'cancelled'], true)) {
                $validator->errors()->add('maintenanceRequest', __('requests.errors.closed'));
            }
        }];
    }

    public function messages(): array
    {
        return [
            'technician_id.required' => __('requests.assign.technician_id.required'),
            'technician_id.exists'   => __('requests.assign.technician_id.exists'),
        ];
    }

    public function attributes(): array
    {
        return ['technician_id' => __('requests.attributes.technician_id')];
    }
}
```

`AssignTechnicianAction::execute()` starts with the same guard:
`if (in_array($request->status, ['done', 'cancelled'], true)) { throw ValidationException::withMessages(['maintenanceRequest' => __('requests.errors.closed')]); }`
and its two existing messages move to `requests.errors.not_a_technician` and `requests.errors.technician_busy`.

`lang/ar/requests.php`: `'errors' => ['closed' => 'الطلب مقفول ولا يمكن تعيين فني عليه.', 'not_a_technician' => 'المستخدم المختار ليس فنيًا.', 'technician_busy' => 'الفني لديه زيارة أخرى في نفس الموعد.']`
`lang/en/requests.php`: `'errors' => ['closed' => 'This request is closed; a technician cannot be assigned.', 'not_a_technician' => 'The selected user is not a technician.', 'technician_busy' => 'The technician already has a visit at this time.']`

Test: for `done` and `cancelled`, a dispatcher calling the assign endpoint gets 422 with error key `maintenanceRequest`; a technician gets 403.

## Not a Form Request problem (verified against the code, docs/bugs.md)
A Form Request only sees input. Do not try to fix these there:
- B1 priority lost: `priority` is validated and passed to `MaintenanceRequest::create($data)`, but is missing from `$fillable`. Rule: every validated field must be persisted.
- B3 `completed_at`: the model casts it and the Resource already emits `Y-m-d\TH:i:s\Z` (UTC). Reproduce first; if mobile still breaks, compare with `scheduled_at`, which is `Y-m-d H:i` (inconsistent). Prefer `toIso8601String()` for ISO 8601.
- B8 times three hours off: `store` converts local to UTC, but the Resource prints `scheduled_at` in UTC. Convert back with `->setTimezone(config('app.local_timezone'))`.
- B5 other technician's request: `show` has no authorization and the Policy has no `view`. Add `view` (office, or `technician_id === $user->id`) and call `Gate::authorize('view', $maintenanceRequest)`.
- B2/B6 slow list: `index` uses `->get()` with no pagination, and the Resource lazy-loads `customer` and `technician` (N+1). Use `->with(['customer', 'technician'])->paginate()`.
- B7 double assignment: the Action's `exists()` check is not concurrency-safe. Wrap in `DB::transaction` with `lockForUpdate()` (SQLite ignores row locks; a MySQL test or a unique constraint is needed).
- Status transitions: `RequestStatusController` lets `done` go back to `in_progress` and clears `completed_at`. Add a transition check in `UpdateRequestStatusRequest::after()` and the controller/Action.

## Checklist
- [ ] real name + flat folder, one request per endpoint
- [ ] authorize() uses a Policy (`return true` only for public endpoints, commented)
- [ ] state checks in after() and in the Action
- [ ] ar + en keys identical, messages() and attributes(), Actions use __() too
- [ ] prepareForValidation() cleaning only
- [ ] every validated field is persisted (`$fillable` + migration)
- [ ] controller type-hints the Request and uses validated(); tests cover success/403/422
