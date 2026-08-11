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

## Getting Started (Local Dev — Laravel Sail)

Requires Docker. Prerequisites: PHP 8.3+, Composer, Docker/Sail.

```bash
# Install dependencies
composer install && npm install

# Configure environment
cp .env.example .env        # set DB, REDIS, and S3/MinIO credentials

# Start the Sail stack (or: ./vendor/bin/sail up -d)
sail up

# Run migrations + seed
sail artisan migrate:fresh --seed

# Build frontend assets
npm run build               # or: npm run dev
```

### Queued jobs (IMPORTANT)

PDF generation, notifications, and image processing run on the **Redis queue**. You **must**
have a queue worker running, otherwise queued jobs will never execute (e.g. the "Generated
PDFs" list stays empty):

```bash
sail artisan queue:work --tries=3 --timeout=90
```

The local dev `compose.yaml` includes a dedicated `worker` service that starts automatically
with `sail up`.

> **NOTE:** The current `compose.yaml` is a **temporary, dev-only configuration** tuned for
> local testing (Laravel Sail + MinIO). The MinIO container attaches to the `laravel.test`
> network namespace, and the worker reaches it via the `http://laravel.test:9000` endpoint.
> Production will use a real S3-compatible provider and a properly isolated worker service —
> the compose file is *not* a blueprint for production infrastructure.

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