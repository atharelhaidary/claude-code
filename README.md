# Maintenance API — training repository

A small Laravel 13 API for a maintenance-requests company, used in the
[Qutell](https://qutell.com) training programme. Dispatchers take requests from
customers and assign them to technicians; technicians work them and file a
report.

It is a training repository: it has bugs on purpose, listed from the user's
side in [`docs/bugs.md`](docs/bugs.md). Please do not open pull requests
against this repository — work on your own fork.

## Stack

- PHP 8.3+, Laravel 13
- Laravel Sanctum (personal access tokens)
- SQLite out of the box; MySQL 8 works too
- PHPUnit feature tests

## Getting started

1. **Fork** this repository on GitHub (the *Fork* button, top right), then
   clone **your fork**:

   ```bash
   git clone https://github.com/<your-username>/maintenance-api-training.git maintenance-api
   cd maintenance-api
   ```

2. Install and configure:

   ```bash
   composer install
   cp .env.example .env
   php artisan key:generate
   ```

3. Database. SQLite needs nothing but the file:

   ```bash
   touch database/database.sqlite
   ```

   For MySQL instead, create a database and set `DB_CONNECTION=mysql` with
   `DB_HOST`, `DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD` in `.env`.

4. Migrate, seed, and run the tests:

   ```bash
   php artisan migrate --seed
   php artisan test
   ```

   Some tests fail. That is expected — it is part of the exercises.

5. Serve it:

   ```bash
   php artisan serve
   ```

## Seeded accounts

Every seeded account's password is `password`.

| Role | Email |
| --- | --- |
| Admin | `admin@example.com` |
| Dispatcher | `dispatcher@example.com` |
| Technician | `technician@example.com` |

Plus two more users per role, ten customers and thirty requests in mixed states.

## The API

All routes are under `/api/v1`. Everything except login needs
`Authorization: Bearer <token>`.

| Method | Path | Who |
| --- | --- | --- |
| `POST` | `/auth/login` | anyone |
| `GET` | `/requests` | all roles (a technician sees their own) |
| `POST` | `/requests` | admin, dispatcher |
| `GET` | `/requests/{id}` | all roles |
| `POST` | `/requests/{id}/assign` | admin, dispatcher |
| `PATCH` | `/requests/{id}/status` | admin, dispatcher, the assigned technician |
| `POST` | `/requests/{id}/report` | the assigned technician |
| `GET` | `/customers` | admin, dispatcher |
| `POST` | `/customers` | admin, dispatcher |

Get a token:

```bash
curl -s -X POST http://localhost:8000/api/v1/auth/login \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"email":"dispatcher@example.com","password":"password"}'
```

Statuses: `new`, `assigned`, `in_progress`, `done`, `cancelled`.
Priorities: `low`, `medium`, `high`.
Dates are sent in local time (`Y-m-d H:i`, Asia/Riyadh) and stored in UTC.

## Layout

```
app/Actions/            business actions (assignment)
app/Http/Controllers/   API controllers, app/Http/Controllers/Api/V1
app/Http/Requests/      validation and authorization per endpoint
app/Http/Resources/     JSON shapes
app/Models/             Eloquent models
app/Policies/           who may do what
database/               migrations, factories, seeders
docs/bugs.md            reported bugs
tests/Feature/          feature tests
```
