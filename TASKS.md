# Development Tasks — Construction Operations Dashboard

> **Reference:** `prd-v2.md` (**v3 PRD**, v0.2.0 build) is the source of truth. `AGENTS.md` is the fast-reference.
> **Commit style:** `[Phase N] short imperative summary` (e.g. `[Phase 1] Add daily_reports migration with UUID PK`).
> **Pre-commit checklist:** `pint` + `pest` must pass before every commit.
>
> **v0.1.0 → v0.2.0:** Phases 1–7 below are complete and shipped as v0.1.0. **Phase 8** (new, below) covers
> the v0.2.0 client-feedback build: weighted milestones/sub-jobs, shift-based daily reports with an automated
> target/deficit engine, an automated delay cascade + mitigation workflow, removal of the client Filament
> portal in favor of emailed PDF reports, worker/attendance/payroll additions, and a new HRD role with
> live-camera-only capture (also now enforced for Site Engineer progress photos). See `prd-v2.md` v3 changelog
> for the full rationale behind each change.

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
- [x] Create notification classes:
  - [x] `ReportSubmittedNotification` (to admin, on `draft` → `need_approval`)
  - [x] `ReportApprovedNotification` (to engineer, on `published`)
  - [x] `RevisionRequestedNotification` (to engineer, on `revision_requested`)
  - [x] `ReportPublishedNotification` (to client, on `published` ONLY — never intermediate states)
- [x] Channels: mail + database (Filament notification bell)
  > *(added `notifications` table migration with `uuidMorphs` — was missing, required for the database channel; enabled bell via `AdminPanelProvider::databaseNotifications()`)*
- [x] **Verify:** Client notification listener is scoped strictly to `published` transition
  > *(dispatched from `DailyReport::approveAndPublish()` only — never on draft/need_approval/revision_requested)*
- [x] **Test:** Notification fires on correct transition; does NOT fire on wrong transition

---

## Phase 5: PDF Paperwork Service & Client Portal

### 5.1 PDF Service Architecture
- [x] Create `app/DTOs/ReportDataDTO.php` — maps DB + JSONB data for Blade views
  > *(immutable, queue-safe — primitives/arrays only; static factories `forDailyReport` / `forWeeklyDigest` / `forAttendanceRoster`; `app/Enums/DocumentType.php` added for doc routing)*
- [x] Create `app/Services/PdfReportService.php` — accepts DTO, renders Blade, returns PDF bytes
- [x] Create `app/Jobs/GeneratePdfJob.php` — queued, dispatches service, stores to S3, fires completion notification
  > *(requires `app/Notifications/PdfReadyNotification.php` with signed 24h download URL; `SerializesModels` carries the fully-built DTO)*
- [x] Blade templates in `resources/views/pdf/`:
  - `daily-progress.blade.php` — project header, weather, progress, worker count, 2×2 photo grid (signed thumbnails from `photos` disk)
  - `weekly-digest.blade.php` — 7 days of `published` reports, worker hours, weather delays, milestone completions
  - `attendance-roster.blade.php` — workers, trades, sites, hours across date range
- [x] **CSS:** Inline/embedded print styles, `@page { size: A4 portrait; margin: 15mm; }`
- [x] **Rule:** NEVER inline HTML in Service classes; NEVER generate PDF synchronously in HTTP request
  > *(model change: added `weather_condition` enum cast to `DailyReport` so DTO mapping is type-safe)*


### 5.2 Filament PDF Actions
- [x] Table action `Generate PDF` dispatches `GeneratePdfJob` to queue
  > *(DailyReportResource: Generate PDF on `published` reports, admin-only. ProjectResource: Weekly Digest PDF + Attendance Roster PDF with date-range modals. All via `app/Services/PdfDocumentService.php`)*
- [x] User gets Filament notification with download link when job completes
  > *(`PdfReadyNotification` now links to `generated-documents.download` route; Bell + mail)*
- [x] Store generated PDF path in `daily_reports` or `generated_documents` table — do not regenerate on every download
  > *(new `generated_documents` migration + `GeneratedDocument` model; `PdfDocumentService` reuses an existing doc matching subject+period instead of regenerating)*
- [x] Weekly Digest: aggregate ONLY `published` reports; exclude `draft`/`need_approval`/`revision_requested`
  > *(tested in 5.1 DTO + attendance roster job test; download route `routes/web.php` streams from `pdfs` disk, authorized to admin/owner/client)*

### 5.3 PDF Tests (Pest)
- [x] Use `Queue::fake()` / `Bus::fake()` to assert `GeneratePdfJob` is dispatched
  > *(GeneratePdfJobTest: `Bus::fake` + `Bus::assertDispatched` verifying the built DTO payload reaches the queued job, not a synchronous render)*
- [x] Assert DTO passed to Blade view contains correct data (do not test rendered PDF bytes)
  > *(PdfReportServiceTest: mock `Barryvdh\DomPDF\PDF`, assert `loadView('pdf.daily-progress'|'pdf.weekly-digest', ['dto' => $dto])` — no byte-level assertions)*
- [x] Test Weekly Digest excludes non-published reports
  > *(PdfReportServiceTest: draft/need_approval/revision_requested + out-of-range excluded from `forWeeklyDigest`)*

### 5.4 Client Portal
- [x] Client read-only dashboard: list of assigned projects, `published` daily reports only
  > *(dedicated `client` panel — `ClientPanelProvider` + `App\Filament\Client\Pages\Dashboard`; No create/edit; scoped to own client + published via `DailyReport::forClient`)*
- [x] Signed, expiring URLs for PDF downloads (not permanent public links)
  > *(dashboard emits `GeneratedDocument::signedUrl()` (24h); download streams from `pdfs` disk behind `generated-documents.download` route authorized to admin/owner/client)*
- [x] **Test:** Client cannot access admin routes, cannot edit reports, cannot see non-published reports
  > *(`User::canAccessPanel` now routes clients to `client` panel, others to `admin`; `ClientPortalTest` covers /admin + /client/daily-reports forbidden, published-only, foreign-client hidden)*

---

## Phase 6: Polish, Audit & Launch Prep

### 6.1 Audit Logging
- [x] Verify `LogsActivity` trait on `DailyReport`, `Project`, `ProjectMilestone`
  > *(DailyReport + Project already had it; added to `ProjectMilestone` with `logOnly(['title','status','target_date','completed_at'])` + `logOnlyDirty`)*
- [x] Admin can view activity log per report (`View Activity Log` action)
  > *(row action on `DailyReportResource` table, admin-only visible; opens modal rendering `resources/views/filament/activity-log.blade.php`)*
- [x] Activity log shows: who changed status, old→new values, timestamp
  > *(reads `attribute_changes` JSONB — causer name, `old.status`→`attributes.status`, and `created_at` in report project timezone)*
  > *(this Spatie version exposes the subject relation as `activitiesAsSubject()` and stores diffs in `attribute_changes`, not `activities()`/`changes()`)*

### 6.2 Performance
- [x] Verify all indexes from Section 4 are present
  > *(verified: `projects(client_id,status)`, `project_milestones(project_id,status)`, `daily_reports(site_id,report_date)` unique + `status` + `report_date`; all FK columns auto-indexed by `foreignUuid()`)*
- [x] Server-side pagination on all table views (no full result set loading)
  > *(every table uses Filament default pagination (10/page) — none call `paginated(false)`; verified via `getTableRecordsPerPage() === 10`)*
- [x] Review N+1 queries in Filament resources; eager-load relationships
  > *(Fixed real N+1 in `GeneratedDocumentResource::subject` closure — added `with(['dailyReport.site','project'])`; added `with(['site.project','createdBy'])` to `DailyReportResource::scopedQuery`. `->counts()` columns use `withCount`; dot-relationship columns auto-eager-loaded by Filament)*

### 6.3 Security Hardening
- [x] Rate-limit auth endpoints and client portal routes
  > *(Filament's built-in `Login` page already rate-limits both `admin`/`client` panels via `WithRateLimiting` (5 attempts). Added a `document-downloads` limiter (30/min per user/IP) applied via `throttle:document-downloads` on the signed download route)*
- [x] Enforce password hashing + minimum policy
  > *(password already hashed via `'password' => 'hashed'` cast; added `minLength(8)` on `UserResource` password field and the forced-reset form)*
- [x] Force password reset on first login for client-invited accounts (optional)
  > *(added `users.must_change_password`; clients created via `UserResource` are flagged; `EnsurePasswordChanged` middleware on the client panel bounces them to a `ChangePassword` page until set)*
- [x] Verify no `.env` or credentials committed
  > *(`.env`/`.env.backup`/`.env.production` gitignored; only `.env.example` tracked; `git ls-files` shows no `.env`/keys)*

### 6.4 Final Test Suite
- [x] Run full `pest` suite — all green before launch
  > *(112 tests / 360 assertions, stable across 3 consecutive runs)*
- [x] Run `pint` — zero formatting issues
  > *(`./vendor/bin/pint --test` → PASS, 151 files)*
- [x] Run `php artisan test` — confirm all tests pass
  > *(112 passed / 360 assertions; phpstan `[OK] No errors`)*
- [x] Manual smoke test: admin creates project → engineer submits report → admin approves → client views PDF
  > *(automated E2E via `CrossPhaseIntegrationTest` + full `migrate:fresh --seed` succeeded; client portal + state-machine action tests green. Full Phase 1-6 feature/UI audit performed — all features scoped, state-machine guarded, and present in the front-end (resources, relation manager, bell, client portal, PDF actions, activity log, generated-PDF resource, change-password page))*

---

## Phase 7: Bug Fixing, Touch Up & Localization

> All subsequent fixes and updates fall under this phase. Commit style: `[Phase 7] <imperative summary>`.
> Former **Phase 8** (localization) was merged into this phase; there is no separate Phase 8.

### 7.1 Bug Fixes
- [x] Client `Change Password` page redirected back to Dashboard for non-forced users
  > *(removed the early redirect in `ChangePassword::mount()` that made the page forced-reset-only; now reachable voluntarily by any authenticated client. Regression tests added: `PanelLoginTest` (client authenticates to client panel; same creds rejected on admin panel) + `SecurityHardeningTest` (client reaches change-password voluntarily).)*
- [x] Fix site photos invisible in Filament preview via signed URLs `94a7862`
  > *(root cause: `DailyReportResource` `FileUpload` lacked `->visibility('private')` — Filament defaults component visibility to `public`, so the preview used an unsigned `Storage::url()` against the private MinIO bucket → HTTP 403. Added `->visibility('private')` + regression test in `DailyReportResourceFormTest`.)*
- [x] Fix empty "Generated PDFs" list — no queue worker consuming jobs `6d1d09a`
  > *(root cause: `GeneratePdfJob` (projects + daily reports) queued to Redis, but no `queue:work` process ever ran, so `generated_documents` stayed empty. Added a dedicated `worker` compose service (dev-only) running `queue:work redis --stop-when-empty` in a durable `while true` loop; MinIO reached via `http://laravel.test:9000` since minio shares `laravel.test`'s network namespace. See `compose.yaml` note — dev-only, not a production blueprint.)*

### 7.2 Photo Upload & Reconciliation
- [x] Reconcile daily report photos: dedupe, prune orphans, warn on missing `fa20eeb`
  > *(`EditDailyReport::afterSave` now diffs kept-vs-existing photo paths — inserts new rows, deletes removed rows, dedupes stale duplicates; exposes missing paths to the edit form via a warning banner. Added `photos:prune` artisan command (`--dry-run` supported) that soft-deletes orphan/duplicate `daily_report_photos` rows. Moved shared `draftFor()` fixture helper into `tests/Support/helpers.php` so reconciliation tests run standalone.)*

### 7.3 Localization (merged from former Phase 8)
- [x] Add per-user EN/ID language toggle `7465822`
  > *(custom Livewire `LanguageSwitcher` toggle button mounted in both Filament panels' topbars (`USER_MENU_BEFORE`); persists `users.locale` (default `en`, migration `2026_08_11_090000`) across logout/login; applied each request via `SetLocale` middleware + `App\Support\LocaleContext`.)*
- [x] Localize Filament core UI + app strings
  > *(published Filament en/id panel translations (`lang/vendor/*`, pruned to en+id); added `lang/en` + `lang/id` PHP arrays for `pdf.*`, `weather`, and `enum` groups; added `getLabel()` returning `__()` on `WeatherCondition`, `DailyReportStatus`, `ProjectStatus`, `ProjectMilestoneStatus`, `DocumentType`.)*
- [x] Localize PDF templates
  > *(`ReportDataDTO` gains a `locale` field baked from the requesting user (`PdfDocumentService`); `PdfReportService` sets app + Carbon locale before render; all three `pdf/*.blade.php` templates use `__('pdf.*')`, `translatedFormat()` dates, and localized weather.)*
- [x] Relabel currency USD → IDR (no conversion)
  > *(`ProjectResource` budget + `WorkerResource` daily_rate changed `->money('USD')` → `->money('IDR')`; factories re-seeded to IDR-scale dummy values — daily_rate ~Rp 1.3M–4M, budget ~Rp 16B–800B.)*
- [x] **Tests:** locale defaults, per-user persistence, livewire toggle, IDR formatting, PDF DTO locale baking, enum translation `LocaleSwitchingTest`

### 7.4 Infrastructure & Deployment Composing
- [x] Remove temporary MinIO network-namespace hack — make `compose.yaml` portable (dev + prod)
  > *(supersedes the TEMP dev-only worker workaround in 7.1 `6d1d09a`. Root cause of the "weird" networking: `.env` used `AWS_ENDPOINT=http://localhost:9000`, which only worked while `minio` was jammed into `laravel.test`'s network namespace (`network_mode: service:laravel.test`) with its ports hoisted onto the app container — and the isolated `worker` could only reach MinIO via a hardcoded `http://laravel.test:9000` override. Fix: **split endpoint from URL**. `minio` is now a normal networked service (own `networks: [sail]` + own `ports` + `restart`); app restored its `depends_on: minio`; `worker` drops the hardcoded AWS overrides and inherits `.env`. In `.env`/`.env.example`: `AWS_ENDPOINT=http://minio:9000` (SDK API over the sail bridge) + `AWS_URL=http://localhost:9000/${AWS_BUCKET}` (browser-facing signed URLs). Prod profile = `AWS_ENDPOINT=` empty + real `AWS_URL`/external hosts — same compose file, driven by `.env`. No service shares another's namespace; peers resolve each other by DNS name.)*
- [x] **Test:** `StorageDiskConfigTest` — asserts `photos`/`pdfs` S3 disks keep the API endpoint distinct from the browser URL (dev) and serve real S3 URLs with no endpoint override (prod) `StorageDiskConfigTest`
- [x] Point dev object storage at a single browser+container-addressable endpoint; harden photo preview against the unsigned fallback
  > *Until now dev used `AWS_ENDPOINT=http://minio:9000` (SDK) + `AWS_URL=http://localhost:9000` (browser). Because the AWS SDK signs presigned URLs against `AWS_ENDPOINT`, every signed preview/download URL emitted host `minio` — unreachable by the browser without an `/etc/hosts` hack. Fix: set **both** `AWS_ENDPOINT` and `AWS_URL` to the machine's LAN IP (`http://<YOUR-LAN-IP>:9000`), which the app container and the browser both reach (MinIO publishes 9000 on `0.0.0.0`). No `/etc/hosts` entry needed; `minio` no longer leaks into browser URLs. Verified: signed URL host = LAN IP and returns HTTP 200 from both host and container.*
  > *Hardened `DailyReportResource` FileUpload preview: overrode `getUploadedFileUsing()` to ALWAYS emit a signed `temporaryUrl()`. Previously Filament (`BaseFileUpload.php`) silently fell back to unsigned `Storage::url()` if `temporaryUrl()` threw → browsers hit `AccessDenied` on the private object. Now a signing failure throws a clear `RuntimeException`; test asserts the preview URL contains `X-Amz-Signature=` and that `url()` is never used.*
  > *Prod guidance added to `.env.example` + README: real S3 = `AWS_ENDPOINT` empty; self-hosted MinIO behind Caddy = point `AWS_ENDPOINT`/`AWS_URL` at the public host Caddy proxies to MinIO (photo previews need a browser-facing MinIO route — DB/Redis/mail and PDF downloads stay internal).*
- [x] **Clone-and-run deployment:** the repo now self-bootstraps so a fresh clone works with `docker compose up -d --build` (`make up`)
  > *Audit found the old stack could NOT be cloned-and-run: `compose.yaml` built from `./vendor/laravel/sail/runtimes/8.5` (gitignored `vendor/`), baked nothing into an image (app was host bind-mounted at `.:/var/www/html`), and had no first-boot provisioning (no `APP_KEY`, `migrate`, seed, MinIO bucket). Rebuilt as a self-contained app image: `Dockerfile` (root, `FROM sail-8.5/app` = the tracked `docker/8.5` runtime) runs `composer install --no-dev` + `npm run build`; `docker/entrypoint.sh` (root) copies `.env.example`→`.env`, `key:generate`, waits for PG/MinIO, `migrate`, seeds once (`.seeded` marker in the `app-state` volume), and ensures the MinIO bucket via `mc`. `compose.yaml` builds `php-base` (runtime) first, then `laravel.test` (web, serves :80) + `worker` (same image, queue only). `make up` handles the build; `.dockerignore` keeps the image lean; `env.example` (host `127.0.0.1` stale duplicate) removed.*
  > *Dev MinIO host unified on `host.docker.internal:9000` — reachable by the container SDK AND the browser on Docker Desktop, no LAN IP or `/etc/hosts` needed (native Linux: set `AWS_ENDPOINT`/`AWS_URL` to your LAN IP). Docker's embedded DNS special-cases `localhost`→loopback, so a `/etc/hosts` rewrite can't work; `host.docker.internal` is the reliable same-host name. `StorageDiskConfigTest` fixture updated to `host.docker.internal`.*
  > *`fakerphp/faker` moved from `require-dev` to `require` because `db:seed` uses factories (`fake()`) and the prod image builds `--no-dev`; lock regenerated (`--ignore-platform-reqs` — host PHP lacks `ext-iconv`). Verified end-to-end: `docker compose up -d --build` → migrate + Shield seed + Super Admin (`admin@example.com` / `password`), bucket `construction-ops` created, S3 write/read via `host.docker.internal` OK, worker boots clean, `/admin` logs in (`Auth::attempt` true), full Pest suite 135 passed in-container.*

### 7.5 Compose Hardening
- [x] Give `worker` service its own `build:` block so `--build` constructs the image locally
  > *(root cause of clone-and-run failures on other devices with misleading `pull access denied repo does not exist` error: `worker` had only `image: 'construction-ops/app:latest'` and no `build:` block. When the upstream `laravel.test` build failed for any reason, the `construction-ops/app:latest` image never existed locally, so `worker` fell back to a Docker Hub pull that always 404s — masking the real build error. Fix: add the same `build:` block (context `.`, `Dockerfile`, `WWWGROUP`/`WWWUSER` args) to `worker` so `--build` builds it locally too. Layer cache makes the second build near-instant since the context is identical. `worker` never attempts a remote pull again, and any real Dockerfile failure now surfaces clearly. Verified: `docker compose config` valid, `build laravel.test` + `build worker` both succeed, `up -d --build` starts all 6 containers, web migrates + serves on :80, worker boots queue supervisor.)*
- [x] Inline the Sail PHP 8.5 runtime base into `Dockerfile` as a multi-stage `FROM ubuntu:24.04 AS base` build
  > *(root cause of the `failed to resolve source metadata for sail-8.5/app:latest ... dial tcp lookup sail-8.5 no such host` error on other devices: the app `Dockerfile` did `FROM sail-8.5/app AS runtime`, where `sail-8.5/app:latest` is a **locally-built** image tagged by a separate `php-base` compose service from `docker/8.5/Dockerfile`. Compose does NOT serialize builds across `depends_on` — it builds services in parallel, so `laravel.test`/`worker` could start building before `php-base` finished tagging `sail-8.5/app:latest`. When the base image was missing, Docker parsed `sail-8.5/app` as `<registry-host>/<image>` and tried a DNS lookup on host `sail-8.5` → "no such host", masking the real race. First attempted fix: BuildKit `additional_contexts: { base: './docker/8.5' }` + `FROM base` — REJECTED after testing: a named-context directory used in `FROM <name>` gives a filesystem-only "image" (the directory contents), it does NOT build the `Dockerfile` in that directory. Confirmed with a minimal repro (`FROM base` + `--build-context base=./subdir` → `runc: /bin/sh: no such file or directory`, no OS). Final fix: inline `docker/8.5/Dockerfile` content as stage `base` in the main `Dockerfile` (COPY paths adjusted to `docker/8.5/start-container` etc.), then `FROM base AS runtime` for the app layer. Deleted the `php-base` compose service entirely. The build is now fully self-contained: one `Dockerfile`, one `docker compose up -d --build`, no race, no external image lookup, no registry fallthrough. `docker/8.5/Dockerfile` is kept as the source of truth for the base stage — keep the two in sync on Sail bumps (comment at top of `Dockerfile` notes this). Verified: `docker compose config` valid, `build laravel.test` + `build worker` succeed (base stage `[base 1/15]..[base 15/15]` + app stage `[runtime 1/8]..[runtime 8/8]`), `up -d --build --remove-orphans` starts all 6 containers, orphan `php-base` container cleaned up, web provisions (env, key, PG/MinIO wait, migrate, MinIO bucket) + serves HTTP 200 on :80, worker boots queue supervisor.)*
- [x] Make `composer install` / `npm ci` resilient to transient network failures and GitHub API rate limits
  > *(root cause of `target worker failed to solve ... '/bin/sh -c composer install --no-dev --optimize-autoloader --no-interaction --no-scripts && npm ci ...' did not complete successfully: exit code 100` on fresh devices after the build progresses for a while: on a device with no layer cache, `composer install` downloads ~9000 classes worth of zipballs, mostly from GitHub. Two failure modes: (1) composer's default `process-timeout` is 300s — on a slow/unstable link a large zipball download (e.g. `symfony/http-foundation`, `laravel/framework`) takes longer than 300s and composer aborts with exit 100 (we saw this once as a transient HTTP 504 from `api.github.com` mid-build); (2) composer uses the unauthenticated GitHub API to resolve dist URLs — the 60 requests/hour limit is easily exhausted on a fresh device pulling many packages, after which GitHub returns 403 and composer exits 100. The build here succeeded because the base layer was already cached, so `composer install` ran against a warm HTTP cache and never re-downloaded. Fix: in the `Dockerfile` install stage — set `COMPOSER_PROCESS_TIMEOUT=900` (15 min, env var picked up by composer), drop the redundant `--no-interaction` flag (now set globally via `COMPOSER_NO_INTERACTION=1`), add an optional `GITHUB_TOKEN` build arg that, when present, is registered via `composer config --global github-oauth.github.com "$GITHUB_TOKEN"` before install (lifts the rate limit to 5000/hr), wrap `composer install --no-dev --prefer-dist --optimize-autoloader --no-scripts` in a 3-attempt shell retry loop with a 5s backoff between attempts (a single transient 504/503 from GitHub no longer fails the whole build), and pass `--fetch-retries=5 --fetch-timeout=300000` to `npm ci` for the same robustness on the npm side. The `GITHUB_TOKEN` arg is threaded through both `laravel.test` and `worker` build blocks in `compose.yaml` as `GITHUB_TOKEN: '${GITHUB_TOKEN:-}'` so a user can pass `GITHUB_TOKEN=ghp_xxx docker compose build` (or export it in `.env`) when they hit the rate limit. `--prefer-dist` is now explicit (was relying on composer default) to guarantee zipball downloads rather than git clones, which are both faster and not rate-limited via the API the same way. Verified: `docker compose config` valid; full `--no-cache` rebuild of `laravel.test` succeeds (install stage `[runtime 6/8]` completes on attempt 1, 9149 classes generated); `up -d --build --remove-orphans` starts all 6 containers, web HTTP 200, worker boots queue. Note: a successful build on a rate-limited device without a token is still possible thanks to the retry loop + 15-min timeout, but if the user repeatedly hits 403s they should supply `GITHUB_TOKEN`.)*

---

## Phase 8: v0.2.0 — Client Feedback Build

> All tasks below are new for v0.2.0. Commit style: `[Phase 8] <imperative summary>`. Reference `prd-v2.md` v3,
> §4–§8, for exact schema/behavior. Sub-phases are ordered by dependency (schema → engine → UI → removal → payroll → roles),
> but can be parallelized across contributors once 8.1 is merged since most subsequent work reads from its tables.

### 8.1 Milestones & Sub-Jobs (Weighted)
- [x] Migration: add `weight_percentage` (decimal 5,2) to `project_milestones`
- [x] Migration: add `start_date` to `project_milestones` (field not in original PRD; requested in review — milestone form now has Start | Target | Completed; start date guarded ≥ project's `start_date` via `App\Support\ScheduleValidator`, friendly Filament error otherwise, `ProjectMilestoneStartDateTest`)
- [x] Migration: `milestone_sub_jobs` — UUID PK, `project_milestone_id` FK (cascade), `title`, `description` (text), `start_date`, `working_days` (int), `quantity` (decimal 12,2), `weight_percentage` (decimal 5,2), `status` enum, `sort_order`, soft deletes, index `(project_milestone_id, status)`
- [x] `app/Enums/MilestoneSubJobStatus.php` (`pending`, `in_progress`, `completed`, `delayed`)
- [x] `MilestoneSubJob` model — `HasUuids`, `SoftDeletes`, `belongsTo(ProjectMilestone)`
- [x] **Weight validation (app-layer, both levels):** updated to an **incremental model** (from review feedback): individual weights may be any value and need not sum to 100 on each save, but `MilestoneWeightsTotalRule`/`SubJobsWeightsTotalRule` (via `App\Support\WeightValidation` `canAdd()`/`isFull()`) reject any save that pushes the set past 100% and block adding a new row once the siblings already total 100%. Sets build up to exactly 100% and stop there.
  > *(implemented via `app/Support/WeightValidation.php` (`canAdd()`/`isFull()`) wired into the milestone `weight_percentage` field and the `subJobs` Repeater — `MilestoneStartDateRule` guards start ≥ project start, `ScheduleValidator`.)*
- [x] Nested `MilestoneSubJobsRelationManager` (deviation) → relationship-bound `subJobs` **Repeater** inside each milestone's create/edit modal (see note below)
  > *(UI touch-up: the Repeater was moved out of the inline 2-column field grid into its own full-width `Section` ("Sub-Jobs", with a 100%-weight helper note) pinned at the bottom of the milestone edit form — previously it floated awkwardly beside the milestone fields.)*
- [x] **Hard 100% completion enforcement → bell notification:** when any sibling set (project milestones, or a milestone's sub-jobs) is non-empty and totals ≠100, a `WeightIncompleteNotification` (`App\Notifications`) appears in the admin bell (warning style) — created once per project, no duplicate. `App\Services\MilestoneWeightNotificationService::reconcile()` creates it via `ProjectMilestone`/`MilestoneSubJob` model `saved`/`deleted` events and **deletes it the moment every set reaches exactly 100%**. It is a **normal, dismissible** notification (view and close via the Filament X) — no persistence, no re-hydration. `MilestoneWeightNotificationTest`.
  > *(out-of-PRD addition from review — "100% at the end" guided by this reminder; interim saves at partial weights are allowed.)*
- [x] **Test:** weight validation rejects a set that exceeds 100% and blocks adding past a full set; supports partial/incremental weights at both levels (`WeightValidationTest`, `WeightRulesTest`)
- [x] **Test:** sub-job CRUD scoped correctly through the existing milestone/project policies (`MilestoneSubJobPolicy` mirrors `ProjectMilestonePolicy` + `MilestoneSubJobPolicyTest` per role)

### 8.2 Shift-Based Daily Reports & Target Engine
- [x] Migration: add `milestone_sub_job_id` (FK, restrict), `shift` enum (`shift_1`/`shift_2`/`shift_3`), `daily_achievement` (decimal 12,2), `daily_target` (decimal 12,2, system-computed), `delay_reason` (text, nullable) to `daily_reports`
- [x] Migration: change unique index from `(site_id, report_date)` to `(site_id, report_date, shift)`
- [x] App-layer duplicate check updated to the new 3-column key, friendly Filament error retained
- [x] `app/Enums/ReportShift.php`
- [x] Scheduled job: nightly recompute of `daily_target` per open sub-job (`baseline + carried deficit`)
  > *(`app/Services/DeficitCarryForwardService` owns baseline/carried-deficit/target math + warning lifecycle + `runNightlyEvaluation()`; `app/Console/Commands/RecomputeDailyTargets` (`daily-targets:recompute`) scheduled `dailyAt('00:30')` in `routes/console.php`, ordered before shift evaluation.)*
- [x] Target-delay-warning model/table + `first_triggered_at`/`resolved_at`, notification fired only on creation, not on repeat evaluation
  > *(`target_delay_warnings` table (decoupled from §5.3 `sub_job_delay_events`); `TargetDelayWarning` model; `TargetDelayWarningNotification` (mail + database); `evaluateTargetDeficit()` creates on first breach, updates silently on recurrence, resolves when achievement catches up.)*
- [x] **Test:** deficit carry-forward math across consecutive missed days
- [x] **Test:** warning notification fires once on first breach, not again on subsequent still-unresolved evaluations
- [x] **Test:** illegal duplicate `(site_id, report_date, shift)` rejected with friendly error
  > *(`tests/Feature/DeficitCarryForwardTest.php` — baseline, carry-forward, accumulation, reset, warning create/once-only/resolve, same-target-on-both-shifts, 3-column dup rejection. Also updated `DailyReportResourceFormTest`, `DailyReportAutoSaveTest`, `DailyReportPhotoReconciliationTest`, `tests/Support/helpers.php` for the now-required sub-job. 179 tests green.)*

### 8.3 Automated Delay Cascade & Mitigation Workflow
- [x] Migration: `sub_job_delay_events` — UUID PK, `milestone_sub_job_id` FK (cascade), `status` enum (`red`/`yellow`/`green`), `triggered_at`, `mitigation_plan` (text, nullable), `mitigation_submitted_by_user_id` FK (set null), `resolved_at` (nullable), index `(milestone_sub_job_id, status)`
  > *(plus a `delay_days` int snapshot on each event — the cumulative delay at trigger time, used for the cascade delta and displayed in the admin table.)*
- [x] Migration: add `delay_threshold_days` (int, default 2) to `projects`, exposed as an editable field on `ProjectResource`
- [x] Threshold-breach detection job: creates `red` event + shifts all subsequent milestones' dates (by `sort_order`) and `projects.target_end_date` by the delay delta, in one transaction
  > *(`App\Services\DelayCascadeService` — breach = sub-job not completed and days past planned end (`start_date + working_days`) strictly exceed `delay_threshold_days` (PRD "exceeds"); delta = cumulative delay at trigger. `sub-job-delays:detect` artisan command scheduled `dailyAt('00:45')` in `routes/console.php` — ordered after `daily-targets:recompute` (00:30) per the AGENTS.md scheduler-ordering gotcha. Event creation + downstream `target_date`/`target_end_date` shifts are one `DB::transaction`.)*
- [x] `SubJobDelayEvent::submitMitigationPlan()` (→ `yellow`) and `::markRecovered()` (→ `green`) actions; a fresh delay after `green` creates a **new** event back at `red`
  > *(illegal transitions throw `DomainException` — red→green is rejected because a mitigation plan is mandatory before recovery; empty plan rejected; green is terminal. Re-detection while a red/yellow event is active is a no-op, so no duplicate events.)*
- [x] Filament table actions: `Submit Mitigation Plan` (Admin, visible on `red`), `Mark Recovered` (Admin, visible on `yellow`)
  > *(new `SubJobDelayEventResource` — list-only, creation disabled; admin-only mutations via `SubJobDelayEventPolicy`, SE read scoped to assigned projects, client denied; status badge + delay-days + mitigation columns.)*
- [x] **Test:** breach creates event + cascades dates atomically
- [x] **Test:** full 🔴→🟡→🟢 cycle, and reset-to-🔴 on recurrence
- [x] **Test:** illegal transitions rejected — confirmed rule: `red` → `green` rejected (mitigation plan required before recovery)
  > *(`tests/Feature/DelayCascadeTest.php` — breach+cascade delta on subsequent milestones & project end, origin milestone untouched, no-duplicate while active, within-threshold no-breach, completed sub-jobs ignored, full cycle, all illegal transitions, empty plan, recurrence-after-green, per-role policy allow/deny. 192 tests green, pint clean.)*

### 8.4 Client Portal Removal & Emailed PDF Reports
- [x] **Delete** `app/Filament/Client/*`, `ClientPanelProvider`, and the `client` panel registration
  > *(also deleted `App\Http\Middleware\EnsurePasswordChanged` — it only served the client panel's forced-reset flow — and dropped the `client` case from `User::canAccessPanel()`; clients now fail panel access everywhere. `/client/*` routes 404, admin panel still 403 for the client role.)*
- [x] **Delete** `ClientVisibilityTest`, `ClientPortalTest` (or repurpose relevant assertions into new email-delivery tests, per 8.4 tests below)
  > *(portal/login/change-password assertions replaced: `SecurityHardeningTest` client test now asserts `/admin` 403 + `/client/dashboard` 404; `DailyReportResourceAccessTest` + `CrossPhaseIntegrationTest` updated the same way; `PanelLoginTest` client case now asserts the admin panel rejects client credentials; `GeneratedDocumentDownloadTest` client case flipped to deny — clients receive emailed PDFs, not download links.)*
- [x] Retain `clients` table and `UserRole::Client` enum value as record-only; `User::canAccessPanel()` no longer routes clients anywhere
- [x] `app/Jobs/SendClientReportEmailJob.php` (queued) — dispatched from `DailyReport::approveAndPublish()`, replacing the old client notification
  > *(`ReportPublishedNotification` deleted. The job guards `status === published`, resolves Sender/Receiver/CC via `PdfDocumentService::clientEmailConfig()` — per-send overrides → `clients.meta_data.email_delivery` defaults → client `email` fallback — then reuses (or first creates) the DailyProgress `GeneratedDocument` and sends `App\Mail\DailyReportPublished` with the PDF attached from the `pdfs` disk (`Attachment::fromStorageDisk`, never bytes on the queue). Transport unchanged (Mailpit); real SMTP stays an `.env`-only swap.)*
- [x] Filament form for Admin to configure Sender/Receiver/CC per send (or saved defaults per client/project)
  > *(saved defaults per client: a "Report Email Delivery Defaults" section on `ClientResource` storing under `meta_data.email_delivery` (`sender_email`, `sender_name`, `receivers`, `cc` as tags). Per-send overrides supported via `SendClientReportEmailJob` constructor args.)*
- [x] Mail transport left on Mailpit/dummy for this phase — do not wire a real SMTP/paid provider without separate approval
- [x] `ReportDataDTO`: add extensible `sections` array (`type` + `payload`), Blade template skips unrecognized types gracefully
  > *(`forDailyReport` reads `meta_data['sections']`; `pdf/daily-progress` renders known `text`/`table` types and skips the rest.)*
- [x] Remove worker-allocation content from `daily-progress.blade.php`; add new `worker-allocation-payroll.blade.php` (or similarly named) template, serving both payroll and HRD needs
  > *(new `DocumentType::WorkerAllocationPayroll` + `ReportDataDTO::forWorkerAllocation` + `PdfDocumentService::queueWorkerAllocation` (published-reports window, deduped like the other document types); payroll columns from `worker_attendance` get appended in 8.5.)*
- [x] Document the removal explicitly in this file's Phase 8 entry (matching the project's existing pattern for documenting reversals, see Phase 7.1/7.4 entries above) once merged
- [x] **Test:** `Mail::fake()` — correct recipients (Sender/Receiver/CC) and correct PDF attached, dispatched only on `published`, never intermediate states
  > *(`ClientReportEmailTest` — configured receivers + CC + PDF filename attachment, client-email fallback, non-published skip, GeneratedDocument reuse/creation, PDF render sanity; `DailyReportNotificationsTest` reworked to `Bus::fake()` asserting `SendClientReportEmailJob` on publish only and not on revision/resubmit/illegal draft publish; `CrossPhaseIntegrationTest` ends at client email now.)*
- [x] **Test:** deleted client panel routes return 404/no route, not a broken auth redirect
  > *(covered in `SecurityHardeningTest`, `DailyReportResourceAccessTest`, `CrossPhaseIntegrationTest`.)*

### 8.5 Worker Profile, Attendance & Payroll
- [x] Migration: add `active_start_date`, `deactivation_date`, `bank_account_number`, `bank_account_name`, `phone_number` to `workers`
  > *(all nullable; `down()` drops them — `2026_09_03_000000_add_payroll_fields_to_workers_table.php`)*
- [x] Migration: `worker_attendance` — UUID PK, `worker_id` FK (restrict), `site_id` FK (restrict), `recorded_by_user_id` FK (set null), `attendance_date`, `hours_worked` (decimal 4,2, default 8), `overtime_hours` (decimal 4,2, default 0), `photo_file_path`, `photo_thumbnail_path`, `captured_at`, `meta_data` (jsonb), soft deletes, unique-ish index `(worker_id, attendance_date)`, index `(site_id, attendance_date)`
  > *(`2026_09_03_000001_create_worker_attendance_table.php`. Unique-ish = partial unique index `(worker_id, attendance_date) WHERE deleted_at IS NULL` named `worker_attendance_worker_date_active_unique`, same pattern as the daily_reports one. `photo_file_path`/`captured_at` nullable at the schema level — the camera-only enforcement and required-photo rule land with the HRD form in 8.6.)*
- [x] Migration: `payroll_runs` — UUID PK, `period_start`, `period_end`, `status` enum, `generated_by_user_id`/`approved_by_user_id` FKs (set null), soft deletes, index `(period_start, period_end)`
- [x] Migration: `payroll_items` — UUID PK, `payroll_run_id` FK (cascade), `worker_id` FK (restrict), `regular_hours_total`, `overtime_hours_total`, `regular_pay`, `overtime_pay`, `total_pay`, unique-ish index `(payroll_run_id, worker_id)`
  > *(plain non-unique index `(payroll_run_id, worker_id)` per PRD §4; the per-run uniqueness is app-enforced by the one-item-per-worker generation loop. `created_at`/`updated_at` only, no soft deletes — PRD §4 omits them for this child table.)*
- [x] `app/Enums/PayrollRunStatus.php` (`draft`, `pending_review`, `approved`, `paid`)
  > *(backed enum with `label()` + `lang/{en,id}/enum.php` `payroll_run_status` entries; consumed by `PayrollRunPolicy`, `PayrollRunResource` badges/filters.)*
- [x] Overtime calculation: `hourly_rate = daily_rate / 8`; `overtime_pay = hourly_rate × overtime_hours` (confirm whether the "8" is hardcoded or should read from `STANDARD_WORKDAY_HOURS` env — lean toward the env-configurable version for consistency with the delay-threshold pattern)
  > *(env-configurable chosen: `config/payroll.php` `standard_workday_hours` ← `STANDARD_WORKDAY_HOURS` (default 8), already stubbed in `.env.example`. All money math via bcmath (`bcdiv`/`bcmul`/`bcadd`, no floats) — `ext-bcmath: *` added to `composer.json` require (already present in the Sail image; noted here per AGENTS dependency rule). `PayrollService::hourlyRate()`; regular_pay is pro-rated the same way: `hourly_rate × regular_hours_total`, so partial days (e.g. 6h) pay 6/8 of the daily rate. Workers with no `daily_rate` accrue zero pay but keep their hours on the item. `PAYROLL_CYCLE_ANCHOR` (default `2026-01-01`) added to `.env.example` alongside the already-stubbed `PAYROLL_CYCLE_DAYS`/`STANDARD_WORKDAY_HOURS`.)*
- [x] Scheduled job: every 14 days, open a `payroll_runs` row for the period and generate one `payroll_items` row per active worker from `worker_attendance` in that window
  > *(`payroll:generate` command + `PayrollService`. Cycle boundary = `cycle_anchor_date + N × cycle_days` — the command self-gates and no-ops on any other day, so it's scheduled `dailyAt('01:15')` (after `sub-job-delays:detect` 00:45) and only fires on day 1 of each 14-day cycle, generating the previous window `[today - 14, today - 1]`. Idempotent per `(period_start, period_end)` — re-run returns the existing run. Items are generated per worker **with attendance in the window** (a worker who never showed up gets no zero-pay row); workers soft-deleted after the period are still paid (`withTrashed`). `--start/--end` flags allow manual backfill; mismatched flags fail. Generation (run + items) is one `DB::transaction`. Scheduled via `routes/console.php`.)*
- [x] Filament `PayrollRunResource` (Admin) for review/approve
  > *(`Finance` nav group; index + view pages, no create/edit — runs are system-generated and immutable once created. `PayrollRunPolicy`: Admin only (SE/Client denied incl. by-UUID guess); transition abilities gate on both role and current status. `Submit for Review`/`Approve`/`Mark Paid` actions wired to the model state-machine methods, visible per status. `PayrollItemsRelationManager` shows hours totals + money columns. `HRD` role/policy scoping lands in 8.6.)*
- [x] **Test:** regular/overtime pay derivation from a seeded attendance fixture across a 14-day window
  > *(`PayrollCalculationTest`: 10 days × 8h + 60h OT at 1,600,000/day → 16,000,000 regular + 12,000,000 OT; partial-day pro-rating (6h = 600,000 at 800,000/day); env-configurable workday (`standard_workday_hours = 10`); zero-attendance worker excluded; soft-deleted worker still paid; idempotency; cycle-boundary/no-op/backfill/mismatched-flags command tests. 13 tests.)*
- [x] **Test:** `worker_attendance` unique constraint `(worker_id, attendance_date)` enforced with friendly error
  > *(`WorkerAttendanceDuplicateTest`: `AttendanceService::record` throws `ValidationException` with "already been recorded" (the 8.6 HRD form will surface it as a field error); raw duplicate insert trips the partial unique index (`UniqueConstraintViolationException`); soft-deleted row can be re-recorded; different workers same date + same worker different dates both allowed. 6 tests.)*
- [x] Bonus (AGENTS.md state-machine/policy mandates): `PayrollRunStateMachineTest` (legal walk draft→pending_review→approved→paid incl. `approved_by_user_id` audit stamp; 7 illegal-transition tests — paid is terminal) and `PayrollRunPolicyTest` (admin ok; SE/client denied list + by-UUID; create forbidden; transition abilities gated by status × role). Full suite 219 passed / 639 assertions, pint PASS (223 files).
- [x] Bonus (deferred from 8.4): `worker-allocation-payroll` PDF now renders the payroll pay breakdown — `ReportDataDTO::forWorkerAllocation` gains an optional `?PayrollRun` that queues safe `payrollItems` + `payrollTotal` rows (soft-deleted workers resolved via `withTrashed`), `PdfDocumentService::queueWorkerAllocation` passes it through, template section + `lang/{en,id}/pdf.php` keys added (`WorkerAllocationPayrollPdfTest`: DTO derivation, section omitted without a run, DTO passed to the Blade view — per AGENTS, never asserting PDF bytes). Also fixed the latent 8.4 bug phpstan caught: `GeneratePdfJob::filename()` didn't handle the `WorkerAllocationPayroll` enum case (match would throw on that document type).
- [x] phpstan: 7 errors remain, **all pre-existing** (baseline run on pre-8.5 HEAD = 9 errors; 8.5 introduced zero, fixed two drive-bys in `PdfDocumentService::clientEmailConfig` + `GeneratePdfJob::filename`). Remaining are Phase 3–8.3 files (`ProjectMilestonesRelationManager`, `MilestoneSubJob`, `SubJobDelayEvent`, `MilestoneWeightsTotalRule`, `LocaleContext`, `ScheduleValidator`) — cleanup deferred to the 8.7 pass.

### 8.6 HRD Role & Camera-Only Capture (SE + HRD)
- [x] Add `hrd` to `UserRole`
  > *(backed enum with `HasLabel` (`enum.user_role.*` in `lang/{en,id}/enum.php`); `users.role` CHECK constraint widened via `2026_09_06_000001_add_hrd_to_users_role_check` (drop+re-add `users_role_check`, reversible); `User::canAccessPanel` already admits any non-Client role onto the admin panel; `UserFactory::hrd()`, `UserSeeder` HRD user (`hrd@example.com`/`password`), `RolePermissionSeeder` maps `hrd` → empty-permission Spatie role (access is policy-gated, not Shield-permission-gated); `UserResource` badge color.)*
- [x] `WorkerAttendancePolicy` — HRD full CRUD scoped to their own submissions (or all, per confirmed scope); Admin full; SE/Client no access
  > *(confirmed scope: ALL records — HRD manages attendance regardless of recorder; allow-list `in_array($user->role, [Admin, Hrd])` on every ability; SE/Client denied incl. by-UUID guess.)*
- [x] HRD-scoped Filament access — either a dedicated `HrdPanelProvider` or a tightly scoped set of resources on the existing admin panel gated by the policy (pick based on how distinct the HRD UI needs to be; default to reusing the admin panel with policy-gated navigation unless the client wants a visually separate HRD portal)
  > *(reusing the admin panel per TASKS default. Policies deny HRD `viewAny`/`view` on DailyReport, Project, Site, ProjectMilestone, MilestoneSubJob, SubJobDelayEvent (viewAny switched from `!== Client` to an explicit Admin+SE allow-list), PayrollRun (admin-only already); scoped queries return `whereRaw('1 = 0')` so nav lists are empty; `User::canAccessPanel` unchanged. HRD sees only Workers (read, for the picker) + Worker Attendance.)*
- [x] Custom live-capture Livewire/Alpine component (native `<input capture>`/`getUserMedia`) — no gallery/file-picker path — shared between HRD attendance and SE progress photos
  > *(`App\Filament\Components\LiveCapture` form field + `filament/forms/components/live-capture` Blade view + `filamentLiveCapture` Alpine component registered via the admin panel `BODY_END` render hook (`resources/views/filament/live-capture-scripts.blade.php`; v3.3.54 has no `SCRIPTS_END` hook). `getUserMedia` rear-camera stream → canvas snap → upload via `$wire.upload()`; fallback `<input type="file" accept="image/jpeg" capture="environment">` rendered ONLY when `getUserMedia` is unavailable (forces the camera app on mobile). The shutter timestamp is embedded in the upload's filename (`capture-<ISO 8601>.jpg`) at capture time. Admin keeps unrestricted device upload via `FileUpload` on the SE pair form (PRD §6.4 "Admin remains unrestricted") — rendered instead of the camera fields, same state keys.)*
- [x] Wire SE's `daily_report_photos` form to the 3-column **Before | After | Description** layout using the new component, capped at exactly one pair per shift
  > *(Section with a 3-column Grid: Before | After | Description. Admin sees two single-file `FileUpload`s + description instead (device upload allowed). Pair cap = partial unique index `(daily_report_id) WHERE deleted_at IS NULL` + one-row-per-report sync in the Create/Edit pages; recapture updates the same row in place. Submission guard: `Submit for Approval` action blocks with a danger notification until the row has both `before_file_path` and `after_file_path` (draft autosave still works before photos exist — photo fields are optional at form level, enforced at the submit transition).)*
- [x] Wire HRD's `worker_attendance` form to the same component for its single attendance photo
  > *(`WorkerAttendanceResource` — new, "Human Resources" nav group; worker/site pickers, date, hours, overtime + one required live capture. Duplicate guard surfaces the friendly error on the form (`data.attendance_date`), photos stored via the shared `PhotoCaptureService` into `worker-attendance-photos/`, `recorded_by_user_id` + `captured_at` + `meta_data.capture` stamped. Edit allows recapture, no-op when the photo is unchanged.)*
- [x] Embed capture timestamp/metadata automatically into `captured_at` (and `meta_data` where richer EXIF is available) on both entities
  > *(the Alpine component stamps `capture-<ISO>` into the filename at shutter time; `LiveCapture::resolveState()` extracts it (falling back to `now()`) and pages persist it to `captured_at` — daily report pair rows and `worker_attendance` rows — plus `meta_data.capture = {captured_at, method: 'live', recorded_by_user_id}` on attendance. No browser-writable EXIF is produced, so `meta_data` carries the richer capture record.)*
- [x] Server-side secondary check on embedded capture metadata (presence/recency), independent of the client-side restriction
  > *(`App\Rules\LiveCapture` — `InvokableRule`; parses the embedded shutter timestamp from the filename (live `TemporaryUploadedFile`, `livewire-file:` token, or plain stored path) and rejects missing / future-beyond-skew / older-than-window captures (`config/capture.php`: `LIVE_CAPTURE_MAX_AGE_SECONDS=900`, `LIVE_CAPTURE_FUTURE_SKEW_SECONDS=120`, stubbed in `.env.example`); applied to both SE photo fields and the HRD attendance field. Already-stored paths re-validated on edit pass through (they were checked at capture time). Photo storage itself generalized: `PhotoCaptureService` (shared, MIME-sniffed, thumbnails) with `DailyReportPhotoService` as the SE-directory subclass.)*
- [x] **Test:** HRD cannot access `daily_reports`, projects, or milestones; SE cannot access `worker_attendance`
  > *(`HrdRbacTest` — panel access, policy allow/deny per ability incl. by-UUID view denials, `/admin/daily-reports/{uuid}/edit` → 404 for HRD, `/admin/worker-attendances` → 403 for SE, HRD↔recorder-independence, payroll/milestone/sub-job/delay-event denials, Livewire list scoping both ways.)*
- [x] **Test:** photo capture component rejects/blocks a gallery-sourced file where feasible to assert in a feature test; otherwise assert the UI never renders a file-picker path for SE/HRD
  > *(`CameraCaptureTest` + `DailyReportResourceFormTest`: SE/HRD forms render `fi-fo-live-capture` and the only file input carries `capture="environment"` (native camera fallback — never a gallery picker); the recency rule rejects a stale/missing/future `capture-<ISO>` filename, which is what a gallery-named upload without a live stamp would trip.)*
- [x] **Test:** SE before/after pair capped at one per shift; a second submission attempt for the same shift is rejected
  > *(`CameraCaptureTest`: partial unique index trips a second pair row (savepoint-wrapped assertion); submit-guard test proves a draft without a saved pair is blocked and submits once the pair is saved. `DailyReportPhotoReconciliationTest` rewritten for the pair model (one row, in-place recapture, description updates, missing-pair warnings, `photos:prune`).)*
  > *(Schema: `2026_09_06_000000_convert_daily_report_photos_to_before_after_pairs` — renames `file_path`→`before_file_path`, `thumbnail_path`→`before_thumbnail_path`, `caption`→`description`, adds nullable `after_file_path`/`after_thumbnail_path`/`captured_at` (nullable columns instead of PRD's non-null `after_file_path` so the migration is reversible on live v0.1.0 data without backfill; the app layer + submit guard enforce presence), dedupes legacy multi-upload rows to the newest per report, then creates the partial unique index; `down()` restores the v0.1.0 shape. Downstream: `DailyReportPhoto` model (before/after signed URLs), `ReportDataDTO::forDailyReport` photo pairs, `daily-progress.blade.php` Before|After grid + description + `pdf.photo_before/after` labels (en/id), `DailyReport` revision-snapshot `photo_paths` merge, `photos:prune` before-file check. `UserResource`/`UserRole` label localization. Full suite 247 passed / 765 assertions, pint PASS (240 files), phpstan 7 pre-existing errors (zero new).)*

### 8.7 Final v0.2.0 Test Suite & Regression Pass
- [x] Run full `pest` suite — all green, including all new Phase 8 tests above
  > *(247 passed / 765 assertions on a fresh `migrate:fresh --seed`, green across repeated runs. Note: the durable compose image bakes `--no-dev` vendor, so pest/pint/phpstan binaries only exist after copying the host `vendor/` in — `docker compose cp ./vendor/. laravel.test:/var/www/html/vendor/`.)*
- [x] Run `pint` — zero formatting issues
  > *(PASS — 158 files on host, 240 in-container; drive-by fixes to 4 Phase 8.6 test files: FQCN→import + import ordering.)*
- [x] Run `phpstan analyse` — no new errors introduced
  > *(better than asked: the 7 pre-existing errors from 8.3/8.5 are now FIXED — `[OK] No errors`. `MilestoneSubJob` parent lookup now uses an explicit `ProjectMilestone::withTrashed()` query instead of `BelongsTo->__call` forwarding; `SubJobDelayEvent` + `LocaleContext` dropped `?->` before `??` (redundant — `??` already has isset-semantics, verified with a minimal phpstan repro); `MilestoneWeightsTotalRule` gained a loop `@var` annotation; `ScheduleValidator` uses native `DateTimeInterface` comparison instead of Carbon-only `lt()`; `ProjectMilestonesRelationManager` dropped a nullsafe on a non-nullable column.)*
- [x] Regression: re-run relevant Phase 1–7 suites (RBAC, state machine, PDF, localization, deployment config) to confirm nothing broke from the client-portal removal or schema changes
  > *(73 targeted tests across all `*Policy`, `*StateMachine`, `Pdf*`, `Locale*`, `Storage*` suites green; `CrossPhaseIntegrationTest` covers setup→submit→approve→publish→PDF→client email end-to-end.)*
- [x] Manual smoke test: admin sets up a project with weighted milestones/sub-jobs → SE submits shift reports across days until a deficit accumulates → warning fires once → delay threshold breached → cascade shifts dates → admin submits mitigation plan → recovery → admin approves a report → client receives emailed PDF (check Mailpit) → HRD records attendance via live camera → payroll run generates correct pay
  > *(automated as a one-off tinker E2E script against the dev DB (`/tmp/e2e-smoke.php` in the container): 60/40 milestones + 100% sub-job → baseline 20/day → shift reports carry deficit 8→16 → `TargetDelayWarning` fires exactly once (`first_triggered_at` stable, no re-notify on re-evaluation) → catch-up target 36 resolves it → 4-day breach (> 2d threshold) creates the `red` event and shifts project end + downstream milestone atomically → mitigation → yellow → recovered → green → recurrence creates a fresh `red`, `red→green` rejected → today's report published → Mailpit shows per-published-report client emails (~1.2 MB PDF attached, correct Sender/recipient) → HRD capture pipeline: recency rule accepts a fresh `capture-<ISO>` upload, rejects a stale one, photo stored, `captured_at` + recorder stamped, duplicate attendance rejected with the friendly error → RBAC spot-checks (HRD sealed out of reports, SE out of attendance/payroll) → payroll derives 1,600,000 regular + 400,000 OT from `worker_attendance`, idempotent per period, absent workers excluded. The only step a script cannot drive is the browser `getUserMedia` camera UI itself.)*
- [x] Dependency sanity across Phase 8 (AGENTS boundary check): `composer.json` delta vs pre-Phase-8 is exactly `ext-bcmath` (documented in 8.5); `package.json` + npm lock unchanged; `composer.lock` refreshed to match (content-hash + `ext-bcmath` platform req) and security-patched: `league/commonmark` 2.9.1→2.10.0 (GHSA-8rr7-cvq3-gmfh, high-severity DoS) + transitive `nette/schema` 1.3.6 — `composer audit` now clean; no new packages.

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
