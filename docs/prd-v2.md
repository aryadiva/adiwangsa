# Technical Product Requirement Document (PRD) & Architecture Blueprint (v2)

**Project Name:** Construction Operations & Back-Office Management Dashboard
**Target Architecture:** Monolithic Laravel + Filament PHP (TALL Stack)
**Database Engine:** PostgreSQL
**Primary Purpose:** AI Coding Agent Execution Context & Implementation Reference

> **Changelog from v1:** Added milestones table, soft-deletes, audit logging, report revision history, queued PDF generation, file storage strategy, notification system, offline/mobile guidance, non-functional requirements, indexing strategy, and a testing/security section. See inline `NEW`/`CHANGED` markers.
>
> **Changelog v2 → v3 (this revision, driving the v0.2.0 build):** Client (v0.2.0) feedback restructures the product significantly. Key changes, all marked inline as `v3`:
> - Milestones gain a required sub-job layer (`milestone_sub_jobs`) with weighted (%) progress, which must tally to 100% per milestone (and milestones per project must also tally to 100%).
> - Daily reporting moves from one report/site/day to up to **3 shift-based reports/site/day**, each carrying a live-captured **before/after photo pair + description**, a daily achievement figure against a sub-job target, and an automatic **deficit carry-forward** engine with first-occurrence-only admin warnings.
> - A new **automated delay cascade**: sub-job delays beyond a per-project configurable threshold shift all downstream milestone dates and the project end date, with a 🔴/🟡/🟢 mitigation workflow.
> - The **client Filament portal is removed entirely**. The `clients` entity and `client` user role are retained as a record-of-truth/mailing-list, but no longer have panel/login access — published reports instead go out as **emailed PDFs** (dummy/Mailpit transport for now), with configurable Sender/Receiver/CC and a new worker-allocation PDF split out separately.
> - A new **HRD role**, scoped only to a new `worker_attendance` entity, with strict live-camera-only capture (no gallery uploads) — a rule now also applied to Site Engineers for progress photos (capped at one before/after pair per shift).
> - A new **bi-weekly payroll module**, computed from `worker_attendance`, including overtime calculated from each worker's daily rate.
>
> Everything under a `v3` marker in this document is new or materially changed in this revision; unmarked content is unchanged from v2 and still authoritative.

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
      ├── Admin / Consultant ──► [Full Read/Write, Approval Queue, PDF Exports, System Config, Mitigation Plans]
      │
      ├── Site Engineer ───────► [Scoped CRUD: Assigned Sites, Shift Daily Logs (Draft/Submit)]
      │
      ├── HRD ──────────────────► [Scoped Write: Worker Attendance Only, Live Camera Capture]        `v3 NEW`
      │
      └── Client (record only) ► [No panel access — email-delivered PDF reports]                     `v3 CHANGED`
```

### 3.1 Role Capabilities

#### Admin / Consultant (Super User)
* Full access to all CRUD resources across all projects, sites, workers, and reports.
* Exclusive privilege to change report statuses to `published` or request revisions.
* Ability to trigger PDF paperwork generation and system-wide exports.
* Manages user onboarding and site assignments.
* Can view full audit trail / activity log per report. `NEW`
* Configures milestone/sub-job weights, working-day durations, and the per-project delay threshold. `v3 NEW`
* Submits and tracks mitigation plans against delayed sub-jobs (🔴→🟡→🟢 workflow). `v3 NEW`
* Configures and triggers the emailed PDF report cycle (Sender/Receiver/CC). `v3 NEW`
* Unrestricted photo capture — may upload from device storage or take a live photo (no camera-only restriction). `v3 NEW`

#### Site Engineer (Field User)
* Restricted write access: Can only create/update `daily_reports` for explicitly assigned project sites.
* Can create and save reports in `draft` status or advance them to `need_approval`.
* Submits **up to 3 shift reports per site per day** (one per shift), each retaining `weather_condition`, `work_summary`, worker allocations, and `admin_notes`, plus a daily achievement figure against the active sub-job target and a delay-reason field (required if target missed). `v3 CHANGED`
* Progress photos **must be captured live via in-app camera only** — gallery/file picker is disabled — limited to one **before** photo and one **after** photo per shift, plus a description field. `v3 CHANGED`
* Cannot publish reports or modify system configuration.
* Receives a notification when a report is approved or sent back for revision. `NEW`

#### HRD (Field User — Attendance Only) `v3 NEW`
* Scoped exclusively to the `worker_attendance` entity — no access to `daily_reports`, projects, or milestones.
* Records daily worker attendance, tied to payroll.
* Attendance photos **must be captured live via in-app camera only** — gallery/file picker is strictly disabled, identical restriction to Site Engineer.
* Cannot view or edit financial fields beyond what's needed to confirm attendance (payroll calculation itself is Admin-reviewed).

#### Client (Record of Truth — No Portal Access) `v3 CHANGED`
* The `client` role and `clients` table are **retained** as the authoritative record of client company/contact information, but the dedicated client Filament panel is **removed entirely** — no login, no dashboard, no in-app read access.
* Clients instead receive **published daily reports as emailed PDFs**, with Admin-configurable Sender, Receiver, and CC addresses per send.
* Email delivery uses a dummy/local transport (Mailpit, already provisioned in the dev stack) for v0.2.0; swapping in a real SMTP/paid provider is an environment-only change deferred to a later phase.

---

## 4. PostgreSQL Database Schema Specification

```
[clients] 1 ─── N [projects] 1 ─── N [sites] 1 ─── N [daily_reports] 1 ─── N [daily_report_photos]
     (record only,          │               │           (per shift, v3)          (before/after pair, v3)
      no panel, v3)         │               │                    │
                             │               │                    ├── N [daily_report_workers] ── N [workers]
                             │               │                    └── N [daily_report_revisions]      (NEW)
                             ├── N [project_milestones] ── N [milestone_sub_jobs]   (v3 NEW, weighted %)
                             │                                        │
                             │                                        └── N [sub_job_delay_events]     (v3 NEW)
                             └── N [project_user] ── N [users]

[activity_log] ── polymorphic, references any model above       (NEW)

[workers] 1 ─── N [worker_attendance] ── captured by [hrd user]        (v3 NEW)
[worker_attendance] N ─── 1 [payroll_items] N ─── 1 [payroll_runs]      (v3 NEW, bi-weekly)
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
* `user_id` (FK -> `users.id`, Nullable - `v3 CHANGED`: the client Filament panel is removed, so this link is no longer used to grant portal access; kept nullable/optional purely for historical/record-keeping purposes. `clients.email` is the operative field driving where emailed PDF reports are sent (§7.3).)
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
* `delay_threshold_days` (Integer, Default: `2`) `v3 NEW` — configurable per project; number of days a sub-job may run behind its target before the automated delay cascade (§5.2) triggers.
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
* `weight_percentage` (Decimal: 5, 2) `v3 NEW` — this milestone's weighted contribution to overall project progress. **All milestones under a project must sum to exactly 100.00%**; enforced at the app layer on create/update of any sibling, rejecting the save (with a friendly Filament error) if the set doesn't tally — same pattern as the existing `(site_id, report_date)` duplicate-prevention check.
* `sort_order` (Integer, Default: `0`) — `v3 CHANGED`: now doubles as the **dependency order** used by the delay cascade (§5.2) to determine which milestones shift when an earlier one is delayed.
* `created_at`, `updated_at`, `deleted_at` (Timestamps)
* **Index:** `(project_id, status)`

#### `milestone_sub_jobs` `v3 NEW`
* `id` (UUID / Primary Key)
* `project_milestone_id` (FK -> `project_milestones.id`, On Delete Cascade)
* `title` (String)
* `description` (Text) — detailed task description, per client requirement.
* `start_date` (Date) — distinct from the parent milestone's/project's start date.
* `working_days` (Integer) — planned duration in working days, used to derive the daily target (§5.1).
* `quantity` (Decimal: 12, 2) — physical unit quantity (e.g., m³, m², units) tracked independently of weight.
* `weight_percentage` (Decimal: 5, 2) — this sub-job's weighted contribution to its parent milestone. **All sub-jobs under one milestone must sum to exactly 100.00%**, same app-layer enforcement as milestone weights above.
* `status` (Enum: `pending`, `in_progress`, `completed`, `delayed`)
* `sort_order` (Integer, Default: `0`)
* `created_at`, `updated_at`, `deleted_at` (Timestamps)
* **Index:** `(project_milestone_id, status)`

#### `sub_job_delay_events` `v3 NEW`
* `id` (UUID / Primary Key)
* `milestone_sub_job_id` (FK -> `milestone_sub_jobs.id`, On Delete Cascade)
* `status` (Enum: `red`, `yellow`, `green`) — see §5.2 for the color workflow; resets to `red` on a fresh delay after a prior `green` recovery.
* `triggered_at` (Timestamp) — when the delay threshold was first breached for this occurrence.
* `mitigation_plan` (Text, Nullable) — Admin-authored action plan, present once state reaches `yellow`.
* `mitigation_submitted_by_user_id` (FK -> `users.id`, Nullable, On Delete Set Null)
* `resolved_at` (Timestamp, Nullable) — set when state reaches `green`.
* `created_at`, `updated_at` (Timestamps)
* **Index:** `(milestone_sub_job_id, status)`

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
* `daily_rate` (Decimal: 10, 2, Nullable) — base for an 8-hour standard day; overtime hourly rate is derived as `daily_rate / 8` (§5.3).
* `active_start_date` (Date, Nullable) `v3 NEW` — *Tanggal Masuk Aktif*.
* `deactivation_date` (Date, Nullable) `v3 NEW` — *Tanggal Non-Aktif*.
* `bank_account_number` (String, Nullable) `v3 NEW`
* `bank_account_name` (String, Nullable) `v3 NEW`
* `phone_number` (String, Nullable) `v3 NEW`
* `is_active` (Boolean, Default: `true`)
* `meta_data` (JSONB, Default: `{}`)
* `created_at`, `updated_at`, `deleted_at` (Timestamps)

#### `daily_reports` — `v3 CHANGED: now shift-based, up to 3 per site per day`
* `id` (UUID / Primary Key)
* `site_id` (FK -> `sites.id`, On Delete Restrict)
* `milestone_sub_job_id` (FK -> `milestone_sub_jobs.id`, On Delete Restrict) `v3 NEW` — the sub-job this shift's achievement is logged against.
* `created_by_user_id` (FK -> `users.id`, On Delete Set Null)
* `reviewed_by_user_id` (FK -> `users.id`, Nullable, On Delete Set Null)
* `report_date` (Date)
* `shift` (Enum: `shift_1`, `shift_2`, `shift_3`) `v3 NEW`
* `weather_condition` (Enum: `sunny`, `rainy`, `cloudy`, `stormy`) — retained per client confirmation.
* `work_summary` (Text) — retained.
* `delays_or_issues` (Text, Nullable) — retained; distinct from the new structured `delay_reason` below.
* `daily_achievement` (Decimal: 12, 2) `v3 NEW` — quantity progressed against the linked sub-job's `quantity`, same unit.
* `daily_target` (Decimal: 12, 2) `v3 NEW` — the computed target for this shift/day, including any carried-forward deficit (§5.1). System-computed, not user-entered.
* `delay_reason` (Text, Nullable) `v3 NEW` — required when `daily_achievement < daily_target`.
* `status` (Enum: `draft`, `need_approval`, `published`, `revision_requested`, Default: `draft`)
* `admin_notes` (Text, Nullable) — retained.
* `meta_data` (JSONB, Default: `{}` - flexible storage for moisture %, safety incidents, custom metrics)
* `created_at`, `updated_at`, `deleted_at` (Timestamps)
* **Index:** `(site_id, report_date, shift)` unique-ish `v3 CHANGED` (was `(site_id, report_date)` — now one report per site **per shift** per day, enforced at app layer, following the same friendly-error pattern as before), `(status)`, `(report_date)`, `(milestone_sub_job_id)` `v3 NEW`

#### `daily_report_revisions` `NEW — preserves history each time a report is edited after "revision_requested"`
* `id` (UUID / Primary Key)
* `daily_report_id` (FK -> `daily_reports.id`, On Delete Cascade)
* `snapshot` (JSONB — full field snapshot of the report at time of resubmission)
* `edited_by_user_id` (FK -> `users.id`, On Delete Set Null)
* `created_at` (Timestamp)
* Written automatically whenever a report transitions `revision_requested` → `need_approval`.

#### `daily_report_photos` — `v3 CHANGED: before/after pair + description, not a free multi-upload list`
* `id` (UUID / Primary Key)
* `daily_report_id` (FK -> `daily_reports.id`, On Delete Cascade)
* `before_file_path` (String — object storage key) `v3 CHANGED` (was generic `file_path`)
* `before_thumbnail_path` (String, Nullable) `v3 NEW`
* `after_file_path` (String — object storage key) `v3 NEW`
* `after_thumbnail_path` (String, Nullable) `v3 NEW`
* `description` (String, Nullable) `v3 CHANGED` (was `caption`) — rightmost column in the 3-column Filament layout: **Before | After | Description**.
* `captured_at` (Timestamp) `v3 NEW` — embedded from photo metadata at live-capture time (§6.4), independent of `created_at`, so the true capture moment is preserved even if the report is saved later.
* `file_size_bytes` (Integer, Nullable) `NEW`
* `created_at`, `updated_at`, `deleted_at` (Timestamps)
* **Constraint:** exactly one `daily_report_photos` row per `daily_report` (one before/after pair per shift) — enforced at the app layer, mirroring the 2-photo-per-shift cap in §6.2.

#### `daily_report_workers` (Pivot / Allocation Detail)
* `id` (UUID / Primary Key)
* `daily_report_id` (FK -> `daily_reports.id`, On Delete Cascade)
* `worker_id` (FK -> `workers.id`, On Delete Restrict)
* `hours_worked` (Decimal: 4, 2, Default: 8.00)
* `remarks` (String, Nullable)
* *Retained as-is per client confirmation — this stays the Site Engineer's on-site allocation record for progress-reporting context. It is **not** the source of truth for payroll; see `worker_attendance` below, which HRD owns independently.* `v3 NOTE`

#### `worker_attendance` `v3 NEW`
* `id` (UUID / Primary Key)
* `worker_id` (FK -> `workers.id`, On Delete Restrict)
* `site_id` (FK -> `sites.id`, On Delete Restrict)
* `recorded_by_user_id` (FK -> `users.id`, On Delete Set Null) — the HRD user who captured attendance.
* `attendance_date` (Date)
* `hours_worked` (Decimal: 4, 2, Default: 8.00)
* `overtime_hours` (Decimal: 4, 2, Default: 0.00) — hours beyond the standard 8-hour day, feeds the overtime pay calculation in §5.3.
* `photo_file_path` (String — object storage key) — live-captured only, no gallery upload (§6.4).
* `photo_thumbnail_path` (String, Nullable)
* `captured_at` (Timestamp) — embedded photo metadata, independent of `created_at`.
* `meta_data` (JSONB, Default: `{}`)
* `created_at`, `updated_at`, `deleted_at` (Timestamps)
* **Index:** `(worker_id, attendance_date)` unique-ish (one attendance record per worker per day, app-layer enforced), `(site_id, attendance_date)`

#### `payroll_runs` `v3 NEW`
* `id` (UUID / Primary Key)
* `period_start` (Date), `period_end` (Date) — a 14-day cycle.
* `status` (Enum: `draft`, `pending_review`, `approved`, `paid`)
* `generated_by_user_id` (FK -> `users.id`, Nullable, On Delete Set Null)
* `approved_by_user_id` (FK -> `users.id`, Nullable, On Delete Set Null)
* `created_at`, `updated_at`, `deleted_at` (Timestamps)
* **Index:** `(period_start, period_end)`

#### `payroll_items` `v3 NEW`
* `id` (UUID / Primary Key)
* `payroll_run_id` (FK -> `payroll_runs.id`, On Delete Cascade)
* `worker_id` (FK -> `workers.id`, On Delete Restrict)
* `regular_hours_total` (Decimal: 6, 2) — summed from `worker_attendance` across the period.
* `overtime_hours_total` (Decimal: 6, 2)
* `regular_pay` (Decimal: 12, 2) — `daily_rate`-derived.
* `overtime_pay` (Decimal: 12, 2) — `(daily_rate / 8) × overtime_hours_total`.
* `total_pay` (Decimal: 12, 2)
* `created_at`, `updated_at` (Timestamps)
* **Index:** `(payroll_run_id, worker_id)` unique-ish

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
4. **Published:** Approved by Admin. Locked from editing. PDF generation unlocked; **an emailed PDF is dispatched to the linked client's configured recipients** (Sender/Receiver/CC), replacing the old client-portal notification. `v3 CHANGED`

### 5.1 Shift-Based Submission `v3 NEW`
* A Site Engineer may submit **up to 3 `daily_reports` per site per day**, one per `shift` (`shift_1`/`shift_2`/`shift_3`), each following the same draft → need_approval → published state machine independently.
* Each shift report is scoped to exactly one `milestone_sub_job_id` — the sub-job the engineer is actively progressing that shift.

### 5.2 Automated Target Accumulation & Deficit Carry-Forward `v3 NEW`
* A sub-job's baseline daily target is derived as `quantity / working_days`.
* Each shift report's `daily_target` is the baseline target **plus any deficit carried over** from the prior day/shift where `daily_achievement < daily_target`.
* This recalculation runs as a scheduled job (daily) that walks open sub-jobs, sums the prior period's shortfall, and writes the next `daily_target`.
* **Target delay warning:** if the (deficit-adjusted) target is missed again on its target date, the system raises a warning tied to that sub-job and, transitively, to its milestone's deadline.
* **Notification rule (important):** the admin is notified only on the **first occurrence** of a given warning — repeat evaluations of the same still-unresolved warning do not re-notify. The warning stays visible/active in the UI until the underlying schedule issue is resolved (achievement catches up), at which point it's marked resolved. Model this with `first_triggered_at` / `resolved_at` timestamps on the warning record, firing the notification only on creation.

### 5.3 Automated Delay Cascade & Mitigation Workflow `v3 NEW`
* If a sub-job's cumulative delay exceeds its project's `delay_threshold_days` (configurable per project, default `2`), the system:
  1. Creates a `sub_job_delay_events` row in `red` status.
  2. **Automatically shifts** the target dates of all subsequent milestones (by `sort_order`, simplest sequential model — no explicit dependency graph in v0.2.0) and the project's `target_end_date`, by the delay delta.
* **Mitigation workflow (color states):**
  * 🔴 **Red** — delay detected, no mitigation plan yet.
  * 🟡 **Yellow** — Admin has submitted a `mitigation_plan` on the event.
  * 🟢 **Green** — recovery confirmed (achievement has caught back up to target).
  * If another delay occurs on the same sub-job after a `green` resolution, a **new** event is created and the cycle repeats from 🔴.
* This is a distinct, lightweight state machine from the Daily Report one above — it lives on `sub_job_delay_events`, not on `daily_reports`.

### 5.4 Bi-Weekly Payroll Cycle `v3 NEW`
* A scheduled job runs every 14 days, opening a `payroll_runs` row for the period and generating one `payroll_items` row per active worker from their `worker_attendance` records in that window.
* `regular_pay` and `overtime_pay` are computed per §7.4 (Payroll & Attendance PDF) directly from attendance, not from `daily_report_workers` (which remains the Site Engineer's progress-context allocation only).
* Admin reviews/approves the run before it's considered final (`draft` → `pending_review` → `approved` → `paid`).

### 5.5 Auto-Save Resilience `NEW` *(renumbered from 5.1)*
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
* **Sub-Job Picker:** `v3 NEW` — filtered to sub-jobs under the site's project, scoping which target the shift's `daily_achievement` applies to.
* **Worker Allocations:** Implemented using Filament `Repeater` component mapping to `daily_report_workers`. Retained per client confirmation.
* **Photo Uploads — 3-column Before/After/Description:** `v3 CHANGED` — replaces the prior multi-file `FileUpload`. A custom live-capture component (native camera API, e.g. `<input capture>`/`getUserMedia`; Filament's stock `FileUpload` cannot force camera-only) renders three aligned columns per shift: **Before** (left), **After** (middle), **Description** (right, free text). Exactly one pair per shift — not a multi-upload list. Applies to Site Engineer only for `daily_report_photos`; see §6.4 for the parallel HRD attendance capture.
* **Flexible Fields (`JSONB`):** Filament `KeyValue` or `Group` component binding directly to `meta_data` for dynamic client-specific properties.
* **Milestone & Sub-Job Tracker:** `v3 CHANGED` — `ProjectMilestoneResource` (nested/relation manager on `ProjectResource`) gains a second-level nested `MilestoneSubJobsRelationManager` under each milestone. Both levels' forms include a `weight_percentage` field with live validation feedback showing the running total against 100% for sibling rows, rejecting save on mismatch.
* **Delay Threshold:** `v3 NEW` — `delay_threshold_days` exposed as a numeric field on the `ProjectResource` form (Admin-editable, default `2`).

### 6.3 Table Resource Filters & Actions
* **Admin Dashboard:** Include a badge tab filter for `Need Approval` items with a count indicator.
* **Header / Table Actions:**
  * `Approve & Publish` (Admin only, visible on `need_approval` state) — dispatches the emailed PDF job to the linked client's configured recipients (§7.3), replacing the old client-portal notification. `v3 CHANGED`
  * `Request Revision` (Admin only, opens modal with `admin_notes` input field) — fires engineer notification job.
  * `Generate PDF` (Dispatches a **queued job**, not a synchronous stream; user gets a notification with download link when ready) `CHANGED — synchronous generation will block on Weekly Digest aggregation`
  * `View Activity Log` (Admin only — reads from `activity_log`) `NEW`
  * `Submit Mitigation Plan` (Admin only, visible on `sub_job_delay_events` in `red` status — moves it to `yellow`) `v3 NEW`
  * `Mark Recovered` (Admin only, visible on `yellow` — moves it to `green`) `v3 NEW`

### 6.4 HRD Attendance Capture & Camera Restrictions `v3 NEW`
* HRD's sole resource is `worker_attendance`: worker picker, `hours_worked`, `overtime_hours`, and a single live-captured photo — same custom camera component as §6.2, no gallery/file-picker path.
* **Role-based capture matrix:**

| Role | Capture Method | Notes |
| :--- | :--- | :--- |
| Admin | Live camera **or** device gallery upload | Unrestricted, as v1. |
| Site Engineer | Live camera **only** | Before/after pair, max 1 pair per shift (§6.2). |
| HRD | Live camera **only** | One attendance photo per worker per day. |

* All live-captured photos must have timestamp/date/location metadata embedded automatically at capture time and stored in `captured_at` (and `meta_data` where richer EXIF is available) — this is the primary evidence-integrity guarantee for both progress and attendance photos, since gallery uploads (which could be pre-existing/edited images) are disabled for the two roles most likely to be tempted to falsify records.

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
                                              [Stored on S3-compatible disk]
                                                                      │
                                                          (on Published, v3 NEW) ▼
                                                [SendClientReportEmailJob ──► Mail::to/cc, dummy/Mailpit transport]
```

### 7.1 Standard Prototype Documents
1. **Daily Site Progress Summary:** Contains project header, weather, progress remarks, and the before/after photo pair with description (§6.2). `v3 CHANGED` — **worker allocation is no longer included here**; it's split into its own document (#4 below).
2. **Weekly Site Executive Digest:** Aggregates 7 days of published `daily_reports`, summarizing worker hours, weather delays, and key milestones completed (now sourced from `project_milestones`, not free text). `CHANGED`
3. **Worker Attendance & Labor Roster:** Tabular breakdown of workers, trade skills, site assignments, and hours logged across a selected date range.
4. **Worker Allocation / Payroll & HRD Document:** `v3 NEW` — replaces the worker-count section removed from Document #1. Built from `worker_attendance` + `daily_report_workers`, serving both payroll (regular/overtime pay breakdown per §5.4) and HRD's own attendance record-keeping needs.

### 7.2 Implementation Rules for Coding Agents
* DO NOT write inline HTML inside PDF Service classes.
* ALWAYS pass data to clean, standard Laravel Blade templates in `resources/views/pdf/`.
* CSS MUST rely on inline/embedded print styles designed for standard A4 page metrics (`@page { size: A4 portrait; margin: 15mm; }`).
* PDF generation MUST run inside a queued job (`GeneratePdfJob`), never synchronously in the HTTP request cycle. `NEW`
* Generated PDFs are written to a dedicated storage disk/prefix and linked from the `daily_reports` or a new `generated_documents` record — do not regenerate on every download request. `NEW`

### 7.3 Client Email Delivery `v3 NEW`
* On the `Approve & Publish` transition, `GeneratePdfJob` completion triggers `SendClientReportEmailJob`, which emails the generated Daily Site Progress Summary PDF to the linked client's configured recipients.
* Admin configures **Sender**, **Receiver(s)**, and **CC** per send (or as a saved default per client/project) via a Filament form — not hardcoded.
* **Transport:** v0.2.0 uses Mailpit (already provisioned in the dev stack) as a dummy/local transport standing in for a real SMTP/paid provider — swapping to production email is an `.env` change only (`MAIL_MAILER`, `MAIL_HOST`, etc.), not a code change.
* This replaces the removed client-portal notification entirely (§3.1, §5).

### 7.4 Configurable DTO Sections `v3 NEW`
* `ReportDataDTO` includes an extensible `sections` array (ordered list of typed content blocks) so new report content — requested ad hoc by the client over time — can be added to a Blade template without redesigning the DTO each time. Each entry carries a `type` (e.g. `text`, `table`, `photo_pair`) and a `payload` array; unrecognized types are skipped gracefully by the Blade template rather than erroring.

---

## 8. Non-Functional Requirements `NEW SECTION`

### 8.1 Security
* All file uploads validated server-side by actual MIME sniffing, not just extension — reject mismatches.
* Rate-limit authentication endpoints. `v3 CHANGED` — the client portal's public-facing routes no longer exist (§3.1), so this now applies to the admin/HRD/site-engineer panels only.
* Enforce Laravel's default password hashing + a minimum password policy; consider forcing password reset on first login for client-invited accounts. `v3 NOTE` — client accounts no longer log in at all (§3.1); this now applies to Admin/SE/HRD onboarding only.
* Signed, expiring URLs for PDF/photo downloads rather than permanently public storage links.
* **Live-capture integrity:** `v3 NEW` — SE and HRD photo capture is enforced client-side (camera-only component, §6.4) but treated as a soft guarantee; server-side validation should check for the presence/recency of embedded capture metadata as a secondary check, since a sufficiently determined client-side actor could still circumvent a purely client-enforced restriction.

### 8.2 Performance & Scale
* Target: admin dashboard table views (reports, projects) should paginate server-side, never load full result sets — expected volume is hundreds of reports/month per active project, now up to 3x shift-based volume on `daily_reports`. `v3 CHANGED`
* Add the indexes noted inline in Section 4 before initial load testing; the `(site_id, report_date, shift)` and `(status)` indexes on `daily_reports`, and the new `(worker_id, attendance_date)` index on `worker_attendance`, are the most likely early bottlenecks given filtered dashboard/payroll views. `v3 CHANGED`

### 8.3 Backup & Disaster Recovery
* Daily automated PostgreSQL backups with a defined retention window (e.g. 30 days) — specify provider/mechanism during infra setup, not left implicit.
* Object storage (photos/PDFs) should use a provider with versioning or cross-region redundancy given these are often the only copy of field evidence.
* Payroll data (`payroll_runs`/`payroll_items`) carries the same retention expectations as financial records generally — confirm retention window with the client separately if it differs from the 30-day general default. `v3 NEW`

### 8.4 Testing Strategy
* Feature tests per role (Admin/Site Engineer/HRD) covering the RBAC scoping rules in Section 3 — these are the highest-risk area for data leakage bugs. `v3 CHANGED` — client is no longer a login-capable role, so client-facing RBAC tests are replaced by email-delivery tests (correct recipients, correct PDF attached, correct trigger point).
* State machine transition tests covering all legal and illegal status transitions in Section 5, including the new `sub_job_delay_events` color-state machine. `v3 CHANGED`
* PDF generation job tests using a fake queue to assert the correct DTO data reaches the Blade template.
* Weight-percentage validation tests: sub-job and milestone weight sets that don't sum to 100% are rejected on create/update. `v3 NEW`
* Target-accumulation tests: deficit carry-forward math, and the first-occurrence-only notification rule for scheduling warnings. `v3 NEW`
* Payroll calculation tests: regular/overtime pay derivation from `worker_attendance` across a 14-day period. `v3 NEW`

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

> **Note:** Phases 1–5 above reflect the original v1/v2 build. Phases 6–7 (polish/audit/security, then bug fixes, localization, and deployment hardening) were executed and are documented in full in `TASKS-v2.md` — this PRD's roadmap section was not historically kept in lockstep with every phase. Phase 8 below is the current, active roadmap for the v0.2.0 client-feedback build described throughout this v3 revision.

### Phase 8: v0.2.0 Client Feedback Build `v3 NEW PHASE`
1. **Milestones & sub-jobs:** `milestone_sub_jobs` + `sub_job_delay_events` migrations; `weight_percentage` added to `project_milestones`; nested `MilestoneSubJobsRelationManager`; weight-sum validation (§4, §6.2).
2. **Shift-based daily reports & target engine:** `daily_reports` schema changes (`shift`, `milestone_sub_job_id`, `daily_achievement`, `daily_target`, `delay_reason`); uniqueness constraint change to `(site_id, report_date, shift)`; scheduled job for deficit carry-forward and first-occurrence warnings (§5.1–5.2).
3. **Delay cascade & mitigation:** threshold-breach detection job, sequential milestone/project date shifting, 🔴/🟡/🟢 workflow and Filament actions (§5.3).
4. **Client portal removal:** delete `app/Filament/Client/*`, `ClientPanelProvider`, associated tests (`ClientVisibilityTest`, `ClientPortalTest`); retain `clients` table/role as record-only; document the removal explicitly in `TASKS-v2.md`, matching the project's existing pattern for documenting reversals.
5. **Email PDF delivery:** `SendClientReportEmailJob`, Sender/Receiver/CC configuration UI, Mailpit-backed dummy transport, configurable DTO `sections` array, worker-allocation PDF split into its own payroll/HRD document (§7.3–7.4).
6. **Worker, attendance & payroll:** `workers` table additions; `worker_attendance` table; `payroll_runs`/`payroll_items`; bi-weekly scheduled job; overtime calculation (§5.4).
7. **HRD role & camera enforcement:** `UserRole::HRD`, `HrdPanelProvider` (or equivalent scoped panel/resource), live-capture-only component shared between HRD and Site Engineer, before/after/description 3-column photo UI for SE (§6.2, §6.4).
8. Write RBAC, state-machine, weight-validation, target-accumulation, delay-cascade, and payroll feature tests per Section 8.4; run the full suite before considering v0.2.0 launch-ready.
