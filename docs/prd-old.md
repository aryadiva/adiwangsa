# Technical Product Requirement Document (PRD) & Architecture Blueprint

**Project Name:** Construction Operations & Back-Office Management Dashboard  
**Target Architecture:** Monolithic Laravel + Filament PHP (TALL Stack)  
**Database Engine:** PostgreSQL  
**Primary Purpose:** AI Coding Agent Execution Context & Implementation Reference  

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

#### Site Engineer (Field User)
* Restricted write access: Can only create/update `daily_reports` for explicitly assigned project sites.
* Can create and save reports in `draft` status or advance them to `need_approval`.
* Uploads site photos and inputs worker counts.
* Cannot publish reports or modify system configuration.

#### Client User (External Stakeholder)
* Strict **Read-Only** access scoped strictly to projects linked to their client record.
* Can view **ONLY** reports with `published` status (cannot view `draft` or `need_approval`).
* Can download generated client-facing PDFs.

---

## 4. PostgreSQL Database Schema Specification

```
[clients] 1 ─── N [projects] 1 ─── N [sites] 1 ─── N [daily_reports] 1 ─── N [daily_report_photos]
                       │               │                    │
                       │               │                    └── N [daily_report_workers] ── N [workers]
                       └── N [project_user] ── N [users]
```

### 4.1 Table Definitions & Relationships

#### `users`
* `id` (UUID / Primary Key)
* `name` (String)
* `email` (String, Unique)
* `password` (String)
* `role` (Enum: `admin`, `site_engineer`, `client`)
* `created_at`, `updated_at` (Timestamps)

#### `clients`
* `id` (UUID / Primary Key)
* `user_id` (FK -> `users.id`, Nullable - links client portal user account)
* `company_name` (String)
* `contact_person` (String)
* `email` (String)
* `phone` (String)
* `meta_data` (JSONB, Default: `{}`)
* `created_at`, `updated_at` (Timestamps)

#### `projects`
* `id` (UUID / Primary Key)
* `client_id` (FK -> `clients.id`, On Delete Restrict)
* `name` (String)
* `code` (String, Unique - e.g., `PRJ-2026-001`)
* `status` (Enum: `planning`, `active`, `on_hold`, `completed`)
* `start_date` (Date)
* `target_end_date` (Date, Nullable)
* `budget` (Decimal: 15, 2, Nullable)
* `meta_data` (JSONB, Default: `{}`)
* `created_at`, `updated_at` (Timestamps)

#### `sites`
* `id` (UUID / Primary Key)
* `project_id` (FK -> `projects.id`, On Delete Cascade)
* `name` (String - e.g., "Block A Foundation")
* `address` (Text, Nullable)
* `location_coordinates` (Point / String, Nullable)
* `created_at`, `updated_at` (Timestamps)

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
* `created_at`, `updated_at` (Timestamps)

#### `daily_reports`
* `id` (UUID / Primary Key)
* `site_id` (FK -> `sites.id`, On Delete Restrict)
* `created_by_user_id` (FK -> `users.id`)
* `reviewed_by_user_id` (FK -> `users.id`, Nullable)
* `report_date` (Date)
* `weather_condition` (Enum: `sunny`, `rainy`, `cloudy`, `stormy`)
* `work_summary` (Text)
* `delays_or_issues` (Text, Nullable)
* `status` (Enum: `draft`, `need_approval`, `published`, `revision_requested`, Default: `draft`)
* `admin_notes` (Text, Nullable)
* `meta_data` (JSONB, Default: `{}` - flexible storage for moisture %, safety incidents, custom metrics)
* `created_at`, `updated_at` (Timestamps)

#### `daily_report_photos`
* `id` (UUID / Primary Key)
* `daily_report_id` (FK -> `daily_reports.id`, On Delete Cascade)
* `file_path` (String)
* `caption` (String, Nullable)
* `created_at`, `updated_at` (Timestamps)

#### `daily_report_workers` (Pivot / Allocation Detail)
* `id` (UUID / Primary Key)
* `daily_report_id` (FK -> `daily_reports.id`, On Delete Cascade)
* `worker_id` (FK -> `workers.id`, On Delete Restrict)
* `hours_worked` (Decimal: 4, 2, Default: 8.00)
* `remarks` (String, Nullable)

---

## 5. Daily Report State Machine & Approval Workflow

```
       [Site Engineer Types]
                 │
                 ▼
          ┌─────────────┐
          │    DRAFT    │◄─── Auto-saved every 10s via Livewire wire:poll
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
  (Visible to                    └──► (Site Engineer edits
   Client User)                        and re-submits to NEED_APPROVAL)
```

### State Definitions
1. **Draft:** Created by Site Engineer. Auto-saves continuously. Editable only by author or admin. **Hidden from Clients.**
2. **Need Approval:** Submitted by Site Engineer upon completion. Read-only for Engineer while under review.
3. **Revision Requested:** Flagged by Admin with feedback in `admin_notes`. Unlocks editing for the Site Engineer.
4. **Published:** Approved by Admin. Locked from editing. **Visible to Client Users** and ready for PDF generation.

---

## 6. Filament PHP UI/UX Implementation Requirements

### 6.1 Form Auto-Save (Draft State)
* The `DailyReportResource` edit form MUST utilize Livewire polling (`wire:poll.10s="saveDraft"`) or Alpine-assisted debounce listeners to auto-persist form states to PostgreSQL when `status === 'draft'`.
* Display a visual indicator in the Filament topbar: `"Draft Saved automatically at HH:mm:ss"`.

### 6.2 Key Form Schema Controls
* **Site Picker:** Filtered by sites belonging to projects assigned to the logged-in user (`project_user`).
* **Worker Allocations:** Implemented using Filament `Repeater` component mapping to `daily_report_workers`.
* **Photo Uploads:** Filament `FileUpload` supporting multiple image uploads (`image/*`), auto-generating responsive thumbnails, and storing paths in `daily_report_photos`.
* **Flexible Fields (`JSONB`):** Filament `KeyValue` or `Group` component binding directly to `meta_data` for dynamic client-specific properties.

### 6.3 Table Resource Filters & Actions
* **Admin Dashboard:** Include a badge tab filter for `Need Approval` items with a count indicator.
* **Header / Table Actions:**
  * `Approve & Publish` (Admin only, visible on `need_approval` state).
  * `Request Revision` (Admin only, opens modal with `admin_notes` input field).
  * `Generate PDF` (Triggers PDF Service class and streams download).

---

## 7. Standard Paperwork & PDF Engine Architecture

To ensure adaptability for confidential or customized client templates, implement the **Data Transfer Object (DTO) + Blade Service Pattern**.

```
[DailyReport Model]
        │
        ▼
[ReportPdfService] ──► Maps DB + JSONB ──► [ReportDataDTO]
                                                  │
                                                  ▼
                                      [Blade View (HTML/CSS)]
                                                  │
                                                  ▼
                                       [PDF Engine (Browsershot/Dompdf)]
```

### 7.1 Standard Prototype Documents
1. **Daily Site Progress Summary:** Contains project header, weather, progress remarks, allocated worker count, and a 2x2 grid of uploaded site photos.
2. **Weekly Site Executive Digest:** Aggregates 7 days of published `daily_reports`, summarizing worker hours, weather delays, and key milestones completed.
3. **Worker Attendance & Labor Roster:** Tabular breakdown of workers, trade skills, site assignments, and hours logged across a selected date range.

### 7.2 Implementation Rules for Coding Agents
* DO NOT write inline HTML inside PDF Service classes.
* ALWAYS pass data to clean, standard Laravel Blade templates in `resources/views/pdf/`.
* CSS MUST rely on inline/embedded print styles designed for standard A4 page metrics (`@page { size: A4 portrait; margin: 15mm; }`).

---

## 8. Development Phases & Execution Roadmap

### Phase 1: Database & Core Models
1. Configure PostgreSQL connection (`config/database.php`).
2. Implement schema migrations as specified in Section 4 using UUIDs for primary keys.
3. Setup Eloquent Model relationships, casts (`meta_data` -> `array`), and status Enums.

### Phase 2: Authentication & Scoped RBAC
1. Install Filament v3 and configure Shield/Spatie Permissions.
2. Define policies for `Project`, `Site`, `DailyReport`, and `Worker` resources.
3. Implement Eloquent Query Scopes:
   * **Site Engineer Scope:** Filter `DailyReport` records by user's assigned projects.
   * **Client User Scope:** Filter `DailyReport` records by assigned client ID AND enforce `status = 'published'`.

### Phase 3: Daily Report Resource & Auto-Save
1. Build `DailyReportResource` in Filament with `Repeater` (workers) and `FileUpload` (photos).
2. Implement the 10-second auto-save feature for `draft` statuses.
3. Implement state machine action modals (`Submit for Approval`, `Approve`, `Request Revision`).

### Phase 4: PDF Paperwork Service & Client Portal
1. Build `App\Services\PdfReportService`.
2. Design responsive HTML Blade PDF layouts for Daily Log and Weekly Digest.
3. Wire up Filament Table Action `Download PDF` for `published` reports.
4. Verify Client User read-only dashboard.
