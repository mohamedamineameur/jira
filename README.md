# Laravel Project API

Laravel backend with a structured architecture based on `Models`, `Policies`, `Services`, `Actions`, custom middleware, and feature tests.

## Getting Started

### Requirements

- PHP `^8.2`
- Composer
- Node.js `>=20` (for Prettier)
- SQLite

### Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
```

### Run Locally

```bash
php artisan serve
```

## Project Architecture

The project follows a clear separation of responsibilities:

- `app/Models`: Eloquent models (UUIDs, relationships, domain soft deletion).
- `app/Http/Controllers`: API endpoints and HTTP orchestration.
- `app/Http/Requests`: input validation (centralized rules).
- `app/Services`: application business logic.
- `app/Actions`: atomic create/update/delete operations.
- `app/Policies`: fine-grained authorization per entity.
- `app/Http/Middleware`: API auth, admin guard, audit log interceptor.
- `database/migrations`: full SQL schema.
- `tests/Feature`: end-to-end API tests (in-memory SQLite).
- `routes/api.php`: REST routes and custom routes (`/me`, `/login`, `/logout`, etc.).

## Domain Model

The main entities exposed through the API are:

- `users`
- `user_sessions`
- `admins`
- `organizations`
- `organization_members`
- `invitations`
- `projects`
- `tickets`
- `comments`
- `labels`
- `ticket_labels`
- `audit_logs`

Important notes:

- `UUID` identifiers are used for primary domain entities.
- Domain soft deletion (`is_deleted` + `deleted_at`) is used on relevant tables.
- `organization_members` and `ticket_labels` use composite keys.

## Quality Gates

- `composer lint`: Laravel Pint check mode.
- `composer format`: Laravel Pint auto-format.
- `composer prettier:check`: Prettier check (Markdown/YAML/JSON).
- `composer prettier:write`: Prettier auto-format.
- `npm run lint:front`: ESLint for SPA frontend code.

## Tests

```bash
composer test
npm run test:front
```

## Continuous Integration

A workflow is configured in `.github/workflows/ci.yml`.

On every `push` and `pull_request`, it runs:

- lint with Laravel Pint;
- Prettier check;
- frontend lint with ESLint;
- frontend unit tests with Vitest;
- Laravel tests.

## CLI Utilities

Create/activate an admin user:

```bash
php artisan app:create-admin
```
