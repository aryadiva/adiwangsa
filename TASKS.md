# Development Tasks — Construction Operations Dashboard

> **Reference:** `prd-v2.md` (v2 PRD) is the source of truth. `AGENTS-v2.md` is the fast-reference.
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
  > *(implemented as `ProjectResource/RelationManagers/ProjectMilestonesRelationManager.php` per AGENTS-v2.md "ProjectMilestoneResource as a relation manager on ProjectResource" + PRD §6.2 — nested, not a standalone top-level resource)*
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
