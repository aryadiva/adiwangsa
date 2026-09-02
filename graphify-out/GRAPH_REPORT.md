# Graph Report - adiwangsa  (2026-09-02)

## Corpus Check
- 18 files · ~105,852 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 4386 nodes · 12651 edges · 251 communities (223 shown, 28 thin omitted)
- Extraction: 88% EXTRACTED · 12% INFERRED · 0% AMBIGUOUS · INFERRED: 1487 edges (avg confidence: 0.58)
- Token cost: 0 input · 0 output

## Community Hubs (Navigation)
- Chart.js Bundle
- Rich Editor Bundle
- Stats Widget Bundle
- Rich Text Editor
- Chart.js Library
- Chart.js Library
- Chart.js Library
- Rich Text Editor
- RBAC & User Resources
- Core Models & Notifications
- User-Client Relations
- Chart.js Library
- PDF DTO Engine
- Markdown Editor
- Chart.js Library
- Chart.js Library
- Filament Support JS
- Chart.js Library
- Filament Support JS
- Chart.js Library
- DailyReport Model & State
- Chart.js Library
- Rich Text Editor
- Markdown Editor
- Rich Text Editor
- Rich Text Editor
- Weight Validation Rules
- Notifications UI
- Chart.js Library
- Rich Text Editor
- Filament Resource Pages
- Rich Text Editor
- PRD Specification
- Chart.js Library
- Enums
- DailyReport Model & State
- Filament Support JS
- Rich Text Editor
- Rich Text Editor
- Chart.js Library
- Chart.js Library
- DailyReport Model & State
- Filament Panel Config
- Chart.js Library
- Laravel Echo JS
- Deficit Carry-Forward Engine
- PRD Specification
- PRD v3 Concepts
- Rich Text Editor
- Chart.js Library
- Rich Text Editor
- Laravel Echo JS
- Chart.js Library
- Chart.js Library
- NPM Package Config
- Filament Support JS
- Chart.js Library
- Chart.js Library
- Filament Support JS
- Phase 8 Task Roadmap
- Application Services
- Chart.js Library
- Service Providers
- Markdown Editor
- appendAttachmentWithAttributes()
- Scheduled Commands
- Select Component
- Chart.js Library
- Weight Validation Rules
- Chart.js Library
- Chart.js Library
- Select Component
- Composer Config
- Color Picker Component
- findIndexAndOffsetAtPosition()
- Eloquent Models
- Composer Config
- Composer Config
- Laravel Echo JS
- Laravel Echo JS
- Chart.js Library
- Select Component
- DateTime Picker Component
- Chart.js Library
- Composer Config
- Laravel Echo JS
- File Upload Component
- Chart.js Library
- Weight Validation Rules
- HTTP Controllers
- Client & Project Models
- Composer Config
- File Upload Component
- Service Providers
- Daily Reports Unique Index Migration
- Daily Report Migrations
- Daily Report Migrations
- Docker Config
- File Upload Component
- Daily Report Migrations
- Test Suite
- File Upload Component
- Select Component
- Select Component
- Select Component
- Select Component
- Select Component
- Select Component
- Laravel Bootstrap
- Composer Config
- Composer Config
- Laravel Config
- File Upload Component
- Select Component
- Select Component
- Chart.js Library
- Milestone Migrations
- Milestone Migrations
- Milestone Migrations
- Route Definitions
- Docker Config
- Docker Config
- Docker Config
- Docker Config
- Docker Config
- Tags Input Component
- Textarea Component
- Table Component
- Frontend JS Bootstrap
- Blade Views
- LogOptions
- Laravel Config

## God Nodes (most connected - your core abstractions)
1. `User` - 105 edges
2. `DailyReport` - 91 edges
3. `_update()` - 90 edges
4. `x()` - 87 edges
5. `_update()` - 85 edges
6. `te()` - 74 edges
7. `V()` - 66 edges
8. `r()` - 64 edges
9. `o()` - 61 edges
10. `Project` - 59 edges

## Surprising Connections (you probably didn't know these)
- `Migration: unique index (site_id, report_date, shift)` ----> `Task 8.2: Shift-Based Daily Reports & Target Engine (COMPLETE)`  [1.0]
  database/migrations/2026_08_26_000001_change_daily_reports_unique_index_to_include_shift.php → TASKS.md
- `Migration: add shift/target columns to daily_reports` ----> `Task 8.2: Shift-Based Daily Reports & Target Engine (COMPLETE)`  [1.0]
  database/migrations/2026_08_26_000000_add_shift_and_target_columns_to_daily_reports_table.php → TASKS.md
- `Task 8.2: Shift-Based Daily Reports & Target Engine (COMPLETE)` ----> `DeficitCarryForwardService (nightly target recompute + warning lifecycle)`  [1.0]
  TASKS.md → app/Services/DeficitCarryForwardService.php
- `Task 8.2: Shift-Based Daily Reports & Target Engine (COMPLETE)` ----> `TargetDelayWarning model (first_triggered_at/resolved_at, notify-once)`  [1.0]
  TASKS.md → app/Models/TargetDelayWarning.php
- `Task 8.3: Automated Delay Cascade & Mitigation (NEXT)` ----> `DeficitCarryForwardService (nightly target recompute + warning lifecycle)`  [0.9]
  TASKS.md → app/Services/DeficitCarryForwardService.php

## Import Cycles
- None detected.

## Hyperedges (group relationships)
- **Nightly target engine: recompute targets, evaluate deficits, warn admin once** — recompute_daily_targets_cmd, deficit_carry_forward_service, target_delay_warning, target_delay_warning_notification [0.95]
- **Delay detection → cascade → mitigation workflow** — docs_prd_v2_sub_job_delay_events, docs_prd_v2_delay_state_machine, docs_prd_v2_delay_cascade, docs_prd_v2_milestone_sub_jobs [EXTRACTED 0.90]
- **Bi-weekly payroll pipeline (attendance → pay)** — docs_prd_v2_worker_attendance, docs_prd_v2_payroll_runs, docs_prd_v2_payroll_items, docs_prd_v2_biweekly_payroll [EXTRACTED 0.95]
- **Shift-based reporting with target/deficit engine** — docs_prd_v2_daily_reports, docs_prd_v2_milestone_sub_jobs, docs_prd_v2_deficit_carry_forward, docs_prd_v2_daily_report_photos, docs_prd_v2_camera_only_capture [INFERRED 0.85]

## Communities (251 total, 28 thin omitted)

### Community 0 - "Chart.js Bundle"
Cohesion: 0.01
Nodes (117): abutsStart(), acquireContext(), addControllers(), addPlugins(), addScales(), Au(), ba(), beforeDatasetDraw() (+109 more)

### Community 1 - "Rich Editor Bundle"
Cohesion: 0.02
Nodes (128): activateAttributeIfSupported(), appendStringToTextAtIndex(), applyBlockAttribute(), attachmentDidChangeAttributes(), attachmentDidChangeUploadProgress(), attachmentIsManaged(), attributeChangedCallback(), canRedo() (+120 more)

### Community 2 - "Stats Widget Bundle"
Cohesion: 0.02
Nodes (124): aa(), addControllers(), addPlugins(), addScales(), alpha(), an(), be(), beforeDatasetDraw() (+116 more)

### Community 3 - "Rich Text Editor"
Cohesion: 0.04
Nodes (150): _a(), Ac(), ad(), Ae(), af(), ai(), al(), An() (+142 more)

### Community 4 - "Chart.js Library"
Cohesion: 0.03
Nodes (127): _a(), after(), afterAutoSkip(), Ag(), Ai(), Al(), as(), before() (+119 more)

### Community 5 - "Chart.js Library"
Cohesion: 0.04
Nodes (121): aspectRatio(), At(), Bs(), Bt(), Ca(), cc(), co(), _computeLabelSizes() (+113 more)

### Community 6 - "Chart.js Library"
Cohesion: 0.04
Nodes (100): addAttribute(), addAttributeAtRange(), addAttributesAtRange(), addHTMLAttribute(), appendText(), applyBlockAttributeAtRange(), breakFormattedBlock(), breaksOnReturn() (+92 more)

### Community 7 - "Rich Text Editor"
Cohesion: 0.04
Nodes (96): ad(), af(), applyStack(), bf(), buildTicks(), C(), _calculateBarIndexPixels(), _calculateBarValuePixels() (+88 more)

### Community 8 - "RBAC & User Resources"
Cohesion: 0.04
Nodes (37): ClientResource, App\Filament\Resources\ClientResource\Pages, CreateClient, DailyReportResource, App\Filament\Resources\DailyReportResource\Pages, CreateDailyReport, GeneratedDocumentResource, App\Filament\Resources\GeneratedDocumentResource\Pages (+29 more)

### Community 9 - "Core Models & Notifications"
Cohesion: 0.05
Nodes (32): DailyReportRevision, DailyReportWorker, MilestoneSubJob, ProjectMilestone, WeightIncompleteNotification, MilestoneSubJobPolicy, ProjectMilestonePolicy, MilestoneWeightNotificationService (+24 more)

### Community 10 - "User-Client Relations"
Cohesion: 0.04
Nodes (27): Client, User, Worker, ClientPolicy, DailyReportPolicy, RolePolicy, WorkerPolicy, BezhanSalleh\FilamentShield\Support\Utils (+19 more)

### Community 11 - "Chart.js Library"
Cohesion: 0.03
Nodes (88): attachFiles(), backspace(), canApplyToDocument(), compositionend(), compositionstart(), compositionupdate(), createLinkHTML(), cut() (+80 more)

### Community 12 - "PDF DTO Engine"
Cohesion: 0.06
Nodes (30): DocumentType, ReportDataDTO, Controller, GeneratedDocumentDownloadController, GeneratePdfJob, GeneratedDocument, PdfReadyNotification, PdfDocumentService (+22 more)

### Community 13 - "Markdown Editor"
Cohesion: 0.07
Nodes (63): _a(), aa(), ai(), ba(), Be(), br(), T(), Ca() (+55 more)

### Community 14 - "Chart.js Library"
Cohesion: 0.05
Nodes (69): addBox(), afterBuildTicks(), afterCalculateLabelRotation(), afterDataLimits(), afterFit(), afterSetDimensions(), afterTickToLabelConversion(), afterUpdate() (+61 more)

### Community 15 - "Chart.js Library"
Cohesion: 0.05
Nodes (69): addElements(), afterBuildTicks(), afterCalculateLabelRotation(), afterDataLimits(), afterDraw(), afterFit(), afterSetDimensions(), afterTickToLabelConversion() (+61 more)

### Community 16 - "Filament Support JS"
Cohesion: 0.13
Nodes (67): at(), B(), he(), br(), Bt(), X(), cd(), Cr() (+59 more)

### Community 17 - "Chart.js Library"
Cohesion: 0.06
Nodes (64): adjustHitBoxes(), ae(), afterDraw(), cd(), _computeLabelArea(), _computeTitleHeight(), cs(), draw() (+56 more)

### Community 18 - "Filament Support JS"
Cohesion: 0.07
Nodes (64): adjustHitBoxes(), Bl(), c(), clear(), _computeLabelArea(), _computeTitleHeight(), cr(), da() (+56 more)

### Community 19 - "Chart.js Library"
Cohesion: 0.10
Nodes (61): be(), as(), Bc(), Be(), bl(), ue(), u(), r() (+53 more)

### Community 20 - "DailyReport Model & State"
Cohesion: 0.04
Nodes (62): Ac(), ar(), Bl(), Ce(), cf(), clone(), Dl(), dtFormatter() (+54 more)

### Community 21 - "Chart.js Library"
Cohesion: 0.07
Nodes (61): canSetCurrentAttribute(), canSetCurrentTextAttribute(), createCaptionElement(), didFocus(), dragstart(), drop(), findPositionAtIndexAndOffset(), findRangesOfBlocks() (+53 more)

### Community 22 - "Rich Text Editor"
Cohesion: 0.11
Nodes (59): Cn(), b(), Bt(), Ct(), dn(), Dt(), Ft(), G() (+51 more)

### Community 23 - "Markdown Editor"
Cohesion: 0.08
Nodes (14): DailyReport, User, ReportApprovedNotification, ReportPublishedNotification, ReportSubmittedNotification, RevisionRequestedNotification, TargetDelayWarningNotification, DailyReportStatus (+6 more)

### Community 24 - "Rich Text Editor"
Cohesion: 0.06
Nodes (57): acquireContext(), Ao(), applyStack(), ar(), as(), aspectRatio(), bc(), _calculateBarIndexPixels() (+49 more)

### Community 25 - "Rich Text Editor"
Cohesion: 0.12
Nodes (51): Qt(), Ae(), ar(), at(), Bi(), I(), c(), H() (+43 more)

### Community 26 - "Weight Validation Rules"
Cohesion: 0.06
Nodes (21): EnsurePasswordChanged, SetLocale, LanguageSwitcher, Locale, Carbon, MilestoneStartDateRule, MilestoneWeightsTotalRule, SubJobsWeightsTotalRule (+13 more)

### Community 27 - "Notifications UI"
Cohesion: 0.06
Nodes (17): EditClient, ListClients, EditDailyReport, DailyReportStatus, ListDailyReports, ListGeneratedDocuments, EditProject, ListProjects (+9 more)

### Community 28 - "Chart.js Library"
Cohesion: 0.05
Nodes (11): Aa(), Bi(), bn(), ji(), Jr(), kd(), qd(), Ri() (+3 more)

### Community 29 - "Rich Text Editor"
Cohesion: 0.06
Nodes (48): addEventListener(), bindEvents(), bindResponsiveEvents(), buildOrUpdateScales(), _checkEventBindings(), cl(), cs(), Ct() (+40 more)

### Community 30 - "Filament Resource Pages"
Cohesion: 0.21
Nodes (40): $c(), ca(), me(), D(), E(), g(), H(), Id() (+32 more)

### Community 31 - "Rich Text Editor"
Cohesion: 0.06
Nodes (47): addElements(), afterDatasetsUpdate(), buildOrUpdateControllers(), buildOrUpdateElements(), clear(), cn(), _d(), _dataCheck() (+39 more)

### Community 32 - "PRD Specification"
Cohesion: 0.06
Nodes (23): actions(), button(), constructor(), danger(), dispatch(), dispatchSelf(), dispatchTo(), duration() (+15 more)

### Community 33 - "Chart.js Library"
Cohesion: 0.08
Nodes (45): add(), applyKeyboardCommand(), attachmentEditorDidRequestRemovalOfAttachment(), canBeGrouped(), checkValidity(), copyUsingObjectMap(), copyUsingObjectsFromDocument(), dialogIsVisible() (+37 more)

### Community 34 - "Enums"
Cohesion: 0.06
Nodes (13): Project, ClientFactory, DailyReportFactory, MilestoneSubJobFactory, ProjectFactory, ProjectMilestoneFactory, static, UserFactory (+5 more)

### Community 35 - "DailyReport Model & State"
Cohesion: 0.07
Nodes (41): canAcceptDataTransfer(), canDecreaseBlockAttributeLevel(), canDecreaseNestingLevel(), canIncreaseNestingLevel(), canSetCurrentBlockAttribute(), compositionControllerDidFocus(), compositionDidRequestChangingSelectionToLocationRange(), createDOMRangeFromPoint() (+33 more)

### Community 36 - "Filament Support JS"
Cohesion: 0.07
Nodes (40): _a(), active(), al(), _animateOptions(), ba(), _cachedScopes(), cancel(), configure() (+32 more)

### Community 37 - "Rich Text Editor"
Cohesion: 0.10
Nodes (38): add(), C(), Co(), _computeLabelSizes(), Et(), format(), formats(), getLabelAndValue() (+30 more)

### Community 38 - "Rich Text Editor"
Cohesion: 0.09
Nodes (9): LogOptions, Project, Site, ProjectPolicy, SitePolicy, SiteFactory, SiteSeeder, clientReport() (+1 more)

### Community 39 - "Chart.js Library"
Cohesion: 0.08
Nodes (34): beforeinput(), cacheViewForObject(), canSyncDocumentView(), compositionDidChangeDocument(), compositionDidLoadSnapshot(), createAttachmentNodes(), createChildView(), createContainerElement() (+26 more)

### Community 40 - "Chart.js Library"
Cohesion: 0.08
Nodes (34): ac(), afterAutoSkip(), beforeDraw(), Bi(), buildLookupTable(), determineDataLimits(), endOf(), Fi() (+26 more)

### Community 41 - "DailyReport Model & State"
Cohesion: 0.10
Nodes (24): AppServiceProvider, AdminPanelProvider, ClientPanelProvider, BezhanSalleh\FilamentShield\FilamentShieldPlugin, Filament\Http\Middleware\Authenticate, Filament\Http\Middleware\AuthenticateSession, Filament\Http\Middleware\DisableBladeIconComponents, Filament\Http\Middleware\DispatchServingFilamentEvent (+16 more)

### Community 42 - "Filament Panel Config"
Cohesion: 0.07
Nodes (33): A(), box(), Ca(), constructor(), disabled(), form(), formDisabledCallback(), fromUCS2String() (+25 more)

### Community 43 - "Chart.js Library"
Cohesion: 0.09
Nodes (15): a(), ar(), b(), cr(), H(), ji(), L(), Me() (+7 more)

### Community 44 - "Laravel Echo JS"
Cohesion: 0.10
Nodes (30): afterDatasetsUpdate(), buildOrUpdateControllers(), _destroyDatasetMeta(), generateLabels(), getController(), getDatasetMeta(), getDataVisibility(), _getLegendItemAt() (+22 more)

### Community 45 - "Deficit Carry-Forward Engine"
Cohesion: 0.12
Nodes (7): TargetDelayWarning, DeficitCarryForwardService, MilestoneSubJob, Carbon\CarbonInterface, Notification, reportWithPhotoPath(), makeSubJobWithReports()

### Community 46 - "PRD Specification"
Cohesion: 0.09
Nodes (29): At(), average(), dataset(), ea(), Fa(), ge(), getCenterPoint(), getMaximumSize() (+21 more)

### Community 47 - "PRD v3 Concepts"
Cohesion: 0.09
Nodes (28): Bi-Weekly Payroll Cycle (14-day), Camera-Only Capture Rule (SE + HRD), Client Filament Portal Removal, daily_report_photos (before/after pair), daily_report_revisions (snapshot history), Daily Report State Machine (draft→need_approval→published + revision_requested), daily_report_workers (allocation, NOT payroll source), daily_reports table (shift-based) (+20 more)

### Community 48 - "Rich Text Editor"
Cohesion: 0.11
Nodes (28): buildTicks(), calculateLabelRotation(), _computeAngle(), _computeLabelItems(), computeTickLimit(), diff(), _drawArgs(), _generate() (+20 more)

### Community 49 - "Chart.js Library"
Cohesion: 0.08
Nodes (9): constructor(), define(), getExtension(), _getTestState(), getType(), registerListeners(), yt(), jn() (+1 more)

### Community 50 - "Rich Text Editor"
Cohesion: 0.09
Nodes (26): actionIsExternal(), canBeConsolidatedWith(), canInvokeAction(), compositionControllerDidBlur(), compositionControllerDidRender(), compositionControllerDidSyncDocumentView(), compositionDidAddAttachment(), compositionDidChangeAttachmentPreviewURL() (+18 more)

### Community 51 - "Laravel Echo JS"
Cohesion: 0.10
Nodes (24): attachmentForFile(), attributesForFile(), compositionShouldAcceptFile(), didChangeAttributes(), getContentType(), getCurrentTextAttributes(), getHeight(), getHref() (+16 more)

### Community 52 - "Chart.js Library"
Cohesion: 0.11
Nodes (24): an(), color(), darken(), Dc(), desaturate(), eo(), hexString(), isPointInArea() (+16 more)

### Community 53 - "Chart.js Library"
Cohesion: 0.17
Nodes (22): B(), C(), D(), H(), I(), J(), O(), U() (+14 more)

### Community 54 - "NPM Package Config"
Cohesion: 0.11
Nodes (23): active(), add(), _animateOptions(), _cachedScopes(), _createAnimations(), get(), _getAnims(), getOptionScopes() (+15 more)

### Community 55 - "Filament Support JS"
Cohesion: 0.09
Nodes (23): Bi(), chartOptionScopes(), constructor(), describe(), Ec(), Fr(), getDevicePixelRatio(), getMeta() (+15 more)

### Community 56 - "Chart.js Library"
Cohesion: 0.09
Nodes (21): axios, concurrently, laravel-vite-plugin, allowScripts, esbuild@0.28.2, devDependencies, axios, concurrently (+13 more)

### Community 57 - "Chart.js Library"
Cohesion: 0.12
Nodes (16): [g](), d(), ee(), et(), g(), h(), J(), M() (+8 more)

### Community 58 - "Filament Support JS"
Cohesion: 0.19
Nodes (22): da(), fa(), Fi(), fn(), S(), Ii(), je(), Jr() (+14 more)

### Community 59 - "Phase 8 Task Roadmap"
Cohesion: 0.11
Nodes (21): AGENTS.md — Build Rules & Fast-Reference, Migration: unique index (site_id, report_date, shift), Migration: add shift/target columns to daily_reports, DeficitCarryForwardService (nightly target recompute + warning lifecycle), DeficitCarryForwardTest (carry-forward math, notify-once, 3-column dup rejection), PRD v2/v3 — Architecture Blueprint & Source of Truth, README.md — Project Overview & Tech Stack, RecomputeDailyTargets command (daily-targets:recompute, scheduled 00:30) (+13 more)

### Community 60 - "Application Services"
Cohesion: 0.20
Nodes (7): DailyReportPhotoService, Illuminate\Contracts\Filesystem\Filesystem, Illuminate\Http\UploadedFile, Intervention\Image\ImageManager, RuntimeException, notAnImage(), photoImage()

### Community 61 - "Chart.js Library"
Cohesion: 0.15
Nodes (19): xg(), Ai(), ca(), ec(), Fc(), G(), getIndexAngle(), getPointPosition() (+11 more)

### Community 62 - "Service Providers"
Cohesion: 0.22
Nodes (18): appendAttachmentWithAttributes(), appendBlockForAttributesWithElement(), appendBlockForElement(), appendBlockForTextNode(), appendEmptyBlock(), appendPiece(), appendStringWithAttributes(), findBlockElementAncestors() (+10 more)

### Community 63 - "Markdown Editor"
Cohesion: 0.15
Nodes (18): ArrowLeft(), ArrowRight(), attachmentManagerDidRequestRemovalOfAttachment(), compositionControllerDidRequestRemovalOfAttachment(), editAttachment(), expandSelectionInDirection(), getAttachmentAtRange(), getExpandedRangeInDirection() (+10 more)

### Community 64 - "appendAttachmentWithAttributes()"
Cohesion: 0.18
Nodes (5): Ne(), nt(), Qe(), Se, xe()

### Community 65 - "Scheduled Commands"
Cohesion: 0.17
Nodes (5): PruneMissingPhotos, RecomputeDailyTargets, DailyReportPhoto, Illuminate\Console\Command, reportWithWorkersAndPhoto()

### Community 66 - "Select Component"
Cohesion: 0.23
Nodes (4): a(), c(), r(), v()

### Community 67 - "Chart.js Library"
Cohesion: 0.16
Nodes (16): aa(), Ah(), determineDataLimits(), Dh(), getAllParsedValues(), _getLabelBounds(), getMatchingVisibleMetas(), getMinMax() (+8 more)

### Community 68 - "Weight Validation Rules"
Cohesion: 0.15
Nodes (16): average(), ch(), dataset(), getMaximumSize(), hasValue(), index(), ne(), nearest() (+8 more)

### Community 69 - "Chart.js Library"
Cohesion: 0.13
Nodes (15): scripts, dev, post-autoload-dump, post-update-cmd, pre-package-uninstall, test, Composer\\Config::disableProcessTimeout, Illuminate\\Foundation\\ComposerScripts::postAutoloadDump (+7 more)

### Community 70 - "Chart.js Library"
Cohesion: 0.14
Nodes (3): style(), update(), [x]()

### Community 71 - "Select Component"
Cohesion: 0.15
Nodes (15): findIndexAndOffsetAtPosition(), getObjectAtIndex(), getObjectAtPosition(), getSplittableListInRange(), insertObjectAtIndex(), insertSplittableListAtIndex(), insertSplittableListAtPosition(), removeObjectAtIndex() (+7 more)

### Community 72 - "Composer Config"
Cohesion: 0.15
Nodes (15): apply(), q(), B(), kn(), lt(), Me(), mo(), ms() (+7 more)

### Community 73 - "Color Picker Component"
Cohesion: 0.14
Nodes (13): description, extra, laravel, keywords, dont-discover, license, minimum-stability, name (+5 more)

### Community 74 - "findIndexAndOffsetAtPosition()"
Cohesion: 0.19
Nodes (14): cf(), da(), fa(), Jc(), Ln(), ma(), qa(), Rr() (+6 more)

### Community 75 - "Eloquent Models"
Cohesion: 0.22
Nodes (7): ChangePassword, Dashboard, Filament\Forms\Components\TextInput, Filament\Forms\Concerns\InteractsWithForms, Filament\Forms\Contracts\HasForms, Filament\Pages\Page, Illuminate\Support\Facades\Auth

### Community 76 - "Composer Config"
Cohesion: 0.26
Nodes (13): dd(), it(), Jl(), ki(), lr(), md(), on(), ot() (+5 more)

### Community 77 - "Composer Config"
Cohesion: 0.23
Nodes (9): p(), e, l(), n(), s(), t(), U(), ce() (+1 more)

### Community 78 - "Laravel Echo JS"
Cohesion: 0.17
Nodes (12): require, barryvdh/laravel-dompdf, bezhansalleh/filament-shield, fakerphp/faker, filament/filament, intervention/image-laravel, laravel/framework, laravel/tinker (+4 more)

### Community 79 - "Laravel Echo JS"
Cohesion: 0.17
Nodes (12): Be(), ei(), ii(), le(), ni(), oi(), r(), ri() (+4 more)

### Community 80 - "Chart.js Library"
Cohesion: 0.20
Nodes (11): di(), e(), g(), Ht(), i(), Ie(), Re(), t() (+3 more)

### Community 81 - "Select Component"
Cohesion: 0.27
Nodes (7): e(), i(), l(), Ni(), o(), t(), u()

### Community 82 - "DateTime Picker Component"
Cohesion: 0.22
Nodes (11): c(), o(), _p(), qp(), s(), Sg(), xt(), Ye() (+3 more)

### Community 83 - "Chart.js Library"
Cohesion: 0.22
Nodes (11): addEventListener(), bindResponsiveEvents(), Du(), isAttached(), Ju(), ku(), Li(), Ma() (+3 more)

### Community 84 - "Composer Config"
Cohesion: 0.20
Nodes (10): require-dev, larastan/larastan, laravel/pail, laravel/pint, laravel/sail, mockery/mockery, nunomaduro/collision, pestphp/pest (+2 more)

### Community 85 - "Laravel Echo JS"
Cohesion: 0.20
Nodes (10): Ce(), De(), Dt(), Fe(), He(), ir(), Mt(), nr() (+2 more)

### Community 86 - "File Upload Component"
Cohesion: 0.24
Nodes (10): ba(), e(), Ip(), It(), lt(), sa(), Wp(), Wt() (+2 more)

### Community 88 - "Weight Validation Rules"
Cohesion: 0.25
Nodes (8): post-root-package-install, setup, composer install, npm install, npm run build, @php artisan key:generate, @php artisan migrate --force, @php -r \"file_exists('.env') || copy('.env.example', '.env');\

### Community 89 - "HTTP Controllers"
Cohesion: 0.46
Nodes (8): de(), Fe(), Gt(), j(), je(), le(), vt(), Zp()

### Community 90 - "Client & Project Models"
Cohesion: 0.25
Nodes (8): h(), l(), Q(), Re(), ur(), v(), Z(), ze()

### Community 91 - "Composer Config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 95 - "Daily Report Migrations"
Cohesion: 0.71
Nodes (6): ensure_env(), log(), provision(), entrypoint.sh script, wait_for_minio(), wait_for_pgsql()

### Community 96 - "Daily Report Migrations"
Cohesion: 0.29
Nodes (7): bi(), jp(), ol(), Tp(), xl(), xp(), yl()

### Community 97 - "Docker Config"
Cohesion: 0.40
Nodes (3): Illuminate\Foundation\Testing\TestCase, ExampleTest, TestCase

### Community 98 - "File Upload Component"
Cohesion: 0.40
Nodes (6): ca(), rl(), Rp(), Sp(), vp(), yp()

### Community 105 - "Select Component"
Cohesion: 0.47
Nodes (6): St(), En(), Mt(), On(), vr(), Wr()

### Community 106 - "Select Component"
Cohesion: 0.40
Nodes (3): Illuminate\Foundation\Application, Illuminate\Foundation\Configuration\Exceptions, Illuminate\Foundation\Configuration\Middleware

### Community 107 - "Select Component"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 108 - "Laravel Bootstrap"
Cohesion: 0.40
Nodes (5): autoload-dev, files, psr-4, Tests\\, tests/Support/helpers.php

### Community 109 - "Composer Config"
Cohesion: 0.40
Nodes (4): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler, Monolog\Processor\PsrLogMessageProcessor

### Community 110 - "Composer Config"
Cohesion: 0.60
Nodes (5): clickPercent(), getPosition(), mouseUp(), moveplayhead(), timelineClicked()

### Community 112 - "File Upload Component"
Cohesion: 0.50
Nodes (4): post-create-project-cmd, @php artisan key:generate --ansi, @php artisan migrate --graceful --ansi, @php -r \"file_exists('database/database.sqlite') || touch('database/database.sqlite');\

### Community 113 - "Select Component"
Cohesion: 0.50
Nodes (3): Illuminate\Foundation\Inspiring, Illuminate\Support\Facades\Artisan, Illuminate\Support\Facades\Schedule

### Community 115 - "Chart.js Library"
Cohesion: 0.50
Nodes (3): ce, Te(), ee()

## Knowledge Gaps
- **91 isolated node(s):** `App\\`, `Database\\Factories\\`, `Database\\Seeders\\`, `Tests\\`, `tests/Support/helpers.php` (+86 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **28 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Br()` connect `Chart.js Library` to `Chart.js Bundle`, `Chart.js Library`, `Filament Panel Config`, `Chart.js Library`, `Filament Support JS`?**
  _High betweenness centrality (0.073) - this node is a cross-community bridge._
- **Why does `Ls()` connect `PRD Specification` to `Chart.js Bundle`, `Stats Widget Bundle`?**
  _High betweenness centrality (0.042) - this node is a cross-community bridge._
- **Why does `constructor()` connect `Filament Panel Config` to `Rich Editor Bundle`, `Chart.js Library`, `Rich Text Editor`, `Chart.js Library`, `Chart.js Library`, `Chart.js Library`, `Rich Text Editor`, `Chart.js Library`, `Laravel Echo JS`, `Chart.js Library`, `File Upload Component`, `Rich Text Editor`?**
  _High betweenness centrality (0.037) - this node is a cross-community bridge._
- **Are the 3 inferred relationships involving `_update()` (e.g. with `g()` and `f()`) actually correct?**
  _`_update()` has 3 INFERRED edges - model-reasoned connections that need verification._
- **Are the 13 inferred relationships involving `x()` (e.g. with `D()` and `g()`) actually correct?**
  _`x()` has 13 INFERRED edges - model-reasoned connections that need verification._
- **Are the 2 inferred relationships involving `_update()` (e.g. with `g()` and `f()`) actually correct?**
  _`_update()` has 2 INFERRED edges - model-reasoned connections that need verification._
- **What connects `App\\`, `Database\\Factories\\`, `Database\\Seeders\\` to the rest of the system?**
  _91 weakly-connected nodes found - possible documentation gaps or missing edges._