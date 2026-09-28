# User Guide & Feature Index

Construction Operations & Back-Office Management Dashboard — v0.2.0

This guide covers everything you can do in the admin panel. All URLs below are relative to your app address, e.g. `https://yourcompany.example.com/admin/daily-reports`.

---

## Visual App Overview

The sidebar menu is split into six groups:

| Group | What lives here | Page |
|---|---|---|
| **Site Activity** | Daily shift reports from the field | [§2.1](#21-daily-reports) |
| **Operations** | Sites, projects, workers, sub-job delay tracking | §2.2 – §2.5 |
| **Administration** | Client records, user accounts, roles | §2.6 – §2.8 |
| **Finance** | Bi-weekly payroll runs | §2.9 |
| **Documents** | Generated PDFs and downloads | §2.10 |
| **Human Resources** | Camera-based worker attendance | §2.11 |

A language switcher (English / Bahasa Indonesia) is available on the login page and in the user menu (top-right avatar).

---

## 1. Authentication

### Login

- **UI Location:** `/admin/login`
- **Purpose:** Sign in with your email and password. Access to each menu is automatically limited to your role.
- **Connected Systems:** Role-based access control (admin, Site Engineer, HRD); session authentication.

### Language Switcher

- **UI Location:** Login page (top area) and User Menu (top-right avatar)
- **Purpose:** Switch the interface between English and Bahasa Indonesia. Logged-in choice is remembered per user; on the login page it applies to the current browser session.
- **Connected Systems:** Locale service, per-recipient notification language for emails and in-app notifications.

---

## 2. Feature Index

### 2.1 Daily Reports

- **UI Location:** `/admin/daily-reports` — Sidebar > **Site Activity** > Daily Reports
- **Purpose:** The core field report: record what happened on a site during a shift (day/night), weather, work progress, manpower allocations, and before/after progress photos. Reports go through an approval workflow before becoming official.
- **Connected Systems:**
  - **Approval workflow:** Draft → Needs Approval → Published, with a Revision Requested branch. All transitions are handled by dedicated actions (`submitForApproval`, `requestRevision`, `resubmitForApproval`, `approveAndPublish`).
  - **PDF generation:** On publish, a PDF is queued in the background (GeneratePdfJob) and appears under Generated PDFs.
  - **Client email:** On publish, the client automatically receives an emailed PDF report (SendClientReportEmailJob). Only on *published* — never on drafts or pending approvals.
  - **Daily target engine:** Each report carries a system-computed daily target; unmet targets roll into the next day's target (deficit carry-forward) and persistent shortfalls notify admins.
  - **Photos:** Exactly one before/after pair per shift, captured with a **live in-app camera** (no file uploads for Site Engineers).
  - **Notifications:** Submissions notify admins; approvals notify the author; revision requests notify the author with the reviewer's notes.

**Key pages:**
| Page | URL | What you do there |
|---|---|---|
| List | `/admin/daily-reports` | Browse, filter by site/date/status, open a report |
| Create | `/admin/daily-reports/create` | Fill a new shift report |
| Edit | `/admin/daily-reports/{id}/edit` | Continue a draft or revise a report after a revision request |

### 2.2 Sites

- **UI Location:** `/admin/sites` — Sidebar > **Operations** > Sites
- **Purpose:** Manage the physical work locations. Every daily report, attendance record, and project milestone ties back to a site.
- **Connected Systems:** Site scoping — Site Engineers only see sites they are assigned to; duplicate-shift protection works per site per date per shift.

### 2.3 Projects

- **UI Location:** `/admin/projects` — Sidebar > **Operations** > Projects
- **Purpose:** The master container for each construction job: client, assigned engineers, sites, timezone, budget, planned dates, and status. Each project holds its milestone list.
- **Connected Systems:**
  - **Milestones (nested):** Open a project to manage milestones with weighted percentages — the weights across milestones must total 100%.
  - **Delay cascade:** Sub-job delays automatically shift dependent milestone dates and the project end date (one atomic operation).
  - **PDF documents:** Project-level summary PDFs are generated in the background on demand.
  - **Timezone handling:** Timestamps are stored UTC and displayed in the project's own timezone.

### 2.4 Workers

- **UI Location:** `/admin/workers` — Sidebar > **Operations** > Workers
- **Purpose:** The worker roster — names, identification, and daily-rate/rate data used by attendance and payroll.
- **Connected Systems:** Worker Attendance (source of truth for hours worked) and Payroll (pays computed from attendance, not from report allocations).

### 2.5 Sub-Job Delays

- **UI Location:** `/admin/sub-job-delay-events` — Sidebar > **Operations** > Sub-Job Delays
- **Purpose:** Track delays on milestone sub-jobs with a traffic-light system: **Red** (delay detected) → **Yellow** (mitigation plan submitted) → **Green** (recovered). A new delay after recovery starts a fresh Red event.
- **Connected Systems:**
  - **Automatic detection:** A nightly job (sub-job-delays:detect, 00:45) detects delays and creates Red events, shifting downstream milestone/project dates in the same transaction.
  - **Mitigation workflow:** Submit a mitigation plan on a Red event, then mark it recovered when back on schedule.
  - **Weight validation:** Sub-job and milestone weights are validated to sum to 100% on save.

### 2.6 Clients

- **UI Location:** `/admin/clients` — Sidebar > **Administration** > Clients
- **Purpose:** Maintain the client directory (contact person, company details). Clients receive published report PDFs by email — there is no client login in v0.2.0.
- **Connected Systems:** Client report emails on publish (recipient email from the client record).

### 2.7 Users

- **UI Location:** `/admin/users` — Sidebar > **Administration** > Users
- **Purpose:** Manage panel accounts and roles: **Admin** (full access), **Site Engineer** (daily reports only), **HRD** (attendance only).
- **Connected Systems:** Role/permission system (Filament Shield) — every list and action is filtered by role.

### 2.8 Roles

- **UI Location:** `/admin/shield/roles` — Sidebar > **Administration** > Roles
- **Purpose:** View and adjust the permission sets attached to each role.
- **Connected Systems:** Same permission system as Users; changes take effect on next login/refresh.

### 2.9 Payroll Runs

- **UI Location:** `/admin/payroll-runs` — Sidebar > **Finance** > Payroll Runs
- **Purpose:** Generate and approve bi-weekly (14-day) payroll. Each run contains one line per worker with regular and overtime pay, computed from attendance.
- **Connected Systems:**
  - **Automatic generation:** Nightly job (payroll:generate, 01:15) creates a run when the 14-day cycle closes.
  - **Attendance source of truth:** Regular/overtime pay derives from Worker Attendance records.
  - **Run workflow:** Draft → For Review → Approved → Paid, via dedicated actions (`submitForReview`, `approve`, `markPaid`).
  - **PDF payslip/summary:** Generated in the background and filed under Generated PDFs.

### 2.10 Generated PDFs

- **UI Location:** `/admin/generated-documents` — Sidebar > **Documents** > Generated PDFs
- **Purpose:** A single library of every PDF the system produced: published daily reports, project summaries, payroll documents. Download any document with an expiring secure link.
- **Connected Systems:** Background PDF generation (queued jobs), S3-compatible storage, throttled download endpoint (`/generated-documents/{id}/download`).

### 2.11 Worker Attendance

- **UI Location:** `/admin/worker-attendances` — Sidebar > **Human Resources** > Worker Attendance
- **Purpose:** Record who showed up, when, and for how long — captured by HRD with a **live camera photo** at the moment of check-in (no gallery uploads).
- **Connected Systems:**
  - **Duplicate guard:** Blocks duplicate attendance for the same worker/site/day.
  - **Photo validation:** Server checks capture metadata (recency) as well as image content.
  - **Payroll:** Attendance feeds the bi-weekly payroll calculation.

---

## 3. Common Workflows ("How Do I...?")

### ...submit a daily report?
1. Sidebar > **Site Activity** > Daily Reports > **New Daily Report** (`/admin/daily-reports/create`)
2. Pick the site, date, and shift (Day/Night).
3. Fill weather, description, manpower allocations, and capture the before/after photos with the live camera.
4. Save as **Draft**, then click **Submit for Approval**.
5. Admin reviews: published (client emailed automatically) or revision requested (you get a notification; resubmit from the report's edit page).

### ...record worker attendance?
1. Sidebar > **Human Resources** > Worker Attendance > **New Worker Attendance** (`/admin/worker-attendances/create`)
2. Choose worker and site.
3. Capture the check-in photo with the **live camera** (file picker is not available for HRD).
4. Save — duplicates for the same worker/site/day are blocked automatically.

### ...handle a sub-job delay?
1. A **Red** event appears automatically under Sidebar > **Operations** > Sub-Job Delays after nightly detection.
2. Open the event, fill in the **Mitigation Plan**, and submit — status turns **Yellow**.
3. When the schedule recovers, click **Mark Recovered** — status turns **Green** and milestone dates are adjusted.
4. If the same sub-job slips again later, a **new Red** event is created.

### ...manage project milestones and weights?
1. Open a project (`/admin/projects` > open record) > **Milestones** tab.
2. Add/edit milestones; set planned dates and weight percentages.
3. The system validates that all milestone weights sum to **100%** before saving (same rule for sub-jobs under each milestone).
4. A notification is sent to admins if a milestone set stays incomplete.

### ...generate payroll?
1. Wait for the automatic run (or trigger via the payroll command) — a **Draft** run appears in Finance > Payroll Runs.
2. Open it to review each worker's regular/overtime pay.
3. Click **Submit for Review**, then **Approve**, then **Mark Paid** after disbursement.
4. The payroll PDF is generated in the background and appears in Documents > Generated PDFs.

### ...email a report to the client?
Nothing to do manually — publishing a daily report triggers the client email automatically (report PDF attached). Verify the client's contact email under Administration > Clients.

### ...download a generated PDF?
1. Sidebar > **Documents** > Generated PDFs (`/admin/generated-documents`)
2. Find the document, click **Download**. Links are secure and expire; re-download if a link has aged out.

### ...change the interface language?
User menu (top-right) > **language switcher**, or the switcher on the login page. Emails/notifications follow each recipient's own saved language.

---

## 4. Quick Reference Table

| Feature | URL / Menu Path | Core Function |
|---|---|---|
| Login | `/admin/login` | Sign in; EN/ID switcher on page |
| Daily Reports | `/admin/daily-reports` | Shift reports with photos, approval workflow, auto PDF + client email |
| Daily report target engine | (automatic) | Computes daily targets, carries deficits forward, warns on persistent shortfalls |
| Sites | `/admin/sites` | Manage work locations; per-site duplicate-shift guard |
| Projects | `/admin/projects` | Master project records, milestones, timezone-aware dates |
| Project Milestones | Project page > Milestones tab | Weighted milestones (must sum 100%), sub-jobs |
| Workers | `/admin/workers` | Worker roster and rates |
| Sub-Job Delays | `/admin/sub-job-delay-events` | Red→Yellow→Green delay tracking, mitigation plans, date cascade |
| Clients | `/admin/clients` | Client directory; email recipients for published reports |
| Users | `/admin/users` | Panel accounts and roles |
| Roles | `/admin/shield/roles` | Permission management |
| Payroll Runs | `/admin/payroll-runs` | Bi-weekly payroll; auto-generated, review→approve→paid |
| Generated PDFs | `/admin/generated-documents` | PDF library with secure expiring downloads |
| Worker Attendance | `/admin/worker-attendances` | Camera-only check-in records; payroll source of truth |
| Language switcher | User menu / login page | EN ↔ ID interface language |
| Nightly automation | (automatic, 00:30–01:15) | Target recompute → delay detection → payroll generation |

---

*Generated from the Graphify architectural map (graphify-out/) — 4,738 nodes / 13,404 edges covering 43 application code files.*