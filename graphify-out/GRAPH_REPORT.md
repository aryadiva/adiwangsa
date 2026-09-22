# Graph Report - adiwangsa  (2026-09-22)

## Corpus Check
- 44 files · ~125,231 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 4738 nodes · 13404 edges · 288 communities (246 shown, 42 thin omitted)
- Extraction: 89% EXTRACTED · 11% INFERRED · 0% AMBIGUOUS · INFERRED: 1523 edges (avg confidence: 0.59)
- Token cost: 0 input · 0 output

## Community Hubs (Navigation)
- Chart Widget JS
- Rich Editor JS
- Stats Overview Widget JS
- Chart Widget JS
- Chart Widget JS
- Sub-Jobs & Delay Workflow
- Markdown Editor JS
- Chart Widget JS
- Chart Widget JS
- Markdown Editor JS
- Chart Widget JS
- Rich Editor JS
- Select Field JS
- Payroll & Clients
- Daily Reports & PDF Jobs
- File Upload JS
- Rich Editor JS
- Filament Support JS
- Rich Editor JS
- Report Status & Documents
- Photos, DTO & Attendance
- Stats Overview Widget JS
- Filament Support JS
- Markdown Editor JS
- Stats Overview Widget JS
- Chart Widget JS
- Stats Overview Widget JS
- Stats Overview Widget JS
- Scheduled Commands
- Markdown Editor JS
- Rich Editor JS
- Markdown Editor JS
- Report Data DTO
- Stats Overview Widget JS
- Rich Editor JS
- Resource Query Scoping
- Filament Support JS
- Live Camera Capture
- Daily Report State Machine
- Rich Editor JS
- Stats Overview Widget JS
- Project & Shift Enums
- Chart Widget JS
- Stats Overview Widget JS
- Rich Editor JS
- Daily Report Form
- Stats Overview Widget JS
- List Pages & Header Actions
- Markdown Editor JS
- Stats Overview Widget JS
- Frontend JS
- PRD v3 Requirements
- Stats Overview Widget JS
- Frontend JS
- Chart Widget JS
- Seeders & Shield Setup
- Rich Editor JS
- Select Field JS
- Frontend JS
- Filament Support JS
- Panel & Auth Wiring
- NPM Toolchain
- Filament Support JS
- Client Resource
- Edit Pages
- Report Create/Edit Pages
- Rich Editor JS
- Rich Editor JS
- Rich Editor JS
- Daily Report Index Migrations
- Frontend JS
- Language Switcher
- Payroll Run Resource
- Markdown Editor JS
- Rich Editor JS
- Composer Manifest
- Rich Editor JS
- Composer Dependencies
- User Resource
- Sites & Workers Migrations
- Frontend JS
- Frontend JS
- Filament Support JS
- TASKS: Daily Reports
- Composer Scripts
- Frontend JS
- Chart Widget JS
- Delay Event Resource
- Locale Middleware & Bootstrap
- Dev Dependencies
- Permission & Revision Migrations
- Frontend JS
- TASKS: Milestones & Sub-Jobs
- Generated PDF Resource
- Project Resource
- Worker Attendance Resource
- TASKS: Target & Delay Engines
- Site Resource
- Worker Resource
- Project Misc
- Project Misc
- Frontend JS
- Stats Overview Widget JS
- TASKS.md Notes
- Composer Manifest
- Project Misc
- Frontend JS
- Pest Tests
- Select Field JS
- TASKS.md Notes
- TASKS.md Notes
- Composer Manifest
- Composer Manifest
- Project Misc
- Frontend JS
- Project Misc
- Support Helpers
- Project Misc
- Project Misc
- Project Misc
- Project Misc
- Project Misc
- Pest Tests
- TASKS.md Notes
- Project Misc
- HTTP Layer
- Project Misc
- Project Misc
- Project Misc
- Project Misc
- Project Misc
- Project Misc
- Project Misc
- Project Misc
- PRD Docs
- Blade Views
- Blade Views
- TASKS.md Notes
- Project Misc
- Filament Resources
- Eloquent Models
- Eloquent Models
- Support Helpers
- Project Misc
- Model Factories
- Project Misc
- TASKS.md Notes
- TASKS.md Notes
- TASKS.md Notes
- TASKS.md Notes
- TASKS.md Notes
- TASKS.md Notes
- TASKS.md Notes
- Pest Tests

## God Nodes (most connected - your core abstractions)
1. `_update()` - 90 edges
2. `User` - 88 edges
3. `x()` - 87 edges
4. `_update()` - 85 edges
5. `DailyReport` - 75 edges
6. `te()` - 74 edges
7. `V()` - 66 edges
8. `r()` - 64 edges
9. `o()` - 61 edges
10. `Cn()` - 53 edges

## Surprising Connections (you probably didn't know these)
- `Migration: unique index (site_id, report_date, shift)` --references--> `Task 8.2: Shift-Based Daily Reports & Target Engine (COMPLETE)`  [EXTRACTED]
  database/migrations/2026_08_26_000001_change_daily_reports_unique_index_to_include_shift.php → TASKS.md
- `Migration: add shift/target columns to daily_reports` --references--> `Task 8.2: Shift-Based Daily Reports & Target Engine (COMPLETE)`  [EXTRACTED]
  database/migrations/2026_08_26_000000_add_shift_and_target_columns_to_daily_reports_table.php → TASKS.md
- `needApprovalReport()` --calls--> `User`  [EXTRACTED]
  tests/Feature/DailyReportNotificationsTest.php → app/Models/User.php
- `delayedProjectFixtureWithRoles()` --calls--> `User`  [EXTRACTED]
  tests/Feature/DelayCascadeTest.php → app/Models/User.php
- `createDownloadDocument()` --calls--> `User`  [EXTRACTED]
  tests/Feature/GeneratedDocumentDownloadTest.php → app/Models/User.php

## Import Cycles
- None detected.

## Hyperedges (group relationships)
- **Daily Report Lifecycle Flow** — tasks_daily_reports, tasks_daily_report_state_machine, tasks_daily_report_revisions, tasks_daily_report_photos, tasks_daily_report_workers [EXTRACTED 1.00]
- **Bi-Weekly Payroll Pipeline** — tasks_worker_attendance, tasks_payroll_runs, tasks_payroll_items, tasks_payroll_service, tasks_payroll_generate_command [EXTRACTED 1.00]
- **Nightly Scheduler Chain (ordered jobs)** — tasks_recompute_daily_targets_command, tasks_deficit_carry_forward_service, tasks_sub_job_delays_detect_command, tasks_delay_cascade_service [EXTRACTED 1.00]
- **Delay detection → cascade → mitigation workflow** — docs_prd_v2_sub_job_delay_events, docs_prd_v2_delay_state_machine, docs_prd_v2_delay_cascade, docs_prd_v2_milestone_sub_jobs [EXTRACTED 0.90]
- **Shift-based reporting with target/deficit engine** — docs_prd_v2_daily_reports, docs_prd_v2_milestone_sub_jobs, docs_prd_v2_deficit_carry_forward, docs_prd_v2_daily_report_photos, docs_prd_v2_camera_only_capture [INFERRED 0.85]

## Communities (288 total, 42 thin omitted)

### Community 0 - "Chart Widget JS"
Cohesion: 0.01
Nodes (126): acquireContext(), active(), addControllers(), addPlugins(), addScales(), afterDraw(), alpha(), an() (+118 more)

### Community 1 - "Rich Editor JS"
Cohesion: 0.02
Nodes (124): A(), activateAttributeIfSupported(), appendStringToTextAtIndex(), applyBlockAttribute(), attachmentDidChangeUploadProgress(), attachmentIsManaged(), attributeChangedCallback(), Ca() (+116 more)

### Community 2 - "Stats Overview Widget JS"
Cohesion: 0.02
Nodes (104): aa(), active(), addControllers(), addPlugins(), addScales(), al(), _animateOptions(), At() (+96 more)

### Community 3 - "Chart Widget JS"
Cohesion: 0.02
Nodes (137): _a(), abutsStart(), Ac(), after(), afterAutoSkip(), Ag(), Ai(), Al() (+129 more)

### Community 4 - "Chart Widget JS"
Cohesion: 0.04
Nodes (128): adjustHitBoxes(), ae(), af(), aspectRatio(), C(), Ca(), _calculateBarValuePixels(), cd() (+120 more)

### Community 5 - "Sub-Jobs & Delay Workflow"
Cohesion: 0.03
Nodes (33): MilestoneSubJob, ProjectMilestone, static, User, SubJobDelayEvent, WeightIncompleteNotification, DailyReportPolicy, MilestoneSubJobPolicy (+25 more)

### Community 6 - "Markdown Editor JS"
Cohesion: 0.05
Nodes (108): _a(), Ac(), Ae(), ai(), al(), ao(), ar(), bf() (+100 more)

### Community 7 - "Chart Widget JS"
Cohesion: 0.03
Nodes (104): add(), ar(), Bi(), Bl(), _cachedScopes(), Ce(), cf(), chartOptionScopes() (+96 more)

### Community 8 - "Chart Widget JS"
Cohesion: 0.04
Nodes (100): addBox(), addElements(), afterBuildTicks(), afterCalculateLabelRotation(), afterDataLimits(), afterFit(), afterSetDimensions(), afterTickToLabelConversion() (+92 more)

### Community 9 - "Markdown Editor JS"
Cohesion: 0.08
Nodes (88): be(), af(), as(), Ba(), Bc(), Be(), bl(), ue() (+80 more)

### Community 10 - "Chart Widget JS"
Cohesion: 0.04
Nodes (88): addEventListener(), average(), bindResponsiveEvents(), Bt(), ch(), cu(), dataset(), dn() (+80 more)

### Community 11 - "Rich Editor JS"
Cohesion: 0.04
Nodes (81): addAttribute(), addAttributeAtRange(), addAttributesAtRange(), appendText(), applyBlockAttributeAtRange(), box(), breakFormattedBlock(), breaksOnReturn() (+73 more)

### Community 12 - "Select Field JS"
Cohesion: 0.05
Nodes (36): p(), a(), c(), ce, d(), de, e, ee() (+28 more)

### Community 13 - "Payroll & Clients"
Cohesion: 0.05
Nodes (16): Client, PayrollRun, static, User, ClientPolicy, PayrollRunPolicy, RolePolicy, WorkerPolicy (+8 more)

### Community 14 - "Daily Reports & PDF Jobs"
Cohesion: 0.06
Nodes (28): DailyReport, GeneratePdfJob, SendClientReportEmailJob, DailyReportPublished, static, PdfReadyNotification, ReportApprovedNotification, ReportSubmittedNotification (+20 more)

### Community 15 - "File Upload JS"
Cohesion: 0.05
Nodes (56): ba(), bi(), c(), ca(), clickPercent(), constructor(), de(), define() (+48 more)

### Community 16 - "Rich Editor JS"
Cohesion: 0.04
Nodes (74): attachFiles(), backspace(), canApplyToDocument(), compositionend(), compositionShouldAcceptFile(), compositionstart(), compositionupdate(), createLinkHTML() (+66 more)

### Community 17 - "Filament Support JS"
Cohesion: 0.07
Nodes (64): _a(), aa(), ai(), ba(), Be(), br(), T(), Ca() (+56 more)

### Community 18 - "Rich Editor JS"
Cohesion: 0.06
Nodes (72): add(), constructor(), createCaptionElement(), decreaseListLevel(), didFocus(), drop(), findPositionAtIndexAndOffset(), findRangesOfBlocks() (+64 more)

### Community 19 - "Report Status & Documents"
Cohesion: 0.05
Nodes (24): GeneratedDocument, Project, Site, Carbon\Carbon, SiteFactory, SiteSeeder, DateTimeZone, Filament\Facades\Filament (+16 more)

### Community 20 - "Photos, DTO & Attendance"
Cohesion: 0.07
Nodes (21): DailyReportPhoto, DailyReportRevision, DailyReportWorker, PayrollItem, TargetDelayWarning, Worker, WorkerAttendance, Carbon (+13 more)

### Community 21 - "Stats Overview Widget JS"
Cohesion: 0.05
Nodes (70): addElements(), afterBuildTicks(), afterCalculateLabelRotation(), afterDataLimits(), afterDraw(), afterFit(), afterSetDimensions(), afterTickToLabelConversion() (+62 more)

### Community 22 - "Filament Support JS"
Cohesion: 0.09
Nodes (64): Cn(), Bt(), Ct(), dn(), Dt(), Ft(), G(), H() (+56 more)

### Community 23 - "Markdown Editor JS"
Cohesion: 0.04
Nodes (18): Pr(), Aa(), Bi(), bn(), Id(), ji(), Jr(), kd() (+10 more)

### Community 24 - "Stats Overview Widget JS"
Cohesion: 0.07
Nodes (64): acquireContext(), adjustHitBoxes(), bc(), Bl(), c(), C(), clear(), _computeLabelArea() (+56 more)

### Community 25 - "Chart Widget JS"
Cohesion: 0.06
Nodes (61): ad(), Ah(), bf(), buildTicks(), _calculateBarIndexPixels(), calculateCircumference(), _calculatePadding(), _circumference() (+53 more)

### Community 26 - "Stats Overview Widget JS"
Cohesion: 0.06
Nodes (58): afterAutoSkip(), Bi(), buildLookupTable(), buildTicks(), computeTickLimit(), determineDataLimits(), diff(), _drawArgs() (+50 more)

### Community 27 - "Stats Overview Widget JS"
Cohesion: 0.05
Nodes (58): alpha(), an(), be(), chartOptionScopes(), color(), constructor(), darken(), data() (+50 more)

### Community 28 - "Scheduled Commands"
Cohesion: 0.07
Nodes (16): DetectSubJobDelays, GeneratePayrollRuns, PruneMissingPhotos, RecomputeDailyTargets, Carbon, DeficitCarryForwardService, MilestoneSubJob, DelayCascadeService (+8 more)

### Community 29 - "Markdown Editor JS"
Cohesion: 0.23
Nodes (48): $c(), X(), ca(), me(), D(), E(), g(), ge() (+40 more)

### Community 30 - "Rich Editor JS"
Cohesion: 0.05
Nodes (48): xt(), beforeinput(), cacheViewForObject(), canSyncDocumentView(), compositionDidChangeDocument(), compositionDidLoadSnapshot(), createAttachmentNodes(), createChildView() (+40 more)

### Community 31 - "Markdown Editor JS"
Cohesion: 0.14
Nodes (48): at(), B(), he(), br(), Bt(), cf(), Ct(), df() (+40 more)

### Community 32 - "Report Data DTO"
Cohesion: 0.09
Nodes (13): ReportDataDTO, GeneratedDocumentDownloadController, PdfDocumentService, DocumentType, GeneratedDocument, PdfReportService, Barryvdh\DomPDF\PDF, Controller (+5 more)

### Community 33 - "Stats Overview Widget JS"
Cohesion: 0.07
Nodes (47): Ao(), applyStack(), ar(), as(), _calculateBarIndexPixels(), _calculateBarValuePixels(), _calculatePadding(), _computeAngle() (+39 more)

### Community 34 - "Rich Editor JS"
Cohesion: 0.06
Nodes (45): canAcceptDataTransfer(), canDecreaseNestingLevel(), canIncreaseNestingLevel(), compositionControllerDidFocus(), compositionDidRequestChangingSelectionToLocationRange(), createDOMRangeFromLocationRange(), createDOMRangeFromPoint(), createLocationRangeFromDOMRange() (+37 more)

### Community 35 - "Resource Query Scoping"
Cohesion: 0.10
Nodes (23): App\Filament\Resources\ClientResource\Pages, App\Filament\Resources\DailyReportResource\Pages, App\Filament\Resources\GeneratedDocumentResource\Pages, App\Filament\Resources\PayrollRunResource\Pages, PayrollItemsRelationManager, App\Filament\Resources\ProjectResource\Pages, ProjectMilestonesRelationManager, App\Filament\Resources\SiteResource\Pages (+15 more)

### Community 36 - "Filament Support JS"
Cohesion: 0.12
Nodes (39): Qt(), Bi(), I(), d(), Di(), dt(), f(), Ge() (+31 more)

### Community 37 - "Live Camera Capture"
Cohesion: 0.08
Nodes (16): LiveCapture, static, CreateWorkerAttendance, DailyReportPhotoService, PhotoCaptureService, Filament\Forms\Components\Field, Illuminate\Contracts\Filesystem\Filesystem, Illuminate\Http\UploadedFile (+8 more)

### Community 38 - "Daily Report State Machine"
Cohesion: 0.08
Nodes (11): DailyReport, DailyReportStatus, User, LogOptions, DailyReportSeeder, Illuminate\Database\Eloquent\Relations\HasMany, LogOptions, Spatie\Activitylog\Support\LogOptions (+3 more)

### Community 39 - "Rich Editor JS"
Cohesion: 0.09
Nodes (40): applyKeyboardCommand(), attachmentDidChangeAttributes(), attachmentEditorDidRequestRemovalOfAttachment(), canBeGrouped(), checkValidity(), createContentNodes(), dialogIsVisible(), didClickActionButton() (+32 more)

### Community 40 - "Stats Overview Widget JS"
Cohesion: 0.08
Nodes (40): _a(), add(), ba(), _cachedScopes(), cl(), createResolver(), D(), datasetElementScopeKeys() (+32 more)

### Community 41 - "Project & Shift Enums"
Cohesion: 0.07
Nodes (12): ClientFactory, DailyReportFactory, PayrollRunFactory, static, ProjectFactory, static, UserFactory, WorkerFactory (+4 more)

### Community 42 - "Chart Widget JS"
Cohesion: 0.07
Nodes (39): At(), ba(), Bs(), bu(), cc(), _computeLabelSizes(), createResolver(), datasetAnimationScopeKeys() (+31 more)

### Community 43 - "Stats Overview Widget JS"
Cohesion: 0.08
Nodes (39): ac(), Ai(), aspectRatio(), ca(), _computeLabelSizes(), getBasePosition(), getBaseValue(), g() (+31 more)

### Community 44 - "Rich Editor JS"
Cohesion: 0.09
Nodes (36): addHTMLAttribute(), copyUsingObjectMap(), copyUsingObjectsFromDocument(), copyWithBaseBlockAttributes(), find(), fromCommonAttributesOfObjects(), getAttributes(), getAttributesAtPosition() (+28 more)

### Community 45 - "Daily Report Form"
Cohesion: 0.08
Nodes (9): DailyReportResource, LiveCapture, MilestoneStartDateRule, SubJobsWeightsTotalRule, WeightValidation, Carbon, Closure, Illuminate\Contracts\Validation\InvokableRule (+1 more)

### Community 46 - "Stats Overview Widget JS"
Cohesion: 0.09
Nodes (33): addEventListener(), average(), bindResponsiveEvents(), Co(), cs(), Ct(), di(), dn() (+25 more)

### Community 47 - "List Pages & Header Actions"
Cohesion: 0.10
Nodes (13): ListClients, ListDailyReports, ListGeneratedDocuments, ListPayrollRuns, ListProjects, ListSites, ListSubJobDelayEvents, ListUsers (+5 more)

### Community 48 - "Markdown Editor JS"
Cohesion: 0.11
Nodes (31): ad(), An(), cd(), Cr(), dd(), dr(), hf(), Ie() (+23 more)

### Community 49 - "Stats Overview Widget JS"
Cohesion: 0.10
Nodes (30): afterDatasetsUpdate(), buildOrUpdateControllers(), _destroyDatasetMeta(), generateLabels(), getController(), getDatasetMeta(), getDataVisibility(), _getLegendItemAt() (+22 more)

### Community 50 - "Frontend JS"
Cohesion: 0.09
Nodes (14): a(), ar(), b(), cr(), H(), ji(), L(), Me() (+6 more)

### Community 51 - "PRD v3 Requirements"
Cohesion: 0.09
Nodes (28): Bi-Weekly Payroll Cycle (14-day), Camera-Only Capture Rule (SE + HRD), Client Filament Portal Removal, daily_report_photos (before/after pair), daily_report_revisions (snapshot history), Daily Report State Machine (draft→need_approval→published + revision_requested), daily_report_workers (allocation, NOT payroll source), daily_reports table (shift-based) (+20 more)

### Community 52 - "Stats Overview Widget JS"
Cohesion: 0.11
Nodes (27): calculateCircumference(), calculateLabelRotation(), _circumference(), _computeLabelItems(), ec(), Fc(), fit(), _fitCols() (+19 more)

### Community 53 - "Frontend JS"
Cohesion: 0.08
Nodes (3): duration(), persistent(), seconds()

### Community 54 - "Chart Widget JS"
Cohesion: 0.11
Nodes (26): afterDatasetsUpdate(), _d(), generateLabels(), getDatasetMeta(), getDataVisibility(), getMaxBorderWidth(), getStyle(), _handleEvent() (+18 more)

### Community 55 - "Seeders & Shield Setup"
Cohesion: 0.11
Nodes (10): BezhanSalleh\FilamentShield\Support\Utils, DatabaseSeeder, ProjectSeeder, RolePermissionSeeder, ShieldSeeder, User, UserSeeder, WorkerSeeder (+2 more)

### Community 56 - "Rich Editor JS"
Cohesion: 0.11
Nodes (24): ArrowLeft(), ArrowRight(), attachmentManagerDidRequestRemovalOfAttachment(), compositionControllerDidRequestRemovalOfAttachment(), editAttachment(), expandSelectionAroundCommonAttribute(), expandSelectionForEditing(), expandSelectionInDirection() (+16 more)

### Community 57 - "Select Field JS"
Cohesion: 0.07
Nodes (4): Be, oe, pe, te

### Community 58 - "Frontend JS"
Cohesion: 0.17
Nodes (22): B(), C(), D(), H(), I(), J(), O(), U() (+14 more)

### Community 59 - "Filament Support JS"
Cohesion: 0.21
Nodes (23): Ae(), ar(), q(), at(), b(), c(), s(), z() (+15 more)

### Community 60 - "Panel & Auth Wiring"
Cohesion: 0.10
Nodes (19): AdminPanelProvider, BezhanSalleh\FilamentShield\FilamentShieldPlugin, Filament\Http\Middleware\Authenticate, Filament\Http\Middleware\AuthenticateSession, Filament\Http\Middleware\DisableBladeIconComponents, Filament\Http\Middleware\DispatchServingFilamentEvent, Filament\Pages, Filament\Panel (+11 more)

### Community 61 - "NPM Toolchain"
Cohesion: 0.09
Nodes (21): axios, concurrently, laravel-vite-plugin, allowScripts, esbuild@0.28.2, devDependencies, axios, concurrently (+13 more)

### Community 62 - "Filament Support JS"
Cohesion: 0.19
Nodes (22): da(), fa(), Fi(), fn(), S(), Ii(), je(), Jr() (+14 more)

### Community 63 - "Client Resource"
Cohesion: 0.13
Nodes (7): ClientResource, CreateClient, CreateProject, CreateSite, CreateUser, CreateWorker, Filament\Resources\Pages\CreateRecord

### Community 64 - "Edit Pages"
Cohesion: 0.13
Nodes (7): EditClient, EditProject, EditSite, EditUser, EditWorkerAttendance, EditWorker, Filament\Resources\Pages\EditRecord

### Community 65 - "Report Create/Edit Pages"
Cohesion: 0.17
Nodes (3): CreateDailyReport, EditDailyReport, DailyReportStatus

### Community 66 - "Rich Editor JS"
Cohesion: 0.12
Nodes (20): actionIsExternal(), canInvokeAction(), compositionControllerDidBlur(), compositionControllerDidSyncDocumentView(), compositionDidAddAttachment(), compositionDidChangeAttachmentPreviewURL(), compositionDidChangeCurrentAttributes(), compositionDidEditAttachment() (+12 more)

### Community 67 - "Rich Editor JS"
Cohesion: 0.20
Nodes (19): appendAttachmentWithAttributes(), appendBlockForAttributesWithElement(), appendBlockForElement(), appendBlockForTextNode(), appendEmptyBlock(), appendPiece(), appendStringWithAttributes(), findBlockElementAncestors() (+11 more)

### Community 68 - "Rich Editor JS"
Cohesion: 0.12
Nodes (19): attachmentForFile(), attributesForFile(), didChangeAttributes(), getContentType(), getHeight(), getHref(), getPreviewURL(), getType() (+11 more)

### Community 70 - "Frontend JS"
Cohesion: 0.12
Nodes (5): [g](), style(), update(), [x](), tt()

### Community 71 - "Language Switcher"
Cohesion: 0.21
Nodes (6): LanguageSwitcher, Locale, LocaleContext, Illuminate\View\View, Livewire\Component, Locale

### Community 72 - "Payroll Run Resource"
Cohesion: 0.14
Nodes (5): ViewPayrollRun, PayrollRunResource, Filament\Notifications\Notification, Filament\Pages\Actions\Action, Filament\Resources\Pages\ViewRecord

### Community 73 - "Markdown Editor JS"
Cohesion: 0.20
Nodes (15): da(), fa(), Gr(), Jc(), Kr(), Ln(), ma(), qa() (+7 more)

### Community 74 - "Rich Editor JS"
Cohesion: 0.14
Nodes (15): canSetCurrentAttribute(), canSetCurrentBlockAttribute(), canSetCurrentTextAttribute(), didClickAttachment(), dragstart(), findAttachmentForElement(), getAttachmentAndPositionById(), getAttachmentById() (+7 more)

### Community 75 - "Composer Manifest"
Cohesion: 0.14
Nodes (13): description, extra, laravel, keywords, dont-discover, license, minimum-stability, name (+5 more)

### Community 76 - "Rich Editor JS"
Cohesion: 0.15
Nodes (14): canBeConsolidatedWith(), canBeGroupedWith(), canDecreaseBlockAttributeLevel(), compositionControllerDidRender(), getAttributeLevel(), getDirection(), hasAttributes(), hasSameAttributesAsPiece() (+6 more)

### Community 77 - "Composer Dependencies"
Cohesion: 0.15
Nodes (13): require, barryvdh/laravel-dompdf, bezhansalleh/filament-shield, ext-bcmath, fakerphp/faker, filament/filament, intervention/image-laravel, laravel/framework (+5 more)

### Community 80 - "Frontend JS"
Cohesion: 0.17
Nodes (12): Be(), ei(), ii(), le(), ni(), oi(), r(), ri() (+4 more)

### Community 81 - "Frontend JS"
Cohesion: 0.20
Nodes (11): di(), e(), g(), Ht(), i(), Ie(), Re(), t() (+3 more)

### Community 82 - "Filament Support JS"
Cohesion: 0.20
Nodes (12): apply(), B(), lt(), Me(), mo(), ms(), os(), rr() (+4 more)

### Community 83 - "TASKS: Daily Reports"
Cohesion: 0.18
Nodes (12): Client Portal Removal (replaced by emailed PDF reports), DailyReportPolicy, Daily Report Revisions Table (snapshot JSONB), Daily Report State Machine (draft, need_approval, published, revision_requested), DailyReportStatus Enum, Daily Reports Table / DailyReport Model, HRD Role (worker_attendance only, denied daily_reports), ReportShift Enum (shift_1/2/3) (+4 more)

### Community 84 - "Composer Scripts"
Cohesion: 0.18
Nodes (11): scripts, dev, post-update-cmd, pre-package-uninstall, test, Composer\\Config::disableProcessTimeout, Illuminate\\Foundation\\ComposerScripts::prePackageUninstall, npx concurrently -c \"#93c5fd,#c4b5fd,#fb7185,#fdba74\" \"php artisan serve\" \"php artisan queue:listen --tries=1 --timeout=0\" \"php artisan pail --timeout=0\" \"npm run dev\" --names=server,queue,logs,vite --kill-others (+3 more)

### Community 85 - "Frontend JS"
Cohesion: 0.27
Nodes (7): e(), i(), l(), Ni(), o(), t(), u()

### Community 86 - "Chart Widget JS"
Cohesion: 0.25
Nodes (11): aa(), determineDataLimits(), Dh(), _getLabelBounds(), getMinMax(), _getOtherScale(), getUserBounds(), handleTickRangeOptions() (+3 more)

### Community 88 - "Locale Middleware & Bootstrap"
Cohesion: 0.24
Nodes (6): SetLocale, Illuminate\Foundation\Application, Illuminate\Foundation\Configuration\Exceptions, Illuminate\Foundation\Configuration\Middleware, Illuminate\Http\Request, Symfony\Component\HttpFoundation\Response

### Community 89 - "Dev Dependencies"
Cohesion: 0.20
Nodes (10): require-dev, larastan/larastan, laravel/pail, laravel/pint, laravel/sail, mockery/mockery, nunomaduro/collision, pestphp/pest (+2 more)

### Community 91 - "Frontend JS"
Cohesion: 0.20
Nodes (10): Ce(), De(), Dt(), Fe(), He(), ir(), Mt(), nr() (+2 more)

### Community 92 - "TASKS: Milestones & Sub-Jobs"
Cohesion: 0.27
Nodes (10): Clients Table, Milestone Sub-Jobs Table / MilestoneSubJob Model, MilestoneWeightNotificationService (reconcile), Project Milestones Table (weight_percentage, start_date), ProjectResource (Filament, RelationManager host), Projects Table, Sites Table, WeightIncompleteNotification (admin bell) (+2 more)

### Community 96 - "TASKS: Target & Delay Engines"
Cohesion: 0.25
Nodes (9): DeficitCarryForwardService, DelayCascadeService, daily-targets:recompute Command (RecomputeDailyTargets), Scheduler Ordering Constraint (target recompute before delay detection), SubJobDelayEventResource (Filament, list-only), Sub-Job Delay Events Table / SubJobDelayEvent Model, Sub-Job Delay Color State Machine (red, yellow, green), sub-job-delays:detect Command (+1 more)

### Community 99 - "Project Misc"
Cohesion: 0.29
Nodes (4): AppServiceProvider, Illuminate\Cache\RateLimiting\Limit, Illuminate\Support\Facades\RateLimiter, Illuminate\Support\ServiceProvider

### Community 100 - "Project Misc"
Cohesion: 0.25
Nodes (8): post-root-package-install, setup, composer install, npm install, npm run build, @php artisan key:generate, @php artisan migrate --force, @php -r \"file_exists('.env') || copy('.env.example', '.env');\

### Community 101 - "Frontend JS"
Cohesion: 0.25
Nodes (8): dispatch(), dispatchSelf(), dispatchTo(), emit(), emitSelf(), emitTo(), event(), eventData()

### Community 102 - "Stats Overview Widget JS"
Cohesion: 0.36
Nodes (8): dataset(), index(), isPointInArea(), Ls(), point(), rs(), St(), zr()

### Community 103 - "TASKS.md Notes"
Cohesion: 0.25
Nodes (8): Daily Report Photos Table (before/after pair), GeneratePdfJob (queued), Generated Documents Table / GeneratedDocument Model, EN/ID Localization (per-user switcher, locale parity test), PdfDocumentService (document routing + reuse), PdfReportService, ReportDataDTO (immutable, queue-safe, locale field), Signed Expiring URLs for Photos/PDFs (24h)

### Community 104 - "Composer Manifest"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 105 - "Project Misc"
Cohesion: 0.71
Nodes (6): ensure_env(), log(), provision(), entrypoint.sh script, wait_for_minio(), wait_for_pgsql()

### Community 106 - "Frontend JS"
Cohesion: 0.29
Nodes (7): actions(), button(), constructor(), grouped(), link(), name(), view()

### Community 107 - "Pest Tests"
Cohesion: 0.40
Nodes (3): Illuminate\Foundation\Testing\TestCase, ExampleTest, TestCase

### Community 109 - "TASKS.md Notes"
Cohesion: 0.33
Nodes (6): AttendanceService, Camera-Only Capture Rule (client + server defense in depth), DailyReportResource (Filament, auto-save, duplicate check), LiveCapture Form Component (getUserMedia camera), App\Rules\LiveCapture (server-side recency check), WorkerAttendanceResource (Filament, HRD)

### Community 110 - "TASKS.md Notes"
Cohesion: 0.47
Nodes (6): Daily Report Workers Table (SE allocation Repeater), payroll:generate Command (14-day cycle), Payroll Items Table, PayrollService (bcmath money math), Worker Attendance Table (payroll source of truth), Workers Table (daily_rate, payroll fields)

### Community 111 - "Composer Manifest"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 112 - "Composer Manifest"
Cohesion: 0.40
Nodes (5): autoload-dev, files, psr-4, Tests\\, tests/Support/helpers.php

### Community 113 - "Project Misc"
Cohesion: 0.40
Nodes (4): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler, Monolog\Processor\PsrLogMessageProcessor

### Community 114 - "Frontend JS"
Cohesion: 0.40
Nodes (5): danger(), info(), status(), success(), warning()

### Community 115 - "Project Misc"
Cohesion: 0.67
Nodes (4): AGENTS.md — Build Rules & Fast-Reference, PRD v2/v3 — Architecture Blueprint & Source of Truth, README.md — Project Overview & Tech Stack, SCAFFOLDING.md — Setup / Install Guide

### Community 117 - "Project Misc"
Cohesion: 0.50
Nodes (4): post-autoload-dump, Illuminate\\Foundation\\ComposerScripts::postAutoloadDump, @php artisan filament:upgrade, @php artisan package:discover --ansi

### Community 118 - "Project Misc"
Cohesion: 0.50
Nodes (4): post-create-project-cmd, @php artisan key:generate --ansi, @php artisan migrate --graceful --ansi, @php -r \"file_exists('database/database.sqlite') || touch('database/database.sqlite');\

### Community 119 - "Project Misc"
Cohesion: 0.50
Nodes (4): Migration: unique index (site_id, report_date, shift), Migration: add shift/target columns to daily_reports, ReportShift enum (shift_1/shift_2/shift_3), Task 8.2: Shift-Based Daily Reports & Target Engine (COMPLETE)

### Community 120 - "Project Misc"
Cohesion: 0.50
Nodes (4): DeficitCarryForwardService (nightly target recompute + warning lifecycle), DeficitCarryForwardTest (carry-forward math, notify-once, 3-column dup rejection), RecomputeDailyTargets command (daily-targets:recompute, scheduled 00:30), Gotcha: deficit recompute must run BEFORE shift warning evaluation (routes/console.php ordering)

### Community 121 - "Project Misc"
Cohesion: 0.50
Nodes (3): Illuminate\Foundation\Inspiring, Illuminate\Support\Facades\Artisan, Illuminate\Support\Facades\Schedule

### Community 123 - "TASKS.md Notes"
Cohesion: 0.67
Nodes (4): PayrollRunPolicy, PayrollRunResource (Filament, admin review/approve), Payroll Runs Table / PayrollRun Model, Payroll Run State Machine (draft, pending_review, approved, paid)

### Community 147 - "Project Misc"
Cohesion: 0.67
Nodes (3): TargetDelayWarning model (first_triggered_at/resolved_at, notify-once), TargetDelayWarningNotification (mail + database), Migration: create target_delay_warnings table

## Knowledge Gaps
- **112 isolated node(s):** `Controller`, `pestphp/pest-plugin`, `php-http/discovery`, `optimize-autoloader`, `preferred-install` (+107 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **42 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Br()` connect `Chart Widget JS` to `Chart Widget JS`, `Rich Editor JS`, `Frontend JS`, `Rich Editor JS`, `Markdown Editor JS`?**
  _High betweenness centrality (0.073) - this node is a cross-community bridge._
- **Why does `Ls()` connect `Stats Overview Widget JS` to `Chart Widget JS`, `Stats Overview Widget JS`, `Stats Overview Widget JS`?**
  _High betweenness centrality (0.044) - this node is a cross-community bridge._
- **Why does `constructor()` connect `Rich Editor JS` to `Chart Widget JS`, `Rich Editor JS`, `Rich Editor JS`, `Filament Support JS`, `Markdown Editor JS`, `Rich Editor JS`, `Markdown Editor JS`, `Rich Editor JS`, `Rich Editor JS`, `File Upload JS`, `Rich Editor JS`?**
  _High betweenness centrality (0.036) - this node is a cross-community bridge._
- **Are the 3 inferred relationships involving `_update()` (e.g. with `g()` and `f()`) actually correct?**
  _`_update()` has 3 INFERRED edges - model-reasoned connections that need verification._
- **Are the 13 inferred relationships involving `x()` (e.g. with `D()` and `g()`) actually correct?**
  _`x()` has 13 INFERRED edges - model-reasoned connections that need verification._
- **Are the 2 inferred relationships involving `_update()` (e.g. with `g()` and `f()`) actually correct?**
  _`_update()` has 2 INFERRED edges - model-reasoned connections that need verification._
- **What connects `Controller`, `pestphp/pest-plugin`, `php-http/discovery` to the rest of the system?**
  _112 weakly-connected nodes found - possible documentation gaps or missing edges._