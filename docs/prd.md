# Technical Product Requirement Document (PRD) & Architecture Blueprint (v2)

**Project Name:** Construction Operations & Back-Office Management Dashboard
**Target Architecture:** Monolithic Laravel + Filament PHP (TALL Stack)
**Database Engine:** PostgreSQL
**Primary Purpose:** AI Coding Agent Execution Context & Implementation Reference

> **Changelog from v1:** Added milestones table, soft-deletes, audit logging, report revision history, queued PDF generation, file storage strategy, notification system, offline/mobile guidance, non-functional requirements, indexing strategy, and a testing/security section. See inline `NEW`/`CHANGED` markers.

---

## 1. System Overview & Objectives

### 1.1 Goal
Migrate manual, error-prone construction consultancy operations (Excel spreadsheets, WhatsApp threads, fragmented site photos) into a centralized, role-based Web Dashboard.

### 1.2 Core Scope
* **Project Management:** Project tracking, site locations, milestones, and status indicators.
* **Worker & Resource Directory:** Assignment of worker pools and daily labor allocations per site.
* **Daily Site Reporting:** Digital log submission by Site Engineers with auto-saving drafts, review queues, and photo uploads.
* **Paperwork Engine:** Data compilation and dynamic PDF generation for standard industry documents (Daily Progress Reports, Weekly Summaries, Attendance Rosters).
* **Client Visibility:** Read-only portal for project owners/contracted clients to track site activity without access to administrative controls.

### 1.3 Deployment Assumption `NEW`
This spec assumes a **single-tenant internal tool** (one consultancy, many clients/projects) rather than multi-tenant SaaS. If multiple consultancies will share this instance, a `tenant_id` column and scoping layer must be added to every table in Section 4 before build — flag this explicitly to stakeholders before Phase 1 starts, since it changes the RBAC and query-scoping design materially.

---

## 2. Tech Stack Specification

| Component | Technology | Version / Details |
| :--- | :--- | :--- |
| **Framework** | Laravel | v11.x / v12.x |
| **Admin UI Framework** | Filament PHP | v3.x |
| **Reactivity Layer** | Laravel Livewire | v3.x |
| **Styling & Interactivity** | Tailwind CSS + Alpine.js | Filament Native Bundling |
| **Database** | PostgreSQL | v15+ (Using native `jsonb` capabilities) |
| **PDF Generation Engine** | Spatie Laravel-PDF or Dompdf | HTML/Blade-to-PDF rendering |
| **Authentication/RBAC** | Filament Shield / Spatie Permission | Role-based policies |
| **Queue / Background Jobs** `NEW` | Laravel Queues (Redis driver) | PDF generation, notifications, image processing |
| **File Storage** `NEW` | Laravel Filesystem — S3-compatible (e.g. AWS S3, DigitalOcean Spaces, MinIO for local dev) | Photos, generated PDFs |
| **Image Processing** `NEW` | Intervention Image | Thumbnail generation, EXIF-based orientation fix, compression for mobile-uploaded photos |
| **Audit Logging** `NEW` | Spatie Laravel-Activitylog | Tracks who changed what, when, across models |
| **Notifications** `NEW` | Laravel Notifications (mail + database channel, Filament notification bell) | Status-change alerts |

---

## 3. User Roles & Access Control Matrix (RBAC)

```
[System User]
      │
      ├── Admin / Consultant ──► [Full Read/Write, Approval Queue, PDF Exports, System Config]
      │
      ├── Site Engineer ───────► [Scoped CRUD: Assigned Sites, Daily Logs (Draft/Submit)]
      │
      └── Client User ─────────► [Scoped Read-Only: Assigned Projects, Published Reports]
```

### 3.1 Role Capabilities

#### Admin / Consultant (Super User)
* Full access to all CRUD resources across all projects, sites, workers, and reports.
* Exclusive privilege to change report statuses to `published` or request revisions.
* Ability to trigger PDF paperwork generation and system-wide exports.
* Manages user onboarding and site assignments.
* Can view full audit trail / activity log per report. `NEW`

#### Site Engineer (Field User)
* Restricted write access: Can only create/update `daily_reports` for explicitly assigned project sites.
* Can create and save reports in `draft` status or advance them to `need_approval`.
* Uploads site photos and inputs worker counts.
* Cannot publish reports or modify system configuration.
* Receives a notification when a report is approved or sent back for revision. `NEW`

#### Client User (External Stakeholder)
* Strict **Read-Only** access scoped strictly to projects linked to their client record.
* Can view **ONLY** reports with `published` status (cannot view `draft`, `need_approval`, or `revision_requested`).
* Can download generated client-facing PDFs.
* Receives a notification (email) when a new report is published for one of their projects. `NEW`

---

## 4. PostgreSQL Database Schema Specification

```
[clients] 1 ─── N [projects] 1 ─── N [sites] 1 ─── N [daily_reports] 1 ─── N [daily_report_photos]
                       │               │                    │
                       │               │                    ├── N [daily_report_workers] ── N [workers]
                       │               │                    └── N [daily_report_revisions]      (NEW)
                       ├── N [project_milestones]            (NEW)
                       └── N [project_user] ── N [users]

[activity_log] ── polymorphic, references any model above       (NEW)
```

### 4.0 Schema-Wide Conventions `NEW`
* All tables use **soft deletes** (`deleted_at`, nullable timestamp) except pure pivot tables (`project_user`) — construction records should never be hard-deleted for audit/liability reasons.
* All FKs that reference "the acting user" (`created_by_user_id`, `reviewed_by_user_id`, etc.) use `On Delete Set Null` so historical records survive user deactivation.
* Timestamps are stored in UTC; display conversion happens at the presentation layer (Filament) using the project's configured timezone (add `timezone` string column to `projects`, default `UTC`).

### 4.1 Table Definitions & Relationships

#### `users`
* `id` (UUID / Primary Key)
* `name` (String)
* `email` (String, Unique)
* `password` (String)
* `role` (Enum: `admin`, `site_engineer`, `client`)
* `is_active` (Boolean, Default: `true`) `NEW` — deactivate without deleting
* `created_at`, `updated_at`, `deleted_at` (Timestamps)

#### `clients`
* `id` (UUID / Primary Key)
* `user_id` (FK -> `users.id`, Nullable - links client portal user account)
* `company_name` (String)
* `contact_person` (String)
* `email` (String)
* `phone` (String)
* `meta_data` (JSONB, Default: `{}`)
* `created_at`, `updated_at`, `deleted_at` (Timestamps)

#### `projects`
* `id` (UUID / Primary Key)
* `client_id` (FK -> `clients.id`, On Delete Restrict)
* `name` (String)
* `code` (String, Unique - e.g., `PRJ-2026-001`)
* `status` (Enum: `planning`, `active`, `on_hold`, `completed`)
* `start_date` (Date)
* `target_end_date` (Date, Nullable)
* `budget` (Decimal: 15, 2, Nullable)
* `timezone` (String, Default: `UTC`) `NEW`
* `meta_data` (JSONB, Default: `{}`)
* `created_at`, `updated_at`, `deleted_at` (Timestamps)
* **Index:** `(client_id, status)` `NEW`

#### `project_milestones` `NEW — table did not exist in v1 despite being in scope`
* `id` (UUID / Primary Key)
* `project_id` (FK -> `projects.id`, On Delete Cascade)
* `title` (String)
* `description` (Text, Nullable)
* `target_date` (Date)
* `completed_at` (Date, Nullable)
* `status` (Enum: `pending`, `in_progress`, `completed`, `delayed`)
* `sort_order` (Integer, Default: `0`)
* `created_at`, `updated_at`, `deleted_at` (Timestamps)
* **Index:** `(project_id, status)`

#### `sites`
* `id` (UUID / Primary Key)
* `project_id` (FK -> `projects.id`, On Delete Cascade)
* `name` (String - e.g., "Block A Foundation")
* `address` (Text, Nullable)
* `latitude` (Decimal: 10, 7, Nullable) `CHANGED — replaces ambiguous "Point / String"`
* `longitude` (Decimal: 10, 7, Nullable) `CHANGED`
* `created_at`, `updated_at`, `deleted_at` (Timestamps)

#### `project_user` (Pivot Table for Scoped Engineer Assignment)
* `project_id` (FK -> `projects.id`, On Delete Cascade)
* `user_id` (FK -> `users.id`, On Delete Cascade)
* **Primary Key:** (`project_id`, `user_id`)

#### `workers`
* `id` (UUID / Primary Key)
* `full_name` (String)
* `trade_skill` (String - e.g., "Mason", "Electrician", "General Laborer")
* `daily_rate` (Decimal: 10, 2, Nullable)
* `is_active` (Boolean, Default: `true`)
* `meta_data` (JSONB, Default: `{}`)
* `created_at`, `updated_at`, `deleted_at` (Timestamps)

#### `daily_reports`
* `id` (UUID / Primary Key)
* `site_id` (FK -> `sites.id`, On Delete Restrict)
* `created_by_user_id` (FK -> `users.id`, On Delete Set Null)
* `reviewed_by_user_id` (FK -> `users.id`, Nullable, On Delete Set Null)
* `report_date` (Date)
* `weather_condition` (Enum: `sunny`, `rainy`, `cloudy`, `stormy`)
* `work_summary` (Text)
* `delays_or_issues` (Text, Nullable)
* `status` (Enum: `draft`, `need_approval`, `published`, `revision_requested`, Default: `draft`)
* `admin_notes` (Text, Nullable)
* `meta_data` (JSONB, Default: `{}` - flexible storage for moisture %, safety incidents, custom metrics)
* `created_at`, `updated_at`, `deleted_at` (Timestamps)
* **Index:** `(site_id, report_date)` unique-ish (one report per site per day, enforce at app layer or DB constraint), `(status)`, `(report_date)` `NEW`

#### `daily_report_revisions` `NEW — preserves history each time a report is edited after "revision_requested"`
* `id` (UUID / Primary Key)
* `daily_report_id` (FK -> `daily_reports.id`, On Delete Cascade)
* `snapshot` (JSONB — full field snapshot of the report at time of resubmission)
* `edited_by_user_id` (FK -> `users.id`, On Delete Set Null)
* `created_at` (Timestamp)
* Written automatically whenever a report transitions `revision_requested` → `need_approval`.

#### `daily_report_photos`
* `id` (UUID / Primary Key)
* `daily_report_id` (FK -> `daily_reports.id`, On Delete Cascade)
* `file_path` (String — object storage key, not local path) `CHANGED`
* `thumbnail_path` (String, Nullable) `NEW`
* `caption` (String, Nullable)
* `file_size_bytes` (Integer, Nullable) `NEW`
* `created_at`, `updated_at`, `deleted_at` (Timestamps)

#### `daily_report_workers` (Pivot / Allocation Detail)
* `id` (UUID / Primary Key)
* `daily_report_id` (FK -> `daily_reports.id`, On Delete Cascade)
* `worker_id` (FK -> `workers.id`, On Delete Restrict)
* `hours_worked` (Decimal: 4, 2, Default: 8.00)
* `remarks` (String, Nullable)

#### `activity_log` `NEW — provided by spatie/laravel-activitylog`
* Standard package schema: polymorphic `subject`, `causer`, `event`, `properties` (JSONB diff of old/new values).
* Used to answer "who changed this report's status, and when" without bloating `daily_reports` itself.

---

## 5. Daily Report State Machine & Approval Workflow

```
       [Site Engineer Types]
                 │
                 ▼
          ┌─────────────┐
          │    DRAFT    │◄─── Auto-saved every 10s via Livewire polling
          └──────┬──────┘
                 │
                 │ (Action: Submit for Approval)
                 ▼
       ┌──────────────────┐
       │  NEED_APPROVAL   │
       └────────┬─────────┘
                │
        ┌───────┴─────────────────┐
        │                         │
 (Admin Approves)        (Admin Requests Changes)
        │                         │
        ▼                         ▼
┌───────────────┐     ┌──────────────────────┐
│   PUBLISHED   │     │  REVISION_REQUESTED  │
└───────────────┘     └──────────┬───────────┘
        │                        │
  (Visible to                    └──► (Site Engineer edits — snapshot
   Client User,                        written to daily_report_revisions —
   notification fired)                 and re-submits to NEED_APPROVAL)
```

### State Definitions
1. **Draft:** Created by Site Engineer. Auto-saves continuously. Editable only by author or admin. **Hidden from Clients.**
2. **Need Approval:** Submitted by Site Engineer upon completion. Read-only for Engineer while under review. Admin gets a notification. `CHANGED`
3. **Revision Requested:** Flagged by Admin with feedback in `admin_notes`. Unlocks editing for the Site Engineer. Engineer gets a notification. `CHANGED`
4. **Published:** Approved by Admin. Locked from editing. **Visible to Client Users**, PDF generation unlocked, client notified. `CHANGED`

### 5.1 Auto-Save Resilience `NEW`
Because Site Engineers often work from mobile devices on unreliable site connectivity:
* The edit form should hold state client-side (Alpine.js store) and retry the `wire:poll` save on reconnect rather than silently failing.
* Show a persistent "Unsaved changes — retrying" banner if a save request fails, not just a last-saved timestamp.
* Full offline-first (service worker / local-first sync) is out of scope for v1 but should be flagged as a fast-follow if field connectivity issues surface post-launch.

---

## 6. Filament PHP UI/UX Implementation Requirements

### 6.1 Form Auto-Save (Draft State)
* The `DailyReportResource` edit form MUST utilize Livewire polling (`wire:poll.10s="saveDraft"`) or Alpine-assisted debounce listeners to auto-persist form states to PostgreSQL when `status === 'draft'`.
* Display a visual indicator in the Filament topbar: `"Draft Saved automatically at HH:mm:ss"`, with a retry/error state per Section 5.1.

### 6.2 Key Form Schema Controls
* **Site Picker:** Filtered by sites belonging to projects assigned to the logged-in user (`project_user`).
* **Worker Allocations:** Implemented using Filament `Repeater` component mapping to `daily_report_workers`.
* **Photo Uploads:** Filament `FileUpload` supporting multiple image uploads (`image/*`), max file size enforced server-side (e.g. 10MB/photo), auto-generating responsive thumbnails via Intervention Image, storing paths in `daily_report_photos` on S3-compatible storage. `CHANGED`
* **Flexible Fields (`JSONB`):** Filament `KeyValue` or `Group` component binding directly to `meta_data` for dynamic client-specific properties.
* **Milestone Tracker:** New `ProjectMilestoneResource` (nested/relation manager on `ProjectResource`) using Filament `Repeater` or a dedicated table with a progress-status badge column. `NEW`

### 6.3 Table Resource Filters & Actions
* **Admin Dashboard:** Include a badge tab filter for `Need Approval` items with a count indicator.
* **Header / Table Actions:**
  * `Approve & Publish` (Admin only, visible on `need_approval` state) — fires client notification job.
  * `Request Revision` (Admin only, opens modal with `admin_notes` input field) — fires engineer notification job.
  * `Generate PDF` (Dispatches a **queued job**, not a synchronous stream; user gets a notification with download link when ready) `CHANGED — synchronous generation will block on Weekly Digest aggregation`
  * `View Activity Log` (Admin only — reads from `activity_log`) `NEW`

---

## 7. Standard Paperwork & PDF Engine Architecture

To ensure adaptability for confidential or customized client templates, implement the **Data Transfer Object (DTO) + Blade Service Pattern**, executed via a queued job.

```
[DailyReport Model]
        │
        ▼
[GeneratePdfJob] ──► [ReportPdfService] ──► Maps DB + JSONB ──► [ReportDataDTO]
   (queued)                                                          │
                                                                      ▼
                                                          [Blade View (HTML/CSS)]
                                                                      │
                                                                      ▼
                                                       [PDF Engine (Browsershot/Dompdf)]
                                                                      │
                                                                      ▼
                                              [Stored on S3-compatible disk, notification fired]
```

### 7.1 Standard Prototype Documents
1. **Daily Site Progress Summary:** Contains project header, weather, progress remarks, allocated worker count, and a 2x2 grid of uploaded site photos.
2. **Weekly Site Executive Digest:** Aggregates 7 days of published `daily_reports`, summarizing worker hours, weather delays, and key milestones completed (now sourced from `project_milestones`, not free text). `CHANGED`
3. **Worker Attendance & Labor Roster:** Tabular breakdown of workers, trade skills, site assignments, and hours logged across a selected date range.

### 7.2 Implementation Rules for Coding Agents
* DO NOT write inline HTML inside PDF Service classes.
* ALWAYS pass data to clean, standard Laravel Blade templates in `resources/views/pdf/`.
* CSS MUST rely on inline/embedded print styles designed for standard A4 page metrics (`@page { size: A4 portrait; margin: 15mm; }`).
* PDF generation MUST run inside a queued job (`GeneratePdfJob`), never synchronously in the HTTP request cycle. `NEW`
* Generated PDFs are written to a dedicated storage disk/prefix and linked from the `daily_reports` or a new `generated_documents` record — do not regenerate on every download request. `NEW`

---

## 8. Non-Functional Requirements `NEW SECTION`

### 8.1 Security
* All file uploads validated server-side by actual MIME sniffing, not just extension — reject mismatches.
* Rate-limit authentication endpoints and the client portal's public-facing routes.
* Enforce Laravel's default password hashing + a minimum password policy; consider forcing password reset on first login for client-invited accounts.
* Signed, expiring URLs for PDF/photo downloads rather than permanently public storage links.

### 8.2 Performance & Scale
* Target: admin dashboard table views (reports, projects) should paginate server-side, never load full result sets — expected volume is hundreds of reports/month per active project.
* Add the indexes noted inline in Section 4 before initial load testing; missing `(site_id, report_date)` and `(status)` indexes on `daily_reports` are the most likely early bottleneck given filtered dashboard views.

### 8.3 Backup & Disaster Recovery
* Daily automated PostgreSQL backups with a defined retention window (e.g. 30 days) — specify provider/mechanism during infra setup, not left implicit.
* Object storage (photos/PDFs) should use a provider with versioning or cross-region redundancy given these are often the only copy of field evidence.

### 8.4 Testing Strategy
* Feature tests per role (Admin/Site Engineer/Client) covering the RBAC scoping rules in Section 3 — these are the highest-risk area for data leakage bugs (e.g. a client seeing another client's draft report).
* State machine transition tests covering all legal and illegal status transitions in Section 5.
* PDF generation job tests using a fake queue to assert the correct DTO data reaches the Blade template.

---

## 9. Development Phases & Execution Roadmap

### Phase 1: Database & Core Models
1. Configure PostgreSQL connection (`config/database.php`).
2. Implement schema migrations as specified in Section 4 using UUIDs for primary keys, soft-deletes, and the indexes noted inline.
3. Setup Eloquent Model relationships, casts (`meta_data` -> `array`), and status Enums.
4. Install `spatie/laravel-activitylog` and attach the `LogsActivity` trait to `daily_reports`, `projects`, and `project_milestones`. `NEW`

### Phase 2: Authentication & Scoped RBAC
1. Install Filament v3 and configure Shield/Spatie Permissions.
2. Define policies for `Project`, `Site`, `DailyReport`, `Worker`, and `ProjectMilestone` resources.
3. Implement Eloquent Query Scopes:
   * **Site Engineer Scope:** Filter `DailyReport` records by user's assigned projects.
   * **Client User Scope:** Filter `DailyReport` records by assigned client ID AND enforce `status = 'published'`.
4. Write RBAC feature tests per Section 8.4 before moving to Phase 3. `NEW`

### Phase 3: Daily Report Resource & Auto-Save
1. Build `DailyReportResource` in Filament with `Repeater` (workers) and `FileUpload` (photos, wired to S3-compatible disk + Intervention Image thumbnailing).
2. Implement the 10-second auto-save feature for `draft` statuses, with retry-on-failure per Section 5.1.
3. Implement state machine action modals (`Submit for Approval`, `Approve`, `Request Revision`), writing to `daily_report_revisions` on resubmission and firing notifications.

### Phase 4: Milestones & Notifications `NEW PHASE`
1. Build `ProjectMilestoneResource` and relation manager on `ProjectResource`.
2. Wire up Laravel Notifications (mail + Filament database channel) for: report submitted, approved, revision requested, published.
3. Verify client-facing notification emails render correctly and respect the `published`-only visibility rule.

### Phase 5: PDF Paperwork Service & Client Portal
1. Build `App\Services\PdfReportService` and `GeneratePdfJob` (queued).
2. Design responsive HTML Blade PDF layouts for Daily Log, Weekly Digest (now pulling milestone data), and Attendance Roster.
3. Wire up Filament Table Action `Generate PDF` to dispatch the job and notify on completion.
4. Verify Client User read-only dashboard and signed-URL PDF downloads.
5. Run full test suite from Section 8.4 before launch.
