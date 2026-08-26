# Graph Report - adiwangsa  (2026-08-26)

## Corpus Check
- 25 files · ~103,021 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 4350 nodes · 12531 edges · 252 communities (225 shown, 27 thin omitted)
- Extraction: 88% EXTRACTED · 12% INFERRED · 0% AMBIGUOUS · INFERRED: 1492 edges (avg confidence: 0.58)
- Token cost: 0 input · 0 output

## Community Hubs (Navigation)
- Chart.js Library
- Rich Text Editor
- Chart.js Library
- Rich Text Editor
- Chart.js Library
- Chart.js Library
- Chart.js Library
- Rich Text Editor
- DailyReport Model & State
- DailyReport Model & State
- DailyReport Model & State
- Chart.js Library
- Rich Text Editor
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
- DailyReport Model & State
- Notifications UI
- Chart.js Library
- Rich Text Editor
- Filament Resource Pages
- Rich Text Editor
- PRD Specification
- Chart.js Library
- Filament Resource Pages
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
- Chart.js Library
- PRD Specification
- Chart.js Library
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
- Chart.js Library
- Application Services
- Chart.js Library
- Service Providers
- Markdown Editor
- appendAttachmentWithAttributes()
- ArrowLeft()
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
- Composer Config
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
- Composer Config
- Laravel Config
- Daily Report Migrations
- Test Suite
- Route Definitions
- Docker Config
- Docker Config
- Docker Config
- Docker Config
- Docker Config
- Docker Config
- Docker Config
- Docker Config
- PRD Specification
- Blade Views
- Blade Views
- LogOptions
- Docker Compose

## God Nodes (most connected - your core abstractions)
1. `User` - 117 edges
2. `_update()` - 90 edges
3. `x()` - 87 edges
4. `_update()` - 85 edges
5. `DailyReport` - 80 edges
6. `te()` - 74 edges
7. `Project` - 66 edges
8. `V()` - 66 edges
9. `r()` - 64 edges
10. `o()` - 61 edges

## Surprising Connections (you probably didn't know these)
- `needApprovalReport()` --calls--> `User`  [EXTRACTED]
  tests/Feature/DailyReportNotificationsTest.php → app/Models/User.php
- `createDownloadDocument()` --calls--> `User`  [EXTRACTED]
  tests/Feature/GeneratedDocumentDownloadTest.php → app/Models/User.php
- `adminUser()` --calls--> `User`  [EXTRACTED]
  tests/Support/helpers.php → app/Models/User.php
- `clientLinkedTo()` --calls--> `User`  [EXTRACTED]
  tests/Support/helpers.php → app/Models/User.php
- `engineerAssignedTo()` --calls--> `User`  [EXTRACTED]
  tests/Support/helpers.php → app/Models/User.php

## Import Cycles
- None detected.

## Hyperedges (group relationships)
- **v0.2.0 Client Feedback Build Pipeline** — tasks_weighted_milestones_subjobs, tasks_shift_based_daily_reports, tasks_deficit_carry_forward_engine, tasks_delay_cascade_mitigation, tasks_sub_job_delay_event_state_machine, tasks_client_portal_removal, tasks_send_client_report_email_job, tasks_payroll, tasks_worker_attendance, tasks_hrd_role, tasks_camera_only_capture [EXTRACTED 1.00]
- **Daily Report Lifecycle (state machine + notifications + PDF)** — tasks_daily_report_state_machine, tasks_report_data_dto, tasks_generate_pdf_job, tasks_pdf_report_service, tasks_send_client_report_email_job, tasks_rbac_scopes [INFERRED 0.85]
- **Weight Enforcement & Notification System** — tasks_weight_validation, tasks_milestone_weight_notification_service, tasks_weighted_milestones_subjobs, tasks_schedule_validator [INFERRED 0.85]
- **Delay detection → cascade → mitigation workflow** — docs_prd_v2_sub_job_delay_events, docs_prd_v2_delay_state_machine, docs_prd_v2_delay_cascade, docs_prd_v2_milestone_sub_jobs [EXTRACTED 0.90]
- **Bi-weekly payroll pipeline (attendance → pay)** — docs_prd_v2_worker_attendance, docs_prd_v2_payroll_runs, docs_prd_v2_payroll_items, docs_prd_v2_biweekly_payroll [EXTRACTED 0.95]
- **Shift-based reporting with target/deficit engine** — docs_prd_v2_daily_reports, docs_prd_v2_milestone_sub_jobs, docs_prd_v2_deficit_carry_forward, docs_prd_v2_daily_report_photos, docs_prd_v2_camera_only_capture [INFERRED 0.85]

## Communities (252 total, 27 thin omitted)

### Community 0 - "Chart.js Library"
Cohesion: 0.01
Nodes (132): acquireContext(), active(), addControllers(), addPlugins(), addScales(), Ag(), alpha(), an() (+124 more)

### Community 1 - "Rich Text Editor"
Cohesion: 0.02
Nodes (128): activateAttributeIfSupported(), appendStringToTextAtIndex(), applyBlockAttribute(), attachmentDidChangeAttributes(), attachmentDidChangeUploadProgress(), attachmentIsManaged(), attributeChangedCallback(), canRedo() (+120 more)

### Community 2 - "Chart.js Library"
Cohesion: 0.02
Nodes (114): aa(), active(), addControllers(), addElements(), addPlugins(), addScales(), an(), _animateOptions() (+106 more)

### Community 3 - "Rich Text Editor"
Cohesion: 0.04
Nodes (127): Ac(), ad(), af(), ai(), al(), An(), ao(), Ba() (+119 more)

### Community 4 - "Chart.js Library"
Cohesion: 0.04
Nodes (131): adjustHitBoxes(), ae(), af(), afterDraw(), Ah(), bf(), buildTicks(), calculateLabelRotation() (+123 more)

### Community 5 - "Chart.js Library"
Cohesion: 0.03
Nodes (125): _a(), abutsStart(), after(), afterAutoSkip(), Ai(), Al(), ar(), as() (+117 more)

### Community 6 - "Chart.js Library"
Cohesion: 0.04
Nodes (101): adjustHitBoxes(), afterBuildTicks(), afterCalculateLabelRotation(), afterDataLimits(), afterDraw(), afterFit(), afterSetDimensions(), afterTickToLabelConversion() (+93 more)

### Community 7 - "Rich Text Editor"
Cohesion: 0.04
Nodes (100): addAttribute(), addAttributeAtRange(), addAttributesAtRange(), addHTMLAttribute(), appendText(), applyBlockAttributeAtRange(), breakFormattedBlock(), breaksOnReturn() (+92 more)

### Community 8 - "DailyReport Model & State"
Cohesion: 0.04
Nodes (38): DocumentType, ReportDataDTO, ClientResource, App\Filament\Resources\ClientResource\Pages, DailyReportResource, App\Filament\Resources\DailyReportResource\Pages, GeneratedDocumentResource, App\Filament\Resources\GeneratedDocumentResource\Pages (+30 more)

### Community 9 - "DailyReport Model & State"
Cohesion: 0.05
Nodes (27): DailyReportRevision, DailyReportWorker, MilestoneSubJob, LogOptions, ProjectMilestone, WeightIncompleteNotification, MilestoneSubJobPolicy, ProjectMilestonePolicy (+19 more)

### Community 10 - "DailyReport Model & State"
Cohesion: 0.04
Nodes (24): User, Worker, DailyReportPolicy, ProjectPolicy, RolePolicy, SitePolicy, WorkerPolicy, BezhanSalleh\FilamentShield\Support\Utils (+16 more)

### Community 11 - "Chart.js Library"
Cohesion: 0.04
Nodes (89): addEventListener(), average(), bindResponsiveEvents(), Bt(), Ca(), ch(), contains(), cu() (+81 more)

### Community 12 - "Rich Text Editor"
Cohesion: 0.03
Nodes (88): attachFiles(), backspace(), canApplyToDocument(), compositionend(), compositionstart(), compositionupdate(), createLinkHTML(), cut() (+80 more)

### Community 13 - "Markdown Editor"
Cohesion: 0.08
Nodes (83): be(), _a(), Ae(), ar(), as(), Bc(), Be(), bl() (+75 more)

### Community 14 - "Chart.js Library"
Cohesion: 0.04
Nodes (78): ht(), Ac(), Bl(), cf(), clone(), constructor(), create(), Dl() (+70 more)

### Community 15 - "Chart.js Library"
Cohesion: 0.05
Nodes (70): addBox(), afterBuildTicks(), afterCalculateLabelRotation(), afterDataLimits(), afterFit(), afterSetDimensions(), afterTickToLabelConversion(), afterUpdate() (+62 more)

### Community 16 - "Filament Support JS"
Cohesion: 0.10
Nodes (66): at(), Cn(), b(), Bt(), Ct(), dn(), Dt(), Ft() (+58 more)

### Community 17 - "Chart.js Library"
Cohesion: 0.06
Nodes (65): chartOptionScopes(), average(), ba(), br(), c(), cr(), Ct(), l() (+57 more)

### Community 18 - "Filament Support JS"
Cohesion: 0.06
Nodes (52): ai(), apply(), B(), co(), Cr(), es(), Et(), fo() (+44 more)

### Community 19 - "Chart.js Library"
Cohesion: 0.05
Nodes (62): applyStack(), aspectRatio(), _calculateBarIndexPixels(), _calculateBarValuePixels(), calculateCircumference(), cd(), _circumference(), clear() (+54 more)

### Community 20 - "DailyReport Model & State"
Cohesion: 0.08
Nodes (22): Project, Site, SiteFactory, SiteSeeder, DateTimeZone, Filament\Livewire\DatabaseNotifications, Illuminate\Foundation\Testing\RefreshDatabase, Illuminate\Support\Facades\Bus (+14 more)

### Community 21 - "Chart.js Library"
Cohesion: 0.14
Nodes (61): at(), B(), he(), br(), Bt(), cd(), Cr(), Ct() (+53 more)

### Community 22 - "Rich Text Editor"
Cohesion: 0.07
Nodes (61): canSetCurrentAttribute(), canSetCurrentTextAttribute(), createCaptionElement(), didFocus(), dragstart(), drop(), findPositionAtIndexAndOffset(), findRangesOfBlocks() (+53 more)

### Community 23 - "Markdown Editor"
Cohesion: 0.05
Nodes (10): Pr(), Bi(), bn(), ji(), kd(), qd(), Ri(), te() (+2 more)

### Community 24 - "Rich Text Editor"
Cohesion: 0.12
Nodes (49): Qt(), Ae(), ar(), q(), Bi(), I(), c(), H() (+41 more)

### Community 25 - "Rich Text Editor"
Cohesion: 0.19
Nodes (46): $c(), X(), ca(), me(), D(), E(), g(), H() (+38 more)

### Community 26 - "DailyReport Model & State"
Cohesion: 0.09
Nodes (13): DailyReport, DailyReportStatus, LogOptions, PdfReadyNotification, ReportApprovedNotification, ReportPublishedNotification, ReportSubmittedNotification, RevisionRequestedNotification (+5 more)

### Community 27 - "Notifications UI"
Cohesion: 0.06
Nodes (23): actions(), button(), constructor(), danger(), dispatch(), dispatchSelf(), dispatchTo(), duration() (+15 more)

### Community 28 - "Chart.js Library"
Cohesion: 0.07
Nodes (46): addElements(), buildOrUpdateControllers(), buildOrUpdateElements(), C(), Ce(), co(), _dataCheck(), _destroy() (+38 more)

### Community 29 - "Rich Text Editor"
Cohesion: 0.08
Nodes (45): add(), applyKeyboardCommand(), attachmentEditorDidRequestRemovalOfAttachment(), canBeGrouped(), checkValidity(), copyUsingObjectMap(), copyUsingObjectsFromDocument(), dialogIsVisible() (+37 more)

### Community 30 - "Filament Resource Pages"
Cohesion: 0.07
Nodes (13): CreateClient, CreateDailyReport, EditDailyReport, DailyReportStatus, CreateProject, CreateSite, CreateUser, UserResource (+5 more)

### Community 31 - "Rich Text Editor"
Cohesion: 0.07
Nodes (41): canAcceptDataTransfer(), canDecreaseBlockAttributeLevel(), canDecreaseNestingLevel(), canIncreaseNestingLevel(), canSetCurrentBlockAttribute(), compositionControllerDidFocus(), compositionDidRequestChangingSelectionToLocationRange(), createDOMRangeFromPoint() (+33 more)

### Community 32 - "PRD Specification"
Cohesion: 0.08
Nodes (39): AGENTS.md — Build Rules & Fast-Reference, PRD v2/v3 — Architecture Blueprint & Source of Truth, README.md — Project Overview & Tech Stack, SCAFFOLDING.md — Setup / Install Guide, TASKS.md — Development Tasks, AGENTS.md (fast-reference), Camera-Only Live Capture Component, Client Portal Removal & Emailed PDF Reports (+31 more)

### Community 33 - "Chart.js Library"
Cohesion: 0.09
Nodes (39): applyStack(), ar(), as(), aspectRatio(), _calculateBarIndexPixels(), _calculateBarValuePixels(), _computeGridLineItems(), countVisibleElements() (+31 more)

### Community 34 - "Filament Resource Pages"
Cohesion: 0.08
Nodes (15): EditClient, ListClients, ListDailyReports, ListGeneratedDocuments, EditProject, ListProjects, EditSite, ListSites (+7 more)

### Community 35 - "DailyReport Model & State"
Cohesion: 0.07
Nodes (12): Client, ClientPolicy, ClientFactory, DailyReportFactory, static, MilestoneSubJobFactory, ProjectFactory, ProjectMilestoneFactory (+4 more)

### Community 36 - "Filament Support JS"
Cohesion: 0.16
Nodes (35): _a(), aa(), ba(), Be(), br(), T(), Ca(), ce() (+27 more)

### Community 37 - "Rich Text Editor"
Cohesion: 0.08
Nodes (34): beforeinput(), cacheViewForObject(), canSyncDocumentView(), compositionDidChangeDocument(), compositionDidLoadSnapshot(), createAttachmentNodes(), createChildView(), createContainerElement() (+26 more)

### Community 38 - "Rich Text Editor"
Cohesion: 0.07
Nodes (33): A(), box(), Ca(), constructor(), disabled(), form(), formDisabledCallback(), fromUCS2String() (+25 more)

### Community 39 - "Chart.js Library"
Cohesion: 0.08
Nodes (33): afterAutoSkip(), Ao(), Bi(), buildLookupTable(), determineDataLimits(), Fi(), getAllParsedValues(), getDataTimestamps() (+25 more)

### Community 40 - "Chart.js Library"
Cohesion: 0.12
Nodes (31): buildOrUpdateElements(), C(), Co(), _dataCheck(), datasetElementScopeKeys(), endOf(), Et(), format() (+23 more)

### Community 41 - "DailyReport Model & State"
Cohesion: 0.10
Nodes (11): PruneMissingPhotos, LanguageSwitcher, Locale, DailyReportPhoto, Carbon, LocaleContext, Locale, Illuminate\Console\Command (+3 more)

### Community 42 - "Filament Panel Config"
Cohesion: 0.09
Nodes (13): ChangePassword, Dashboard, static, UserFactory, Filament\Forms\Components\TextInput, Filament\Forms\Concerns\InteractsWithForms, Filament\Forms\Contracts\HasForms, Filament\Pages\Page (+5 more)

### Community 43 - "Chart.js Library"
Cohesion: 0.10
Nodes (30): afterDatasetsUpdate(), buildOrUpdateControllers(), _destroyDatasetMeta(), generateLabels(), getController(), getDatasetMeta(), getDataVisibility(), _getLegendItemAt() (+22 more)

### Community 44 - "Laravel Echo JS"
Cohesion: 0.09
Nodes (14): a(), ar(), b(), cr(), H(), ji(), L(), Me() (+6 more)

### Community 45 - "Chart.js Library"
Cohesion: 0.08
Nodes (11): constructor(), define(), getExtension(), _getTestState(), getType(), registerListeners(), yt(), jn() (+3 more)

### Community 46 - "PRD Specification"
Cohesion: 0.09
Nodes (28): Bi-Weekly Payroll Cycle (14-day), Camera-Only Capture Rule (SE + HRD), Client Filament Portal Removal, daily_report_photos (before/after pair), daily_report_revisions (snapshot history), Daily Report State Machine (draft→need_approval→published + revision_requested), daily_report_workers (allocation, NOT payroll source), daily_reports table (shift-based) (+20 more)

### Community 47 - "Chart.js Library"
Cohesion: 0.11
Nodes (27): buildOrUpdateScales(), cl(), _computeLabelSizes(), D(), E(), ensureScalesHaveIDs(), Eo(), Fo() (+19 more)

### Community 48 - "Rich Text Editor"
Cohesion: 0.09
Nodes (26): actionIsExternal(), canBeConsolidatedWith(), canInvokeAction(), compositionControllerDidBlur(), compositionControllerDidRender(), compositionControllerDidSyncDocumentView(), compositionDidAddAttachment(), compositionDidChangeAttachmentPreviewURL() (+18 more)

### Community 49 - "Chart.js Library"
Cohesion: 0.12
Nodes (25): afterDatasetsUpdate(), _d(), generateLabels(), getDatasetMeta(), getDataVisibility(), getMaxBorderWidth(), getStyle(), _handleEvent() (+17 more)

### Community 50 - "Rich Text Editor"
Cohesion: 0.10
Nodes (24): attachmentForFile(), attributesForFile(), compositionShouldAcceptFile(), didChangeAttributes(), getContentType(), getCurrentTextAttributes(), getHeight(), getHref() (+16 more)

### Community 51 - "Laravel Echo JS"
Cohesion: 0.17
Nodes (22): B(), C(), D(), H(), I(), J(), O(), U() (+14 more)

### Community 52 - "Chart.js Library"
Cohesion: 0.11
Nodes (23): add(), createResolver(), datasetAnimationScopeKeys(), datasetElementScopeKeys(), datasetScopeKeys(), fu(), _getAnims(), gn() (+15 more)

### Community 53 - "Chart.js Library"
Cohesion: 0.12
Nodes (23): _a(), add(), al(), beforeUpdate(), _cachedScopes(), cancel(), _createDescriptors(), _descriptors() (+15 more)

### Community 54 - "NPM Package Config"
Cohesion: 0.09
Nodes (21): axios, concurrently, laravel-vite-plugin, allowScripts, esbuild@0.28.2, devDependencies, axios, concurrently (+13 more)

### Community 55 - "Filament Support JS"
Cohesion: 0.12
Nodes (16): [g](), d(), ee(), et(), g(), h(), J(), M() (+8 more)

### Community 56 - "Chart.js Library"
Cohesion: 0.12
Nodes (22): alpha(), en(), _getUniformDataChanges(), Hi(), interpolate(), Io(), Jo(), Ko() (+14 more)

### Community 57 - "Chart.js Library"
Cohesion: 0.11
Nodes (22): be(), beforeDraw(), dataset(), ea(), fe(), _getSortedDatasetMetas(), getSortedVisibleDatasetMetas(), getVisibleDatasetCount() (+14 more)

### Community 58 - "Filament Support JS"
Cohesion: 0.16
Nodes (21): da(), fa(), Fi(), fn(), S(), Ii(), je(), Li() (+13 more)

### Community 59 - "Chart.js Library"
Cohesion: 0.13
Nodes (21): xg(), ac(), Ai(), ca(), ec(), Fc(), G(), getIndexAngle() (+13 more)

### Community 60 - "Application Services"
Cohesion: 0.20
Nodes (7): DailyReportPhotoService, Illuminate\Contracts\Filesystem\Filesystem, Illuminate\Http\UploadedFile, Intervention\Image\ImageManager, RuntimeException, notAnImage(), photoImage()

### Community 61 - "Chart.js Library"
Cohesion: 0.14
Nodes (19): buildTicks(), _computeAngle(), computeTickLimit(), diff(), _generate(), _getLabelCapacity(), _getLabelSize(), getTickLimit() (+11 more)

### Community 62 - "Service Providers"
Cohesion: 0.20
Nodes (16): BezhanSalleh\FilamentShield\FilamentShieldPlugin, Filament\Http\Middleware\Authenticate, Filament\Http\Middleware\AuthenticateSession, Filament\Http\Middleware\DisableBladeIconComponents, Filament\Http\Middleware\DispatchServingFilamentEvent, Filament\Pages, Filament\Support\Colors\Color, Filament\View\PanelsRenderHook (+8 more)

### Community 63 - "Markdown Editor"
Cohesion: 0.14
Nodes (18): Aa(), cf(), ed(), Jc(), kn(), Ln(), ma(), nd() (+10 more)

### Community 64 - "appendAttachmentWithAttributes()"
Cohesion: 0.22
Nodes (18): appendAttachmentWithAttributes(), appendBlockForAttributesWithElement(), appendBlockForElement(), appendBlockForTextNode(), appendEmptyBlock(), appendPiece(), appendStringWithAttributes(), findBlockElementAncestors() (+10 more)

### Community 65 - "ArrowLeft()"
Cohesion: 0.15
Nodes (18): ArrowLeft(), ArrowRight(), attachmentManagerDidRequestRemovalOfAttachment(), compositionControllerDidRequestRemovalOfAttachment(), editAttachment(), expandSelectionInDirection(), getAttachmentAtRange(), getExpandedRangeInDirection() (+10 more)

### Community 66 - "Select Component"
Cohesion: 0.18
Nodes (5): Ne(), nt(), Qe(), Se, xe()

### Community 67 - "Chart.js Library"
Cohesion: 0.16
Nodes (18): At(), ba(), Bi(), Bs(), bu(), cc(), describe(), getPadding() (+10 more)

### Community 68 - "Weight Validation Rules"
Cohesion: 0.18
Nodes (5): MilestoneWeightsTotalRule, SubJobsWeightsTotalRule, WeightValidation, Closure, Illuminate\Contracts\Validation\ValidationRule

### Community 69 - "Chart.js Library"
Cohesion: 0.15
Nodes (17): acquireContext(), datasetAnimationScopeKeys(), getContext(), getLineWidthForValue(), ha(), ir(), ja(), Mc() (+9 more)

### Community 70 - "Chart.js Library"
Cohesion: 0.15
Nodes (17): addEventListener(), bindEvents(), bindResponsiveEvents(), bindUserEvents(), _checkEventBindings(), cs(), _destroy(), Ei() (+9 more)

### Community 71 - "Select Component"
Cohesion: 0.23
Nodes (4): a(), c(), r(), v()

### Community 72 - "Composer Config"
Cohesion: 0.13
Nodes (15): scripts, dev, post-autoload-dump, post-update-cmd, pre-package-uninstall, test, Composer\\Config::disableProcessTimeout, Illuminate\\Foundation\\ComposerScripts::postAutoloadDump (+7 more)

### Community 73 - "Color Picker Component"
Cohesion: 0.14
Nodes (3): style(), update(), [x]()

### Community 74 - "findIndexAndOffsetAtPosition()"
Cohesion: 0.15
Nodes (15): findIndexAndOffsetAtPosition(), getObjectAtIndex(), getObjectAtPosition(), getSplittableListInRange(), insertObjectAtIndex(), insertSplittableListAtIndex(), insertSplittableListAtPosition(), removeObjectAtIndex() (+7 more)

### Community 75 - "Eloquent Models"
Cohesion: 0.19
Nodes (4): Controller, GeneratedDocumentDownloadController, GeneratedDocument, Symfony\Component\HttpFoundation\StreamedResponse

### Community 76 - "Composer Config"
Cohesion: 0.14
Nodes (13): description, extra, laravel, keywords, dont-discover, license, minimum-stability, name (+5 more)

### Community 77 - "Composer Config"
Cohesion: 0.17
Nodes (12): require, barryvdh/laravel-dompdf, bezhansalleh/filament-shield, fakerphp/faker, filament/filament, intervention/image-laravel, laravel/framework, laravel/tinker (+4 more)

### Community 78 - "Laravel Echo JS"
Cohesion: 0.17
Nodes (12): Be(), ei(), ii(), le(), ni(), oi(), r(), ri() (+4 more)

### Community 79 - "Laravel Echo JS"
Cohesion: 0.20
Nodes (11): di(), e(), g(), Ht(), i(), Ie(), Re(), t() (+3 more)

### Community 80 - "Chart.js Library"
Cohesion: 0.20
Nodes (12): c(), o(), _p(), qp(), s(), Sg(), xt(), Ye() (+4 more)

### Community 81 - "Select Component"
Cohesion: 0.26
Nodes (11): p(), ce, l(), n(), s(), t(), U(), ce() (+3 more)

### Community 82 - "DateTime Picker Component"
Cohesion: 0.27
Nodes (7): e(), i(), l(), Ni(), o(), t(), u()

### Community 83 - "Chart.js Library"
Cohesion: 0.25
Nodes (11): aa(), determineDataLimits(), Dh(), _getLabelBounds(), getMinMax(), _getOtherScale(), getUserBounds(), handleTickRangeOptions() (+3 more)

### Community 84 - "Composer Config"
Cohesion: 0.20
Nodes (10): require-dev, larastan/larastan, laravel/pail, laravel/pint, laravel/sail, mockery/mockery, nunomaduro/collision, pestphp/pest (+2 more)

### Community 85 - "Laravel Echo JS"
Cohesion: 0.20
Nodes (10): Ce(), De(), Dt(), Fe(), He(), ir(), Mt(), nr() (+2 more)

### Community 86 - "File Upload Component"
Cohesion: 0.24
Nodes (10): ba(), e(), Ip(), It(), lt(), sa(), Wp(), Wt() (+2 more)

### Community 87 - "Chart.js Library"
Cohesion: 0.31
Nodes (10): dd(), Jl(), lr(), md(), ot(), rd(), uf(), xl() (+2 more)

### Community 88 - "Weight Validation Rules"
Cohesion: 0.28
Nodes (4): MilestoneStartDateRule, ScheduleValidator, Carbon\Carbon, DateTimeInterface

### Community 89 - "HTTP Controllers"
Cohesion: 0.43
Nodes (4): EnsurePasswordChanged, SetLocale, Illuminate\Http\Request, Symfony\Component\HttpFoundation\Response

### Community 90 - "Client & Project Models"
Cohesion: 0.32
Nodes (4): AdminPanelProvider, ClientPanelProvider, Filament\Panel, Filament\PanelProvider

### Community 91 - "Composer Config"
Cohesion: 0.25
Nodes (8): post-root-package-install, setup, composer install, npm install, npm run build, @php artisan key:generate, @php artisan migrate --force, @php -r \"file_exists('.env') || copy('.env.example', '.env');\

### Community 92 - "File Upload Component"
Cohesion: 0.46
Nodes (8): de(), Fe(), Gt(), j(), je(), le(), vt(), Zp()

### Community 93 - "Service Providers"
Cohesion: 0.33
Nodes (4): AppServiceProvider, Illuminate\Cache\RateLimiting\Limit, Illuminate\Support\Facades\RateLimiter, Illuminate\Support\ServiceProvider

### Community 94 - "Composer Config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 97 - "Docker Config"
Cohesion: 0.71
Nodes (6): ensure_env(), log(), provision(), entrypoint.sh script, wait_for_minio(), wait_for_pgsql()

### Community 98 - "File Upload Component"
Cohesion: 0.29
Nodes (7): bi(), jp(), ol(), Tp(), xl(), xp(), yl()

### Community 100 - "Test Suite"
Cohesion: 0.40
Nodes (3): Illuminate\Foundation\Testing\TestCase, ExampleTest, TestCase

### Community 101 - "File Upload Component"
Cohesion: 0.40
Nodes (6): ca(), rl(), Rp(), Sp(), vp(), yp()

### Community 108 - "Laravel Bootstrap"
Cohesion: 0.40
Nodes (3): Illuminate\Foundation\Application, Illuminate\Foundation\Configuration\Exceptions, Illuminate\Foundation\Configuration\Middleware

### Community 109 - "Composer Config"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 110 - "Composer Config"
Cohesion: 0.40
Nodes (5): autoload-dev, files, psr-4, Tests\\, tests/Support/helpers.php

### Community 111 - "Laravel Config"
Cohesion: 0.40
Nodes (4): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler, Monolog\Processor\PsrLogMessageProcessor

### Community 112 - "File Upload Component"
Cohesion: 0.60
Nodes (5): clickPercent(), getPosition(), mouseUp(), moveplayhead(), timelineClicked()

### Community 115 - "Chart.js Library"
Cohesion: 0.50
Nodes (5): ad(), nd(), Oa(), path(), rd()

### Community 116 - "Composer Config"
Cohesion: 0.50
Nodes (4): post-create-project-cmd, @php artisan key:generate --ansi, @php artisan migrate --graceful --ansi, @php -r \"file_exists('database/database.sqlite') || touch('database/database.sqlite');\

### Community 117 - "Laravel Config"
Cohesion: 0.50
Nodes (3): Spatie\Activitylog\Actions\CleanActivityLogAction, Spatie\Activitylog\Actions\LogActivityAction, Spatie\Activitylog\Models\Activity

## Knowledge Gaps
- **91 isolated node(s):** `@php artisan key:generate --ansi`, `@php artisan migrate --graceful --ansi`, `@php -r \"file_exists('database/database.sqlite') || touch('database/database.sqlite');\`, `create-testing-database.sh script`, `create-testing-database.sh script` (+86 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **27 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Br()` connect `Chart.js Library` to `Chart.js Library`, `Rich Text Editor`, `Laravel Echo JS`, `Chart.js Library`, `Rich Text Editor`?**
  _High betweenness centrality (0.087) - this node is a cross-community bridge._
- **Why does `Ls()` connect `Chart.js Library` to `Chart.js Library`, `Chart.js Library`?**
  _High betweenness centrality (0.046) - this node is a cross-community bridge._
- **Why does `constructor()` connect `Rich Text Editor` to `Chart.js Library`, `Rich Text Editor`, `Rich Text Editor`, `Rich Text Editor`, `Rich Text Editor`, `Markdown Editor`, `Rich Text Editor`, `Rich Text Editor`, `File Upload Component`, `Rich Text Editor`, `Rich Text Editor`, `Rich Text Editor`?**
  _High betweenness centrality (0.044) - this node is a cross-community bridge._
- **Are the 3 inferred relationships involving `_update()` (e.g. with `g()` and `f()`) actually correct?**
  _`_update()` has 3 INFERRED edges - model-reasoned connections that need verification._
- **Are the 13 inferred relationships involving `x()` (e.g. with `D()` and `g()`) actually correct?**
  _`x()` has 13 INFERRED edges - model-reasoned connections that need verification._
- **Are the 2 inferred relationships involving `_update()` (e.g. with `g()` and `f()`) actually correct?**
  _`_update()` has 2 INFERRED edges - model-reasoned connections that need verification._
- **What connects `@php artisan key:generate --ansi`, `@php artisan migrate --graceful --ansi`, `@php -r \"file_exists('database/database.sqlite') || touch('database/database.sqlite');\` to the rest of the system?**
  _91 weakly-connected nodes found - possible documentation gaps or missing edges._