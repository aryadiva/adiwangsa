# Construction Operations & Back-Office Management Dashboard

A centralized, role-based internal tool that migrates manual, error-prone construction
consultancy operations (Excel spreadsheets, WhatsApp threads, fragmented site photos)
into a single web dashboard built with the **TALL stack**.

## Overview

- **Project Management** — track projects, sites, weighted milestones, and sub-jobs with
  their own start dates, quantities, and progress weighting (must tally to 100%).
- **Worker & Resource Directory** — worker profiles (bank info, active/deactivation dates),
  daily labor allocations, and a separate HRD-owned attendance record feeding payroll.
- **Daily Site Reporting** — up to 3 shift-based digital logs per site per day, submitted
  by Site Engineers with auto-saving drafts, a review/approval queue, worker allocations,
  and a live-captured before/after photo pair with description.
- **Automated Target Tracking & Delay Cascade** — daily targets accumulate any missed
  deficit into the next day; sustained delays automatically shift downstream milestone
  and project dates, with a 🔴/🟡/🟢 admin mitigation workflow.
- **Paperwork Engine** — dynamic, queued PDF generation for standard industry documents
  (Daily Progress Reports, Weekly Site Executive Digest, Worker Attendance & Labor Roster,
  and a separate Payroll/HRD document).
- **Automated Email Reports** — published daily reports are emailed directly to clients
  (configurable Sender/Receiver/CC) instead of a client web portal.
- **Bi-Weekly Payroll** — automatic payroll accumulation every 14 days, computed strictly
  from HRD-recorded attendance, including overtime.

This is a **single-tenant internal tool** (one consultancy, many clients/projects), not a
multi-tenant SaaS.

> **v0.2.0 (in progress):** based on client feedback after v0.1.0 launch, this revision adds weighted
> milestone/sub-job tracking, shift-based daily reports with automatic target/deficit tracking, an
> automated delay-cascade + mitigation workflow, a new HRD role for live-camera attendance capture,
> bi-weekly payroll, and **replaces the client web portal with emailed PDF reports**. See `prd.md`
> (v3) and `TASKS.md` Phase 8 for full detail.

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
      ├── Admin / Consultant ────► [Full Read/Write, Approval Queue, PDF Exports, Mitigation Plans, System Config]
      ├── Site Engineer ─────────► [Scoped CRUD: Assigned Sites, Shift Daily Logs (Draft/Submit), Live Camera Only]
      ├── HRD ────────────────────► [Scoped Write: Worker Attendance Only, Live Camera Only]  (v0.2.0)
      └── Client (record only) ──► [No panel access — receives emailed PDF reports]           (v0.2.0)
```

- **Admin / Consultant** — full CRUD, exclusive publish/revision authority, triggers queued
  PDF generation, manages users/assignments, views audit trail, configures milestone/sub-job
  weights and delay thresholds, submits mitigation plans, and configures the emailed PDF
  report cycle. Only role allowed to upload photos from device storage (unrestricted).
- **Site Engineer** — create/update up to 3 shift-based `daily_reports` per site per day for
  explicitly assigned project sites; manage drafts and submit for approval; captures a live
  before/after photo pair + description per shift (camera-only, no gallery); cannot publish.
- **HRD** *(v0.2.0)* — scoped exclusively to worker attendance; records daily attendance with
  a live-captured photo (camera-only, no gallery); feeds the bi-weekly payroll calculation.
- **Client** *(v0.2.0 — record only)* — no login or web access; retained as the authoritative
  contact/company record. Receives `published` daily reports as emailed PDFs with
  Admin-configured Sender/Receiver/CC (dummy/Mailpit transport for now).

## Key Features

- **Daily Report state machine:** `draft → need_approval → published`, with
  `revision_requested` as an editable branch, now per-shift (up to 3/site/day). All
  transitions route through dedicated model actions; resubmission snapshots history to
  `daily_report_revisions`; `published` now triggers an emailed PDF instead of a portal
  notification.
- **Weighted milestones & sub-jobs** *(v0.2.0)* — sub-jobs nest under milestones with their
  own start date, duration, quantity, and weight (%); weights must sum to 100% at every
  level, enforced on save.
- **Automated target tracking & delay cascade** *(v0.2.0)* — daily targets auto-adjust for
  carried-forward deficits; sustained delays automatically shift downstream milestone/project
  dates and open a 🔴/🟡/🟢 mitigation workflow.
- **Draft auto-save** via Livewire polling with retry-on-failure for flaky mobile connectivity.
- **Queued PDF generation** (`GeneratePdfJob`) — always background, never synchronous;
  documents are stored on the `pdfs` disk and recorded in `generated_documents`; worker
  allocation is a separate document from the Daily Progress PDF.
- **Emailed client reports** *(v0.2.0)* — replaces the client web portal; configurable
  Sender/Receiver/CC per send, dummy/Mailpit transport pending a production SMTP provider.
- **Live-capture-only photos** *(v0.2.0)* — Site Engineer (before/after + description) and
  HRD (attendance) must use the in-app camera; gallery uploads are disabled for both roles
  to protect evidence integrity. Admin remains unrestricted.
- **Bi-weekly payroll** *(v0.2.0)* — automated 14-day payroll runs computed from HRD-owned
  `worker_attendance`, including overtime derived from each worker's daily rate.
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

- **RBAC enforcement** — per-role allow/deny on `daily_reports`, `sites`, `projects`, and
  (v0.2.0) `worker_attendance`; HRD and Site Engineer cannot access each other's scoped data.
- **State machine transitions** — all legal transitions plus rejection of illegal ones,
  including (v0.2.0) the `sub_job_delay_events` 🔴/🟡/🟢 workflow.
- **PDF generation** — queued job DTO assertions with a faked queue.
- **Email delivery** *(v0.2.0)* — `Mail::fake()` assertions on recipients and attachment
  for the client PDF report send.
- **Weight validation, target accumulation, and payroll calculation** *(v0.2.0)* — see
  `prd.md` §8.4 for the full test matrix.

Run the full suite before every commit; do not commit red tests.

## Project Structure

- `app/Filament/Resources/` — one Filament Resource per model; nested relation managers
  for child data (now two levels deep for Milestones → Sub-Jobs).
- `app/Jobs/GeneratePdfJob.php` + `app/Services/PdfReportService.php` — queued PDF pipeline.
- `app/Jobs/SendClientReportEmailJob.php` *(v0.2.0)* — queued email delivery, dummy/Mailpit
  transport, dispatched on `published`.
- `app/DTOs/ReportDataDTO.php` — immutable, queue-safe data passed to Blade; carries a
  configurable `sections` array *(v0.2.0)* for future custom report content.
- `resources/views/pdf/` — PDF Blade layouts (never inline HTML in services/jobs), including
  the *(v0.2.0)* separate worker allocation/payroll document.
- `app/Enums/` — backed PHP enums for all status fields (no magic strings), including the
  *(v0.2.0)* `UserRole::Hrd` and sub-job delay color states.
- `app/Filament/Client/*` — **removed in v0.2.0**; the client Filament panel no longer exists.
- `docs/prd.md` — the full **v3 PRD** / source of truth for resources, policies, and schema.

## License

Proprietary internal tool — construction consultancy operations dashboard.