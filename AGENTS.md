# AGENTS-v2.md

Construction Operations & Back-Office Management Dashboard — Laravel 11/12 + Filament v3 (TALL stack), PostgreSQL 15+.
Full spec lives in `docs/prd-v2.md` (the v2 PRD). Read it before implementing any resource, policy, or migration —
this file is the fast-reference; the PRD is the source of truth when they conflict.

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
- Status enums (`daily_reports.status`, `projects.status`, `project_milestones.status`) are PHP backed enums in `app/Enums/`, referenced by class, never magic strings.
- Money fields (`budget`, `daily_rate`) are `decimal`, never `float`, in both migrations and casts.
- Timestamps stored UTC; convert for display using the owning `project.timezone`.

## RBAC — treat as highest scrutiny tier
Three roles: `admin`, `site_engineer`, `client` (PRD §3). Every new query on `daily_reports`, `sites`, or `projects` MUST go through an Eloquent scope or Policy — never an unscoped `Model::all()`/`Model::query()` in a controller, Livewire component, or Filament Resource.
- Site Engineer: read/write only `daily_reports` for sites under their `project_user` assignments.
- Client: read-only, only `status = 'published'` reports, only for their own `client_id`.
- Any change touching a Policy, scope, or the client portal MUST ship with a Pest feature test proving the *other* roles cannot see/edit the data (e.g., a client cannot fetch a `draft` report by guessing its UUID).
- Do not weaken a scope or policy to make a test pass — fix the query, not the assertion.

## State machine
`draft → need_approval → published`, with `revision_requested` as a branch back to editable (PRD §5). Every transition:
- Must go through a dedicated action/method (e.g. `DailyReport::submitForApproval()`), never a raw `->update(['status' => ...])` scattered in UI code.
- `revision_requested → need_approval` MUST write a snapshot to `daily_report_revisions` before saving the new state.
- `published` is terminal/locked — no direct edits; only a new revision cycle can reopen it, and only if you build that flow explicitly (not in current scope).

## Testing expectations
- Every Policy and scope: a Pest test per role confirming allowed AND denied access.
- Every state transition: a test for the legal transition and at least one test asserting an illegal transition is rejected.
- PDF jobs: use `Queue::fake()`/`Bus::fake()` and assert the DTO passed to the Blade view, not the rendered PDF bytes.
- Don't skip tests to unblock a task — mark with `->skip('reason')` and flag it, never delete a failing assertion.

## Boundaries — do not do these without explicit approval
- Never commit `.env`, real credentials, or S3/API keys. Use `.env.example` with dummy values only.
- Never write file uploads to local disk in application code — always through the configured filesystem disk (S3-compatible), even in dev (MinIO).
- Never hard-code MIME/extension checks from the client-supplied filename — validate uploads by server-side content sniffing.
- Never expose signed URLs with no expiry for photos/PDFs.
- Never add a new top-level dependency (Composer or npm) without noting it in the PR description and why.
- Never generate a migration that drops or renames a column without a paired down() that's actually reversible.

## PR / commit style
- One task = one focused commit or PR. No "implement everything in Phase 3" mega-commits.
- Commit message: `[Phase N] short imperative summary` (e.g. `[Phase 3] Add auto-save retry on Livewire poll failure`).
- Run `pint` + `pest` before every commit; do not commit red tests.

## Known gotchas
- `daily_reports` needs an app-layer (not just DB) check preventing duplicate `(site_id, report_date)` — enforce in the Resource form, not only a DB constraint, so the UI gives a friendly error.
- Weekly Digest PDF aggregates 7 days of `published` reports — exclude `draft`/`need_approval`/`revision_requested` even if within the date range.
- Client notifications fire only on `published`, never on intermediate states — double-check the notification listener is scoped to that one transition.
