# Maintenance API — Entity Relationship Diagram

> Scope: the schema currently present in `database/migrations/`, plus the required proposed `request_status_histories` entity. The history table is a design addition for this exercise; it does **not** exist in the uploaded migrations yet.

## ERD

```mermaid
erDiagram
    USERS ||--o{ MAINTENANCE_REQUESTS : "assigned technician (optional)"
    CUSTOMERS ||--o{ MAINTENANCE_REQUESTS : owns
    MAINTENANCE_REQUESTS ||--o| REQUEST_REPORTS : has
    USERS ||--o{ REQUEST_REPORTS : writes
    MAINTENANCE_REQUESTS ||--o{ REQUEST_STATUS_HISTORIES : records
    USERS o|--o{ REQUEST_STATUS_HISTORIES : "changed by (optional after deletion)"

    USERS {
        bigint id PK
        string name
        string email UK
        string password
        enum role "admin|dispatcher|technician"
        string phone "nullable"
        timestamp created_at
        timestamp updated_at
    }
    CUSTOMERS {
        bigint id PK
        string name
        string phone
        string address
        timestamp created_at
        timestamp updated_at
    }
    MAINTENANCE_REQUESTS {
        bigint id PK
        bigint customer_id FK
        bigint technician_id FK "nullable"
        string title
        text description
        enum status "new|assigned|in_progress|done|cancelled"
        enum priority "low|medium|high; nullable"
        datetime scheduled_at
        datetime completed_at "nullable"
        timestamp created_at
        timestamp updated_at
    }
    REQUEST_REPORTS {
        bigint id PK
        bigint maintenance_request_id FK_UK
        bigint technician_id FK
        text notes
        text parts_used "nullable"
        timestamp created_at
        timestamp updated_at
    }
    REQUEST_STATUS_HISTORIES {
        bigint id PK
        bigint maintenance_request_id FK
        bigint changed_by FK "nullable"
        string old_status "nullable for initial state"
        string new_status
        text reason "nullable"
        timestamp created_at
    }
```

## Entity and relationship notes

| Entity | Purpose | Important constraints / relationships |
|---|---|---|
| `users` | Admin, dispatcher, and technician accounts; Sanctum token owners | `email` is unique. Role is one of `admin`, `dispatcher`, `technician`. |
| `customers` | Customer contact and address information | One customer can own many maintenance requests. `phone` has a non-unique index. |
| `maintenance_requests` | Work requested by a customer and optionally assigned to a technician | Required customer; optional technician; one of five statuses; optional priority; schedule and completion timestamps. |
| `request_reports` | Technician's completion/work report | One report per request (`maintenance_request_id` unique); each report references its authoring technician. |
| `request_status_histories` | Append-only audit trail of every status transition | Many history rows per request. `changed_by` is nullable to preserve the audit record if the user account is deleted. This is the required proposed entity. |

## Foreign keys and ON DELETE behavior

| FK | Current / proposed database behavior | Rationale |
|---|---|---|
| `maintenance_requests.customer_id → customers.id` | **Current:** `ON DELETE CASCADE` | Existing migration deletes a customer's requests with the customer. This is destructive; see justified adjustment #1 below. |
| `maintenance_requests.technician_id → users.id` | **Current:** `ON DELETE SET NULL` | A deleted technician should not delete the request; the request becomes unassigned. |
| `request_reports.maintenance_request_id → maintenance_requests.id` | **Current:** `ON DELETE CASCADE` | A report cannot exist without its parent request; the unique FK also enforces at most one report per request. |
| `request_reports.technician_id → users.id` | **Current:** default restrictive/no-action behavior (no explicit delete action in migration) | Prevents deleting the reporting technician while retaining a report that references them. Consider nullable author + `SET NULL` if user deletion must be supported. |
| `request_status_histories.maintenance_request_id → maintenance_requests.id` | **Proposed:** `ON DELETE CASCADE` | History belongs to the request aggregate and is removed with it. If legal/audit retention is required, requests must instead be soft-deleted or archived. |
| `request_status_histories.changed_by → users.id` | **Proposed:** nullable + `ON DELETE SET NULL` | Preserve the history row when an account is removed; the actor identity becomes unavailable, but the event remains. |

## Indexes and uniqueness

| Table | Index / constraint | Source / reason |
|---|---|---|
| `users` | Unique index on `email` | Existing migration; login lookup and duplicate prevention. |
| `customers` | Index on `phone` | Existing migration; customer phone lookup. Not unique because the schema does not establish phone uniqueness. |
| `maintenance_requests` | Index on `customer_id` (created by FK), `technician_id`, `status`, `scheduled_at` | Existing migration / FK. Supports customer joins, technician workload, status filters, and scheduling. |
| `request_reports` | Unique index on `maintenance_request_id`; FK index on `technician_id` | Existing migration. Enforces one report per request. |
| `request_status_histories` | `(maintenance_request_id, created_at)`; index on `changed_by`; FK indexes | Proposed. Efficient request timeline and actor audit queries. |

## Status model and edge cases

Allowed status values in the current model are `new`, `assigned`, `in_progress`, `done`, and `cancelled`. The intended transition policy documented for this exercise is:

| Current | Allowed next status |
|---|---|
| `new` | `cancelled`, `assigned` |
| `assigned` | `in_progress`, `cancelled` |
| `in_progress` | `done`, `cancelled` |
| `done` | none (terminal) |
| `cancelled` | none (terminal) |

`new → assigned` occurs through technician assignment; `assigned → in_progress`, `in_progress → done`, and cancellation occur through the status endpoint. This is a proposed business rule, not fully enforced by the current `UpdateRequestStatusRequest` (which currently validates only the target status enum).

Edge cases represented in the model:

- An unassigned request has `technician_id = NULL`; assignment sets the technician and status to `assigned`.
- A request may have no report. The unique FK allows at most one report, not multiple reports.
- A request may have `completed_at = NULL`; it is set when status becomes `done`. Non-`done` requests should have it cleared/null, although the current controller clears it on any non-`done` update.
- The first history row has `old_status = NULL` and `new_status = 'new'` when request creation is audited. Subsequent rows require both old and new statuses.
- A history actor may become null after user deletion; `reason` is optional because not every transition necessarily has a human-entered explanation.
- Deleting a customer currently cascades to requests and therefore their reports/history. This may be unacceptable for audit retention; see adjustment #1.
- Deleting an assigned technician sets `technician_id` to null. The request remains, but its previous assignment can only be reconstructed from history once the proposed history table is implemented.
- Concurrent assignments to the same technician/time are a known race (B7); application-level `exists()` alone is not a database concurrency guarantee.

## تعديلات على اقتراح الأداة

1. **Customer deletion should not erase maintenance/audit records.** The existing migration uses `cascadeOnDelete()` from `maintenance_requests.customer_id` to `customers`. For a production maintenance system, I would change this to `restrictOnDelete()` (or soft-delete customers) so a customer cannot accidentally erase requests, reports, and the status trail. This is a deliberate design recommendation; it is **not** the current migration behavior.
2. **The status-history actor must be nullable with `SET NULL`, not cascade.** The history is an audit record. Deleting a user must not erase status events; retain each event and null only `changed_by`. This is part of the proposed `request_status_histories` design, not an existing migration.
3. **Keep `request_reports.maintenance_request_id` unique.** A generic one-to-many suggestion would allow multiple reports, but the current migration explicitly enforces one report per request and the API exposes a single `report` relation. The ERD therefore preserves one-to-zero-or-one.

## Implementation delta

Before treating this ERD as the deployed schema, add a migration for `request_status_histories`, its model/relation, and transactional writes for creation, assignment, and status changes. Decide whether customer deletion changes from the current cascade behavior to restrict/soft delete in a separate migration. Existing schema facts above were read from the repository migrations; proposed changes are explicitly labeled.
