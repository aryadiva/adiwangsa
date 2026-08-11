# Development Tasks — Construction Operations Dashboard

> **Reference:** `prd.md` (v2 PRD) is the source of truth. `AGENTS.md` is the fast-reference.
> **Commit style:** `[Phase N] short imperative summary` (e.g. `[Phase 1] Add daily_reports migration with UUID PK`).
> **Pre-commit checklist:** `pint` + `pest` must pass before every commit.

---

## Phase 1: Database & Core Models

### 1.1 Project Bootstrap & Tooling
- [x] Initialize Laravel 11/12 project (`composer create-project laravel/laravel .`)
- [x] Configure PostgreSQL connection in `config/database.php`
- [x] Install core dependencies:
  - `filament/filament:^3.0`
  - `spatie/laravel-permission`
  - `spatie/laravel-activitylog`
  - `spatie/laravel-pdf` (or `barryvdh/laravel-dompdf`)
  - `intervention/image-laravel`
- [x] Configure S3-compatible filesystem disk (`photos`, `pdfs`) in `config/filesystems.php`
- [x] Configure Redis queue driver in `.env` and `config/queue.php`
- [x] Run `php artisan filament:install --panels`
- [x] Run `php artisan shield:install` (Filament Shield)
- [x] Configure `pint` and `phpstan` (if applicable)

### 1.2 Migrations (UUIDs, Soft Deletes, Indexes)
- [x] `users` — UUID PK, `role` enum, `is_active`, soft deletes
- [x] `clients` — UUID PK, `user_id` FK (nullable), `meta_data` JSONB, soft deletes
- [x] `projects` — UUID PK, `client_id` FK (restrict), `code` unique, `status` enum, `budget` decimal(15,2), `timezone`, `meta_data` JSONB, soft deletes, index `(client_id, status)`
- [x] `project_milestones` — UUID PK, `project_id` FK (cascade), `status` enum, `sort_order`, soft deletes, index `(project_id, status)`
- [x] `sites` — UUID PK, `project_id` FK (cascade), `latitude`/`longitude` decimal(10,7), soft deletes
- [x] `project_user` — pivot, composite PK `(project_id, user_id)`, both FKs cascade
- [x] `workers` — UUID PK, `trade_skill`, `daily_rate` decimal(10,2), `meta_data` JSONB, soft deletes
- [x] `daily_reports` — UUID PK, `site_id` FK (restrict), `created_by_user_id`/`reviewed_by_user_id` FKs (set null), `status` enum (default `draft`), `meta_data` JSONB, soft deletes, unique-ish index `(site_id, report_date)`, index `(status)`, index `(report_date)`
- [x] `daily_report_revisions` — UUID PK, `daily_report_id` FK (cascade), `snapshot` JSONB, `edited_by_user_id` FK (set null)
- [x] `daily_report_photos` — UUID PK, `daily_report_id` FK (cascade), `file_path`, `thumbnail_path`, `file_size_bytes`, soft deletes
- [x] `daily_report_workers` — UUID PK, `daily_report_id` FK (cascade), `worker_id` FK (restrict), `hours_worked` decimal(4,2)
- [x] `activity_log` — standard Spatie schema (polymorphic `subject`, `causer`, `event`, `properties` JSONB)
- [x] **Verify:** Every table except `project_user` has `deleted_at`. Every acting-user FK uses `onDelete('set null')`.
- [x] **Verify:** No migration drops/renames columns without a reversible `down()`.

### 1.3 Eloquent Models, Enums & Casts
- [x] Create `app/Enums/`:
  - `UserRole.php` (`admin`, `site_engineer`, `client`)
  - `ProjectStatus.php` (`planning`, `active`, `on_hold`, `completed`)
  - `ProjectMilestoneStatus.php` (`pending`, `in_progress`, `completed`, `delayed`)
  - `DailyReportStatus.php` (`draft`, `need_approval`, `published`, `revision_requested`)
  - `WeatherCondition.php` (`sunny`, `rainy`, `cloudy`, `stormy`)
- [x] Create models with `HasUuids`, `SoftDeletes`, `LogsActivity` (where applicable):
  - `User`, `Client`, `Project`, `ProjectMilestone`, `Site`, `Worker`, `DailyReport`, `DailyReportRevision`, `DailyReportPhoto`, `DailyReportWorker`
- [x] Define all relationships (belongsTo, hasMany, belongsToMany for `project_user`)
- [x] Cast `meta_data` to `array` on all JSONB models
- [x] Cast `budget`/`daily_rate` to `decimal:2` (string casts, never float)
- [x] Add `timezone` accessor on `Project` for UTC→local display conversion

### 1.4 Seeding
- [x] Create seeders: `UserSeeder`, `ClientSeeder`, `ProjectSeeder`, `SiteSeeder`, `WorkerSeeder`
- [x] Seed at least: 1 admin, 2 site engineers, 1 client user; 2 projects with sites; 5 workers
- [x] Run `php artisan migrate:fresh --seed` and verify all tables populate correctly

---

## Phase 2: Authentication & Scoped RBAC

### 2.1 Policies
- [x] `ProjectPolicy` — admin full; site engineer view assigned; client view own
- [x] `SitePolicy` — admin full; site engineer view assigned projects' sites; client view own projects' sites
- [x] `DailyReportPolicy` — admin full; site engineer CRUD only assigned sites; client read-only `published` only
- [x] `WorkerPolicy` — admin full; site engineer read-only; client no access
- [x] `ProjectMilestonePolicy` — admin full; site engineer read-only; client read-only
- [x] **Verify:** No `Model::all()` or unscoped `Model::query()` in any policy or resource

### 2.2 Eloquent Query Scopes
- [x] `DailyReport::scopeForSiteEngineer($query, User $user)` — filter by `project_user` assignments
- [x] `DailyReport::scopeForClient($query, User $user)` — filter by `client_id` + enforce `status = published`
- [x] Apply scopes in all Filament Resources' `getEloquentQuery()` methods

### 2.3 RBAC Feature Tests (Pest)
- [x] `DailyReportPolicyTest` — test each role can/cannot view/edit/delete reports
- [x] `ProjectPolicyTest` — test each role's project visibility
- [x] `ClientVisibilityTest` — assert client CANNOT see `draft`/`need_approval`/`revision_requested` reports (even by guessing UUID)
- [x] `SiteEngineerScopeTest` — assert engineer cannot see reports for unassigned sites
- [x] **Rule:** Every test must assert BOTH allowed AND denied access per role. Do not skip failing assertions.

### 2.4 Filament Shield Configuration
- [x] Run `php artisan shield:generate --all`
- [x] Assign default roles and permissions in seeder
- [x] Verify login flow and role-based menu visibility

---

## Phase 3: Daily Report Resource & Auto-Save

### 3.1 DailyReportResource (Filament)
- [x] Create `app/Filament/Resources/DailyReportResource.php`
- [x] **Form schema:**
  - Site picker: filtered by user's assigned projects (`project_user`)
  - `report_date` (DatePicker)
  - `weather_condition` (Select enum)
  - `work_summary` (Textarea)
  - `delays_or_issues` (Textarea, nullable)
  - Worker allocations: `Repeater` → `daily_report_workers` (worker select, hours_worked, remarks)
  - Photo uploads: `FileUpload` (multiple, `image/*`, max 10MB, S3 disk)
  - `meta_data`: `KeyValue` or `Group` component for flexible fields
  - `admin_notes` (Textarea, visible only to admin)
- [x] **Duplicate prevention:** App-layer check for `(site_id, report_date)` uniqueness in form validation with friendly error message
- [x] **Table:**
  - Columns: report_date, site.name, status badge, created_by.name
  - Filters: status, date range, site
  - Admin `need_approval` indicator: navigation badge count + live count in status filter option
    > *(deviated from "tab filter" — Filament v3.3.54 lacks `Table::tabs()`; implemented as nav badge + count-in-filter instead)*

### 3.2 Auto-Save (Draft State)
- [x] Implement `wire:poll.10s="saveDraft"` on edit form when `status === draft`
  > *(implemented as `:wire:poll="'10s saveDraft'"` rendered only when draft — equivalent Livewire interval+method form of `wire:poll.10s="saveDraft"`)*
- [x] Alpine.js store to hold form state client-side
- [x] Retry-on-failure banner: "Unsaved changes — retrying" (not just timestamp)
- [x] Visual indicator in topbar: "Draft Saved at HH:mm:ss" / error state
- [x] **Test:** Auto-save persists data; retry works after simulated disconnect

### 3.3 State Machine Actions
- [x] `submitForApproval()` — Site Engineer action, transitions `draft` → `need_approval`
- [x] `approveAndPublish()` — Admin action, transitions `need_approval` → `published` — *notification dispatch wired in 4.2 when notification classes exist*
- [x] `requestRevision()` — Admin action (modal with `admin_notes`), transitions `need_approval` → `revision_requested` — *notification dispatch wired in 4.2*
- [x] `resubmitForApproval()` — Site Engineer action, transitions `revision_requested` → `need_approval`, **writes snapshot to `daily_report_revisions` before the transition**
- [x] **Guard:** `published` is terminal — no direct edits (Save locked + backend block)
- [x] **Test (Pest):** Legal transitions pass; illegal transitions (e.g. `draft` → `published`) are rejected

### 3.4 Photo Upload & Processing
- [x] Server-side MIME sniffing (not extension-based) — reject mismatches (`DailyReportPhotoService::sniffMime` + `assertAllowed`)
- [x] Intervention Image: generate thumbnails (`scaleDown` 600px), EXIF orientation (auto-orient on decode), compress (JPEG q75)
- [x] Store original + thumbnail on S3-compatible disk (`photos`)
- [x] Save `file_path`, `thumbnail_path`, `file_size_bytes` to `daily_report_photos`
- [x] Signed, expiring URLs for photo display (`DailyReportPhoto::signedUrl` / `signedThumbnailUrl`)

---

## Phase 4: Milestones & Notifications

### 4.1 ProjectMilestoneResource
- [x] Create `app/Filament/Resources/ProjectMilestoneResource.php`
  > *(implemented as `ProjectResource/RelationManagers/ProjectMilestonesRelationManager.php` per AGENTS.md "ProjectMilestoneResource as a relation manager on ProjectResource" + PRD §6.2 — nested, not a standalone top-level resource)*
- [x] Add as RelationManager on `ProjectResource` (inline table with progress badges)
- [x] Fields: title, description, target_date, completed_at, status, sort_order
- [x] Sortable/reorderable by `sort_order`

### 4.2 Notifications
- [ ] Create notification classes:
  - `ReportSubmittedNotification` (to admin, on `draft` → `need_approval`)
  - `ReportApprovedNotification` (to engineer, on `published`)
  - `RevisionRequestedNotification` (to engineer, on `revision_requested`)
  - `ReportPublishedNotification` (to client, on `published` ONLY — never intermediate states)
- [ ] Channels: mail + database (Filament notification bell)
- [ ] **Verify:** Client notification listener is scoped strictly to `published` transition
- [ ] **Test:** Notification fires on correct transition; does NOT fire on wrong transition

---

## Phase 5: PDF Paperwork Service & Client Portal

### 5.1 PDF Service Architecture
- [ ] Create `app/DTOs/ReportDataDTO.php` — maps DB + JSONB data for Blade views
- [ ] Create `app/Services/PdfReportService.php` — accepts DTO, renders Blade, returns PDF bytes
- [ ] Create `app/Jobs/GeneratePdfJob.php` — queued, dispatches service, stores to S3, fires completion notification
- [ ] Blade templates in `resources/views/pdf/`:
  - `daily-progress.blade.php` — project header, weather, progress, worker count, 2×2 photo grid
  - `weekly-digest.blade.php` — 7 days of `published` reports, worker hours, weather delays, milestone completions
  - `attendance-roster.blade.php` — workers, trades, sites, hours across date range
- [ ] **CSS:** Inline/embedded print styles, `@page { size: A4 portrait; margin: 15mm; }`
- [ ] **Rule:** NEVER inline HTML in Service classes; NEVER generate PDF synchronously in HTTP request

### 5.2 Filament PDF Actions
- [ ] Table action `Generate PDF` dispatches `GeneratePdfJob` to queue
- [ ] User gets Filament notification with download link when job completes
- [ ] Store generated PDF path in `daily_reports` or `generated_documents` table — do not regenerate on every download
- [ ] Weekly Digest: aggregate ONLY `published` reports; exclude `draft`/`need_approval`/`revision_requested`

### 5.3 PDF Tests (Pest)
- [ ] Use `Queue::fake()` / `Bus::fake()` to assert `GeneratePdfJob` is dispatched
- [ ] Assert DTO passed to Blade view contains correct data (do not test rendered PDF bytes)
- [ ] Test Weekly Digest excludes non-published reports

### 5.4 Client Portal
- [ ] Client read-only dashboard: list of assigned projects, `published` daily reports only
- [ ] Signed, expiring URLs for PDF downloads (not permanent public links)
- [ ] **Test:** Client cannot access admin routes, cannot edit reports, cannot see non-published reports

---

## Phase 6: Polish, Audit & Launch Prep

### 6.1 Audit Logging
- [ ] Verify `LogsActivity` trait on `DailyReport`, `Project`, `ProjectMilestone`
- [ ] Admin can view activity log per report (`View Activity Log` action)
- [ ] Activity log shows: who changed status, old→new values, timestamp

### 6.2 Performance
- [ ] Verify all indexes from Section 4 are present
- [ ] Server-side pagination on all table views (no full result set loading)
- [ ] Review N+1 queries in Filament resources; eager-load relationships

### 6.3 Security Hardening
- [ ] Rate-limit auth endpoints and client portal routes
- [ ] Enforce password hashing + minimum policy
- [ ] Force password reset on first login for client-invited accounts (optional)
- [ ] Verify no `.env` or credentials committed

### 6.4 Final Test Suite
- [ ] Run full `pest` suite — all green before launch
- [ ] Run `pint` — zero formatting issues
- [ ] Run `php artisan test` — confirm all tests pass
- [ ] Manual smoke test: admin creates project → engineer submits report → admin approves → client views PDF

---

## Appendix: Quick Commands

```bash
# Fresh start
php artisan migrate:fresh --seed

# Run tests
php artisan test
./vendor/bin/pest
./vendor/bin/pest --filter=DailyReportPolicyTest

# Lint
./vendor/bin/pint

# Static analysis
./vendor/bin/phpstan analyse

# Local server
./vendor/bin/sail up
npm run dev
```
