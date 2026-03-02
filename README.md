# Agilify

Agile project management for modern teams. Plan sprints, track issues, manage Kanban boards and collaborate in real time.

Built with **Laravel 12** (REST API) + **Vue.js** (SPA frontend), served by **Nginx + PHP-FPM** in a single Docker container.

---

## Table of Contents

- [Requirements](#requirements)
- [Local Development](#local-development)
- [Docker](#docker)
- [Environment Variables](#environment-variables)
- [Project Architecture](#project-architecture)
- [Domain Model](#domain-model)
- [Quality Gates](#quality-gates)
- [Tests](#tests)
- [CI/CD](#cicd)
- [CLI Utilities](#cli-utilities)

---

## Requirements

| Tool       | Version   |
| ---------- | --------- |
| PHP        | `^8.3`    |
| Composer   | `^2`      |
| Node.js    | `>=20`    |
| MySQL      | `8+`      |
| Docker     | `>=24`    |

---

## Local Development

### Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Configure your database in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=yourdb
DB_USERNAME=username
DB_PASSWORD=password
```

Start MySQL via Docker, run migrations, then start the dev servers:

```bash
docker compose up -d        # MySQL only
php artisan migrate
php artisan serve           # Laravel API  →  http://localhost:8000
npm run dev                 # Vite SPA     →  http://localhost:5173
```

---

## Docker

### Pull and run from Docker Hub

```bash
docker pull aminefaw89/agilify:latest

docker run -d \
  --name agilify-app \
  -v $(pwd)/.env:/var/www/html/.env \
  -p 8000:8000 \
  aminefaw89/agilify:latest
```

The container exposes ports **80** and **8000** (Nginx).

### Build locally

```bash
docker build -t agilify:local .
docker run -d --name agilify-app -v $(pwd)/.env:/var/www/html/.env -p 8000:8000 agilify:local
```

### What the container does on startup

The entrypoint (`docker/entrypoint.sh`) automatically:

1. Copies `.env.example` → `.env` if no `.env` is mounted
2. Generates `APP_KEY` if missing
3. Creates all required storage directories
4. Runs `config:cache`, `route:cache`, `view:cache`, `event:cache`
5. Runs `php artisan migrate --force`
6. Starts **Nginx + PHP-FPM + Queue worker** via Supervisor

### HTTPS / Reverse proxy

When running behind a reverse proxy (Nginx, Traefik, Cloudflare…) that handles SSL termination, set in your `.env`:

```env
APP_ENV=production
APP_URL=https://your-domain.com
```

Laravel is already configured to trust `X-Forwarded-Proto` headers and force HTTPS scheme in production, preventing mixed-content issues.

---

## Environment Variables

| Variable            | Description                                 | Example                        |
| ------------------- | ------------------------------------------- | ------------------------------ |
| `APP_KEY`           | Laravel encryption key                      | generated automatically        |
| `APP_ENV`           | Environment (`local` / `production`)        | `production`                   |
| `APP_URL`           | Public URL of the app                       | `https://app.agilify.com`      |
| `DB_HOST`           | MySQL host                                  | `agilify-mysql`                |
| `DB_PORT`           | MySQL port                                  | `3306`                         |
| `DB_DATABASE`       | Database name                               | `agilify`                      |
| `DB_USERNAME`       | Database user                               | `agilify`                      |
| `DB_PASSWORD`       | Database password                           | —                              |
| `MAIL_MAILER`       | Mail driver                                 | `smtp`                         |
| `MAIL_HOST`         | SMTP host                                   | `smtp.mailtrap.io`             |
| `MAIL_PORT`         | SMTP port                                   | `587`                          |
| `MAIL_USERNAME`     | SMTP username                               | —                              |
| `MAIL_PASSWORD`     | SMTP password                               | —                              |
| `MAIL_FROM_ADDRESS` | Default sender address                      | `no-reply@agilify.com`         |

---

## Project Architecture

```
app/
├── Actions/            # Atomic create / update / delete operations
├── Http/
│   ├── Controllers/    # API endpoints and HTTP orchestration
│   ├── Middleware/     # Auth, admin guard, audit log interceptor
│   └── Requests/       # Input validation (centralized rules)
├── Models/             # Eloquent models (UUIDs, relationships)
├── Policies/           # Fine-grained authorization per entity
└── Services/           # Application business logic

resources/
├── js/                 # Vue.js SPA (Vite)
└── views/              # Blade views (email templates, auth pages)

routes/
├── api.php             # REST API routes
└── web.php             # Web routes (email verify, password reset, sitemap…)

docker/
├── nginx.conf          # Nginx configuration
├── supervisord.conf    # Supervisor (Nginx + PHP-FPM + Queue worker)
├── php.ini             # PHP production settings
└── entrypoint.sh       # Container boot script
```

---

## Domain Model

| Entity                | Notes                                          |
| --------------------- | ---------------------------------------------- |
| `users`               | UUID PK, soft deletion                         |
| `user_sessions`       | Custom session management                      |
| `admins`              | Separate admin accounts                        |
| `organizations`       | Top-level workspace entity                     |
| `organization_members`| Composite key, role-based                     |
| `invitations`         | Email-based org invitations                    |
| `projects`            | Belong to organizations                        |
| `tickets`             | Issues/tasks, belong to projects               |
| `comments`            | Threaded comments on tickets                   |
| `labels`              | Project-scoped labels                          |
| `ticket_labels`       | Composite key                                  |
| `audit_logs`          | Immutable action trail                         |

- **UUID** identifiers on all primary domain entities
- **Soft deletion** via `is_deleted` + `deleted_at` on relevant tables
- **Composite keys** on `organization_members` and `ticket_labels`

---

## Quality Gates

```bash
composer lint            # Laravel Pint – check mode
composer format          # Laravel Pint – auto-fix
composer prettier:check  # Prettier – check Markdown / YAML / JSON
composer prettier:write  # Prettier – auto-fix
npm run lint:front       # ESLint – SPA frontend
```

---

## Tests

```bash
composer test        # Laravel feature tests (SQLite in-memory)
npm run test:front   # Vitest unit tests
```

---

## CI/CD

Workflow: `.github/workflows/ci.yml`

| Trigger                  | Job       | Steps                                                        |
| ------------------------ | --------- | ------------------------------------------------------------ |
| `push` / `pull_request`  | `quality` | Pint lint · Prettier check · ESLint · Vitest · Laravel tests |
| `push` on `main`         | `build`   | Build Docker image · Push to `aminefaw89/agilify` on Docker Hub |

Required GitHub secrets: `DOCKERHUB_USERNAME`, `DOCKERHUB_TOKEN`.

---

## CLI Utilities

Create or activate an admin user:

```bash
php artisan app:create-admin
```
