# Construction Operations & Back-Office Management Dashboard

A centralized, role-based internal tool that migrates manual, error-prone construction
consultancy operations (Excel spreadsheets, WhatsApp threads, fragmented site photos)
into a single web dashboard built with the **TALL stack**.

## Overview

- **Project Management** — track projects, site locations, milestones, and status indicators.
- **Worker & Resource Directory** — worker pools and daily labor allocations per site.
- **Daily Site Reporting** — digital logs submitted by Site Engineers with auto-saving
  drafts, a review/approval queue, worker allocations, and photo uploads.
- **Paperwork Engine** — dynamic, queued PDF generation for standard industry documents
  (Daily Progress Reports, Weekly Site Executive Digest, Worker Attendance & Labor Roster).
- **Client Visibility** — read-only portal for project owners / contracted clients to track
  site activity with no access to administrative controls.

This is a **single-tenant internal tool** (one consultancy, many clients/projects), not a
multi-tenant SaaS.

## Tech Stack

| Component              | Technology                                     |
| :--------------------- | :--------------------------------------------- |
| Framework              | Laravel 11.x / 12.x (PHP 8.3+)                 |
| Admin UI               | Filament PHP v3.x                              |
| Reactivity             | Laravel Livewire v3.x                          |
| Styling / Interactivity| Tailwind CSS + Alpine.js (Filament-bundled)    |
| Database               | PostgreSQL 15+ (native `jsonb`)                |
| PDF Generation         | Blade-to-PDF (PdfReportService + queued job)   |
| RBAC                   | Filament + Eloquent policies / scopes          |
| Queue                  | Laravel Queues (**Redis** driver)              |
| File storage           | S3-compatible (AWS S3 / Spaces / **MinIO** in dev) |
| Image processing       | Intervention Image                             |
| Audit logging          | spatie/laravel-activitylog                     |
| Notifications          | Laravel Notifications (mail + database)        |

## Roles (RBAC)

```
[System User]
      ├── Admin / Consultant ────► [Full Read/Write, Approval Queue, PDF Exports, System Config]
      ├── Site Engineer ─────────► [Scoped CRUD: Assigned Sites, Daily Logs (Draft/Submit)]
      └── Client User ───────────► [Scoped Read-Only: Assigned Projects, Published Reports]
```

- **Admin / Consultant** — full CRUD, exclusive publish/revision authority, triggers queued
  PDF generation, manages users/assignments, views audit trail.
- **Site Engineer** — create/update `daily_reports` for explicitly assigned project sites;
  manage drafts and submit for approval; uploads photos and worker data; cannot publish.
- **Client User** — strict read-only, only `published` reports, only their linked projects;
  can download generated PDFs; notified on publication.

## Key Features

- **Daily Report state machine:** `draft → need_approval → published`, with
  `revision_requested` as an editable branch. All transitions route through dedicated
  model actions; resubmission snapshots history to `daily_report_revisions`.
- **Draft auto-save** via Livewire polling with retry-on-failure for flaky mobile connectivity.
- **Queued PDF generation** (`GeneratePdfJob`) — always background, never synchronous;
  documents are stored on the `pdfs` disk and recorded in `generated_documents`.
- **Photo uploads** to S3-compatible storage with automatic thumbnail generation and
  photo-file reconciliation (dedupe + orphan prune).
- **Audit trail** and **notifications** across approval workflow events.

## Getting Started (clone → run)

Requires only **Docker** (Compose v2). No PHP, Composer, or Node on the host.

```bash
git clone <this-repo> && cd <repo>
docker compose up -d --build        # or: make up
```

On first start the web container self-bootstraps in the background:

1. Creates `.env` from `.env.example` and generates an `APP_KEY`.
2. Waits for PostgreSQL/Redis/MinIO, runs `migrate` + seeds demo data.
3. Creates the MinIO `construction-ops` bucket.
4. Starts the PHP server (and the queue `worker` service runs jobs).

Then open the app at <http://localhost/admin> and log in with a seeded admin account
(see the next section). A `Makefile` wraps the common commands: `make up`, `make down`,
`make logs`, `make fresh`, `make test`.

> **One-liner is a local demo**. Everything—composer/npm install, asset build, migrations,
> seeding—runs inside the image; your host stays clean (PHP/Composer/Node not required).

### Daily snapshot of the dev workflow

If you prefer developing against a live, editable bind-mount instead of the self-contained
image (e.g. hot-reload with Vite), run the classic Sail flow after `composer install`:

```bash
composer install && npm install     # host deps (dev only)
cp .env.example .env
docker compose up -d                 # or: ./vendor/bin/sail up -d
sail artisan migrate:fresh --seed
npm run build                        # or: npm run dev
```

### Queued jobs (IMPORTANT)

PDF generation, notifications, and image processing run on the **Redis queue**. The `worker`
service is part of the stack and starts automatically, so jobs are processed without any
extra step. To observe them live: `docker compose logs -f worker`.

> **NOTE:** `compose.yaml` is a **single, portable** file usable in both development and
> self-hosted production. Every service (app, worker, pgsql, redis, mailpit, minio) is a
> normal networked container that talks to peers over an internal bridge by DNS name — no
> service shares another's network namespace. Environment drives the deployment profile.
> Object storage uses a **single addressable host** — the AWS SDK signs presigned URLs
> against `AWS_ENDPOINT`, so it (and the browser) must both resolve the same host:
> * **Dev:** `AWS_ENDPOINT=http://<YOUR-LAN-IP>:9000` + `AWS_URL` set to the same LAN IP.
>   The LAN IP must be reachable from the app container AND from the browser — this works
>   because MinIO publishes port 9000 on `0.0.0.0`. No `/etc/hosts` entry is needed.
> * **Prod (real S3):** `AWS_ENDPOINT=` empty + `AWS_URL` to the real bucket URL.
> * **Prod (self-hosted MinIO behind Caddy):** point `AWS_ENDPOINT` and `AWS_URL` at the same
>   public host Caddy proxies to the MinIO container (e.g. `https://minio.example.com`).
>   Caddy proxies the app domain to `laravel.test`; the worker inherits the app's `.env`
>   (bind-mounted) and needs no endpoint overrides. Only MinIO must be browser-facing (photo
>   previews fetch signed object URLs directly) — DB/Redis/mail and PDF downloads stay internal.
> The worker inherits the app's `.env` (bind-mounted) and needs no endpoint overrides.

## Commands

```bash
sail artisan migrate:fresh --seed   # reset DB with seed data
sail artisan test                   # run the Pest test suite
sail artisan test --filter=DailyReportPolicyTest
./vendor/bin/pint                   # code style lint/format
./vendor/bin/phpstan analyse        # static analysis (if configured)
sail artisan photos:prune --dry-run # reconcile/prune orphan photo records
```

## Testing

Pest feature tests cover:

- **RBAC enforcement** — per-role allow/deny on `daily_reports`, `sites`, and `projects`;
  clients cannot fetch `draft`/`need_approval` reports by guessing UUIDs.
- **State machine transitions** — all legal transitions plus rejection of illegal ones.
- **PDF generation** — queued job DTO assertions with a faked queue.

Run the full suite before every commit; do not commit red tests.

## Project Structure

- `app/Filament/Resources/` — one Filament Resource per model; nested relation managers
  for child data.
- `app/Jobs/GeneratePdfJob.php` + `app/Services/PdfReportService.php` — queued PDF pipeline.
- `app/DTOs/ReportDataDTO.php` — immutable, queue-safe data passed to Blade.
- `resources/views/pdf/` — PDF Blade layouts (never inline HTML in services/jobs).
- `app/Enums/` — backed PHP enums for all status fields (no magic strings).
- `docs/prd.md` — the full v2 PRD / source of truth for resources, policies, and schema.

## License

Proprietary internal tool — construction consultancy operations dashboard.