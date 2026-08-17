# AGENTS-v2.md

Construction Operations & Back-Office Management Dashboard — Laravel 11/12 + Filament v3 (TALL stack), PostgreSQL 15+.
Full spec lives in `docs/prd-v2.md` (the **v2 PRD**, driving the v0.2.0 build). Read it before implementing any resource,
policy, or migration — this file is the fast-reference; the PRD is the source of truth when they conflict.

> **v0.2.0 in progress.** v0.1.0 (Phases 1–7) is complete and live. This revision layers in client feedback:
> sub-job/weighted milestone tracking, shift-based daily reports with an auto-target/deficit engine, an automated
> delay cascade with a mitigation workflow, removal of the client Filament portal in favor of emailed PDF reports,
> a new HRD role with live-camera-only attendance capture, and bi-weekly payroll. See `prd-v2.md` changelog and
> `TASKS-v2.md` Phase 8 for the full breakdown. Items below marked `v3` reflect this revision.

## Stack
- PHP 8.3+, Laravel 11/12, Filament v3, Livewire v3, Alpine.js, Tailwind (Filament-bundled)
- PostgreSQL 15+ (uses native `jsonb` — cast `meta_data` columns to `array` in models, never raw JSON strings)
- Queue driver: Redis. Storage: S3-compatible disk (`photos`, `pdfs`). Image processing: Intervention Image.
- Local dev: Laravel Sail (Docker). Tests: Pest.

## Commands
- Install: `composer install && npm install`
- Migrate (fresh + seed): `php artisan migrate:fresh --seed`
- Run tests: `php artisan test` (or `./vendor/bin/pest`) — run before every commit, not just at the end of a task
- Single test file: `php artisan test --filter=DailyReportPolicyTest`
- Lint/format: `./vendor/bin/pint`
- Static analysis (if configured): `./vendor/bin/phpstan analyse`
- Frontend build: `npm run build` / `npm run dev`
- Local server: `./vendor/bin/sail up`

## Conventions
- UUID primary keys on every table (see PRD §4). Use `HasUuids` trait, not auto-increment ints.
- **Soft deletes on everything except pivot tables** (`project_user`). Never hard-delete construction records.
- All `On Delete Set Null` FKs that reference an acting user (`created_by_user_id`, `reviewed_by_user_id`, `edited_by_user_id`) — never cascade-delete a user's authored history.
- Filament Resources go in `app/Filament/Resources/`, one Resource per model, RelationManagers for nested data (`ProjectMilestoneResource` as a relation manager on `ProjectResource`).
- PDF Blade views live only in `resources/views/pdf/` — never inline HTML inside a Service or Job class (PRD §7.2).
- PDF generation is **always** dispatched via `GeneratePdfJob` (queued). Never generate a PDF synchronously in a controller/Livewire action.
- `meta_data` JSONB columns are edited via Filament `KeyValue`/`Group` components, not raw text areas.
- Status enums (`daily_reports.status`, `projects.status`, `project_milestones.status`, `milestone_sub_jobs.status`, `sub_job_delay_events.status`, `payroll_runs.status`) are PHP backed enums in `app/Enums/`, referenced by class, never magic strings. `v3 CHANGED`
- Money fields (`budget`, `daily_rate`, `payroll_items.*_pay`) are `decimal`, never `float`, in both migrations and casts. `v3 CHANGED`
- Timestamps stored UTC; convert for display using the owning `project.timezone`.
- **Weight percentages must tally to 100%** for sibling sets — all `milestone_sub_jobs` under one `project_milestones` row, and all `project_milestones` under one `project` — validated at the app layer on create/update, following the same friendly-error pattern as the `(site_id, report_date, shift)` duplicate check below. `v3 NEW`
- `daily_target` on `daily_reports` is **system-computed only** (deficit carry-forward engine) — never a user-editable form field. `v3 NEW`

## RBAC — treat as highest scrutiny tier
Four roles: `admin`, `site_engineer`, `hrd`, `client` (PRD §3). `v3 CHANGED` — `client` is retained as a data record but is **no longer a panel-accessible role**; there is no client-facing Filament panel to scope in v0.2.0. Every new query on `daily_reports`, `sites`, `projects`, `milestone_sub_jobs`, or `worker_attendance` MUST go through an Eloquent scope or Policy — never an unscoped `Model::all()`/`Model::query()` in a controller, Livewire component, or Filament Resource.
- Site Engineer: read/write only `daily_reports` (and their `daily_report_photos`/`daily_report_workers`) for sites under their `project_user` assignments. Cannot access `worker_attendance` or payroll. `v3 CHANGED`
- HRD: read/write only `worker_attendance`. No access to `daily_reports`, projects, milestones, or sub-jobs. `v3 NEW`
- Client: no panel access at all (§ above) — treat any request to add client login/read routes back in as out of scope unless explicitly re-approved.
- Any change touching a Policy or scope MUST ship with a Pest feature test proving the *other* roles cannot see/edit the data (e.g., an HRD user cannot fetch a `daily_report` by guessing its UUID; a Site Engineer cannot see `worker_attendance`).
- Do not weaken a scope or policy to make a test pass — fix the query, not the assertion.

## State machine
`draft → need_approval → published`, with `revision_requested` as a branch back to editable (PRD §5). Every transition:
- Must go through a dedicated action/method (e.g. `DailyReport::submitForApproval()`), never a raw `->update(['status' => ...])` scattered in UI code.
- `revision_requested → need_approval` MUST write a snapshot to `daily_report_revisions` before saving the new state.
- `published` is terminal/locked — no direct edits; only a new revision cycle can reopen it, and only if you build that flow explicitly (not in current scope).
- `published` now also dispatches `SendClientReportEmailJob` (queued, same rule as `GeneratePdfJob` below) instead of the removed client-portal notification. `v3 CHANGED`

## Sub-job delay state machine `v3 NEW`
Separate, lightweight state machine on `sub_job_delay_events`: `red → yellow → green`, resetting to a **new** `red` event on recurrence after a `green` resolution (PRD §5.3).
- Must go through dedicated actions (e.g. `SubJobDelayEvent::submitMitigationPlan()`, `::markRecovered()`), same rule as the Daily Report state machine — never a raw status update in UI code.
- Creating the initial `red` event and cascading the date shift to subsequent milestones/project end date happens in one transaction — don't leave the cascade as a separate, un-atomic follow-up step.

## Camera-only capture rule `v3 NEW`
- Site Engineer (progress photos) and HRD (attendance photos) **must** use a live in-app camera component — no gallery/file-picker path. Admin remains unrestricted (device upload or live capture).
- Filament's stock `FileUpload` cannot enforce camera-only — this needs a custom Livewire/Alpine component using the browser's native camera capture API (`<input capture>` / `getUserMedia`). Do not reach for `FileUpload` alone for SE or HRD photo fields.
- Treat client-side camera-only enforcement as a soft guarantee — pair it with a server-side check on embedded capture metadata (timestamp/recency) as defense in depth (PRD §8.1). Don't skip the server-side check just because the client-side control is in place.
- Site Engineer progress photos are a **before/after pair + description**, not a multi-file upload — exactly one `daily_report_photos` row per `daily_reports` row, capped at 2 photos (before, after) per shift.

## Testing expectations
- Every Policy and scope: a Pest test per role confirming allowed AND denied access.
- Every state transition: a test for the legal transition and at least one test asserting an illegal transition is rejected. This now covers both the Daily Report state machine and the `sub_job_delay_events` color state machine. `v3 CHANGED`
- PDF jobs: use `Queue::fake()`/`Bus::fake()` and assert the DTO passed to the Blade view, not the rendered PDF bytes.
- Email jobs: use `Mail::fake()` and assert the correct recipients (Sender/Receiver/CC) and attached PDF, not the rendered email HTML byte-for-byte. `v3 NEW`
- Weight-sum validation: a test asserting a sub-job/milestone set summing to ≠100% is rejected on save. `v3 NEW`
- Deficit carry-forward: a test asserting a missed target rolls into the next day's `daily_target`, and that a persisting warning notifies the admin only once (`first_triggered_at` set, no duplicate notification on re-evaluation). `v3 NEW`
- Payroll: a test asserting `regular_pay`/`overtime_pay` are correctly derived from `worker_attendance` for a given 14-day period. `v3 NEW`
- Don't skip tests to unblock a task — mark with `->skip('reason')` and flag it, never delete a failing assertion.

## Boundaries — do not do these without explicit approval
- Never commit `.env`, real credentials, or S3/API keys. Use `.env.example` with dummy values only.
- Never write file uploads to local disk in application code — always through the configured filesystem disk (S3-compatible), even in dev (MinIO).
- Never hard-code MIME/extension checks from the client-supplied filename — validate uploads by server-side content sniffing.
- Never expose signed URLs with no expiry for photos/PDFs.
- Never add a new top-level dependency (Composer or npm) without noting it in the PR description and why.
- Never generate a migration that drops or renames a column without a paired down() that's actually reversible.
- Never wire up a real SMTP/paid email provider without explicit approval — v0.2.0 email delivery stays on the Mailpit/dummy transport; only `.env` values change when that approval happens, not application code. `v3 NEW`
- Never re-add client panel/login routes without explicit approval — the removal in Phase 8 is deliberate, not an oversight to "fix." `v3 NEW`

## PR / commit style
- One task = one focused commit or PR. No "implement everything in Phase 3" mega-commits.
- Commit message: `[Phase N] short imperative summary` (e.g. `[Phase 8] Add milestone_sub_jobs migration with weight validation`).
- Run `pint` + `pest` before every commit; do not commit red tests.

## Known gotchas
- `daily_reports` needs an app-layer (not just DB) check preventing duplicate `(site_id, report_date, shift)` — enforce in the Resource form, not only a DB constraint, so the UI gives a friendly error. `v3 CHANGED` (was `(site_id, report_date)` before shift-based reporting)
- Weekly Digest PDF aggregates 7 days of `published` reports — exclude `draft`/`need_approval`/`revision_requested` even if within the date range.
- Client emails fire only on `published`, never on intermediate states — double-check `SendClientReportEmailJob` is dispatched only from that one transition, same rule that used to apply to the old client-portal notification. `v3 CHANGED`
- The deficit carry-forward scheduled job must run **before** the day's shift reports are evaluated for warnings, or the `daily_target` used for the warning check will be stale. Order the scheduler entries deliberately. `v3 NEW`
- The delay-cascade date shift and the `sub_job_delay_events` creation must happen atomically — a delay recorded without the corresponding downstream date shift (or vice versa) leaves the project timeline inconsistent with its own audit trail. `v3 NEW`
- `worker_attendance` is the payroll source of truth; `daily_report_workers` is not — don't accidentally wire payroll calculations to the SE's allocation Repeater data. `v3 NEW`
