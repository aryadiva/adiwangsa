# Graph Report - adiwangsa  (2026-08-19)

## Corpus Check
- 5 files · ~99,800 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 4244 nodes · 12308 edges · 234 communities (212 shown, 22 thin omitted)
- Extraction: 88% EXTRACTED · 12% INFERRED · 0% AMBIGUOUS · INFERRED: 1473 edges (avg confidence: 0.58)
- Token cost: 0 input · 0 output

## Community Hubs (Navigation)
- Vendor JS bundle
- Vendor JS bundle
- Vendor JS bundle
- Vendor JS bundle
- Vendor JS bundle
- Vendor JS bundle
- Vendor JS bundle
- Vendor JS bundle
- Vendor JS bundle
- Vendor JS bundle
- Policies & RBAC authorization
- Vendor JS bundle
- Vendor JS bundle
- Filament Resources (forms/tables)
- Vendor JS bundle
- Vendor JS bundle
- Eloquent Models & casts
- Vendor JS bundle
- Vendor JS bundle
- Vendor JS bundle
- Vendor JS bundle
- Vendor JS bundle
- Tests & DB fixtures
- Vendor JS bundle
- Vendor JS bundle
- Vendor JS bundle
- Services & DTOs layer
- Vendor JS bundle
- Notifications & related models
- Vendor JS bundle
- Providers & HTTP bootstrap
- Vendor JS bundle
- Vendor JS bundle
- Vendor JS bundle
- Filament — Project resource
- Vendor JS bundle
- Vendor JS bundle
- Vendor JS bundle
- Vendor JS bundle
- Vendor JS bundle
- Vendor JS bundle
- Vendor JS bundle
- Vendor JS bundle
- Vendor JS bundle
- Vendor JS bundle
- Vendor JS bundle
- Service — DailyReport photos
- Vendor JS bundle
- Vendor JS bundle
- Agent docs (AGENTS files)
- Vendor JS bundle
- Livewire locale & support
- Node deps (axios/concurrently)
- Vendor JS bundle
- Enums & database definitions
- Filament — Client resource
- Vendor JS bundle
- Vendor JS bundle
- Vendor JS bundle
- Vendor JS bundle
- Vendor JS bundle
- Vendor JS bundle
- Vendor JS bundle
- Filament — DailyReport resource
- Build scripts (node/composer)
- Vendor JS bundle
- Composer — laravel deps
- Vendor JS bundle
- Vendor JS bundle
- Vendor JS bundle
- Composer — dompdf dep
- Vendor JS bundle
- Vendor JS bundle
- Vendor JS bundle
- Vendor JS bundle
- Vendor JS bundle
- Vendor JS bundle
- Composer — larastan dev deps
- Vendor JS bundle
- Vendor JS bundle
- Composer — setup scripts
- Vendor JS bundle
- Vendor JS bundle
- FilamentShield seeder
- Composer — pest dev deps
- DB — schema migrations (early)
- DB — schema migrations (report)
- Docker — entrypoint
- Vendor JS bundle
- Vendor JS bundle
- DB — activity log table
- Tests — Laravel base TestCase
- Vendor JS bundle
- Vendor JS bundle
- Vendor JS bundle
- Vendor JS bundle
- Vendor JS bundle
- Vendor JS bundle
- Composer — autoload psr-4
- Composer — autoload-dev
- Config — logging
- Vendor JS bundle
- Vendor JS bundle
- Vendor JS bundle
- Composer — post-autoload-dump
- Config — activitylog
- DB — must_change_password column
- DB — locale column
- Routes — console (artisan)
- Docker — start-container
- Docker — start-container
- Docker — start-container
- Docker — start-container
- Docker — start-container
- Docker — start-container
- Docker — test db script
- Resources — frontend JS
- View — change password
- Artisan CLI entrypoint

## God Nodes (most connected - your core abstractions)
1. `User` - 117 edges
2. `_update()` - 90 edges
3. `x()` - 87 edges
4. `_update()` - 85 edges
5. `DailyReport` - 80 edges
6. `te()` - 74 edges
7. `Project` - 67 edges
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
- **Bi-weekly payroll pipeline (attendance → pay)** — docs_prd_v2_worker_attendance, docs_prd_v2_payroll_runs, docs_prd_v2_payroll_items, docs_prd_v2_biweekly_payroll [EXTRACTED 0.95]
- **Delay detection → cascade → mitigation workflow** — docs_prd_v2_sub_job_delay_events, docs_prd_v2_delay_state_machine, docs_prd_v2_delay_cascade, docs_prd_v2_milestone_sub_jobs [EXTRACTED 0.90]
- **Shift-based reporting with target/deficit engine** — docs_prd_v2_daily_reports, docs_prd_v2_milestone_sub_jobs, docs_prd_v2_deficit_carry_forward, docs_prd_v2_daily_report_photos, docs_prd_v2_camera_only_capture [INFERRED 0.85]

## Communities (234 total, 22 thin omitted)

### Community 0 - "Vendor JS bundle"
Cohesion: 0.01
Nodes (107): acquireContext(), addControllers(), addPlugins(), addScales(), Ag(), alpha(), Au(), beforeDatasetDraw() (+99 more)

### Community 1 - "Vendor JS bundle"
Cohesion: 0.02
Nodes (133): A(), activateAttributeIfSupported(), appendStringToTextAtIndex(), applyBlockAttribute(), attachmentDidChangeUploadProgress(), attachmentIsManaged(), attributeChangedCallback(), Ca() (+125 more)

### Community 2 - "Vendor JS bundle"
Cohesion: 0.02
Nodes (116): aa(), active(), addControllers(), addElements(), addPlugins(), addScales(), an(), _animateOptions() (+108 more)

### Community 3 - "Vendor JS bundle"
Cohesion: 0.04
Nodes (122): Ac(), ad(), af(), ai(), al(), An(), ao(), bf() (+114 more)

### Community 4 - "Vendor JS bundle"
Cohesion: 0.05
Nodes (65): $c(), me(), D(), E(), g(), H(), Id(), J() (+57 more)

### Community 5 - "Vendor JS bundle"
Cohesion: 0.04
Nodes (112): addAttribute(), addAttributeAtRange(), addAttributesAtRange(), addHTMLAttribute(), appendText(), applyBlockAttributeAtRange(), canBeGroupedWith(), canDecreaseBlockAttributeLevel() (+104 more)

### Community 6 - "Vendor JS bundle"
Cohesion: 0.07
Nodes (101): be(), _a(), Ae(), ar(), as(), Ba(), Bc(), Be() (+93 more)

### Community 7 - "Vendor JS bundle"
Cohesion: 0.03
Nodes (102): Sg(), _a(), abutsStart(), after(), afterAutoSkip(), Ai(), before(), Bi() (+94 more)

### Community 8 - "Vendor JS bundle"
Cohesion: 0.04
Nodes (101): af(), average(), Bt(), Ca(), cd(), ch(), cn(), co() (+93 more)

### Community 9 - "Vendor JS bundle"
Cohesion: 0.04
Nodes (100): addBox(), addEventListener(), afterBuildTicks(), afterCalculateLabelRotation(), afterDataLimits(), afterDraw(), afterFit(), afterSetDimensions() (+92 more)

### Community 10 - "Policies & RBAC authorization"
Cohesion: 0.04
Nodes (27): ProjectMilestone, User, Worker, ClientPolicy, ProjectMilestonePolicy, ProjectPolicy, RolePolicy, SitePolicy (+19 more)

### Community 11 - "Vendor JS bundle"
Cohesion: 0.04
Nodes (94): adjustHitBoxes(), afterBuildTicks(), afterCalculateLabelRotation(), afterDataLimits(), afterDraw(), afterFit(), afterSetDimensions(), afterTickToLabelConversion() (+86 more)

### Community 12 - "Vendor JS bundle"
Cohesion: 0.04
Nodes (88): ad(), Ah(), applyStack(), aspectRatio(), bf(), buildTicks(), C(), _calculateBarIndexPixels() (+80 more)

### Community 13 - "Filament Resources (forms/tables)"
Cohesion: 0.04
Nodes (31): ChangePassword, Dashboard, ClientResource, App\Filament\Resources\ClientResource\Pages, DailyReportResource, App\Filament\Resources\DailyReportResource\Pages, GeneratedDocumentResource, App\Filament\Resources\ProjectResource\Pages (+23 more)

### Community 14 - "Vendor JS bundle"
Cohesion: 0.12
Nodes (78): at(), B(), he(), br(), Bt(), X(), ca(), cf() (+70 more)

### Community 15 - "Vendor JS bundle"
Cohesion: 0.05
Nodes (58): ba(), bi(), c(), ca(), clickPercent(), constructor(), de(), define() (+50 more)

### Community 16 - "Eloquent Models & casts"
Cohesion: 0.07
Nodes (63): _a(), aa(), ai(), ba(), Be(), br(), T(), Ca() (+55 more)

### Community 17 - "Vendor JS bundle"
Cohesion: 0.06
Nodes (69): chartOptionScopes(), average(), ba(), br(), c(), cr(), Ct(), l() (+61 more)

### Community 18 - "Vendor JS bundle"
Cohesion: 0.06
Nodes (19): PruneMissingPhotos, DailyReportPhoto, DailyReportRevision, DailyReportWorker, LogOptions, LogOptions, DomainException, Illuminate\Console\Command (+11 more)

### Community 19 - "Vendor JS bundle"
Cohesion: 0.07
Nodes (64): adjustHitBoxes(), ae(), beforeDraw(), _computeGridLineItems(), _computeLabelArea(), _computeTitleHeight(), cs(), df() (+56 more)

### Community 20 - "Vendor JS bundle"
Cohesion: 0.08
Nodes (22): DocumentType, ReportDataDTO, App\Filament\Resources\GeneratedDocumentResource\Pages, Controller, GeneratedDocumentDownloadController, GeneratePdfJob, GeneratedDocument, PdfDocumentService (+14 more)

### Community 21 - "Vendor JS bundle"
Cohesion: 0.07
Nodes (25): Client, Project, Site, ClientFactory, DailyReportFactory, static, ProjectFactory, SiteFactory (+17 more)

### Community 22 - "Tests & DB fixtures"
Cohesion: 0.11
Nodes (59): Cn(), b(), Bt(), Ct(), dn(), Dt(), Ft(), G() (+51 more)

### Community 23 - "Vendor JS bundle"
Cohesion: 0.07
Nodes (58): breakFormattedBlock(), breaksOnReturn(), canSetCurrentAttribute(), canSetCurrentBlockAttribute(), createCaptionElement(), decreaseBlockAttributeLevel(), decreaseListLevel(), didFocus() (+50 more)

### Community 24 - "Vendor JS bundle"
Cohesion: 0.04
Nodes (12): Aa(), Bi(), bn(), ji(), kd(), qd(), Ri(), te() (+4 more)

### Community 25 - "Vendor JS bundle"
Cohesion: 0.12
Nodes (51): Qt(), Ae(), ar(), at(), Bi(), I(), c(), H() (+43 more)

### Community 26 - "Services & DTOs layer"
Cohesion: 0.05
Nodes (54): backspace(), createLinkHTML(), cut(), d(), delete(), deleteByComposition(), deleteByCut(), deleteByDrag() (+46 more)

### Community 27 - "Vendor JS bundle"
Cohesion: 0.06
Nodes (53): add(), applyKeyboardCommand(), attachmentDidChangeAttributes(), attachmentEditorDidRequestRemovalOfAttachment(), canBeGrouped(), canSyncDocumentView(), checkValidity(), compositionDidChangeDocument() (+45 more)

### Community 28 - "Notifications & related models"
Cohesion: 0.06
Nodes (52): add(), Al(), ar(), cf(), count(), diff(), Dl(), endOf() (+44 more)

### Community 29 - "Vendor JS bundle"
Cohesion: 0.06
Nodes (49): attachFiles(), beforeinput(), canApplyToDocument(), compositionend(), compositionstart(), compositionupdate(), constructor(), dragend() (+41 more)

### Community 30 - "Providers & HTTP bootstrap"
Cohesion: 0.06
Nodes (48): afterAutoSkip(), Ao(), Bi(), buildLookupTable(), buildTicks(), _computeAngle(), computeTickLimit(), determineDataLimits() (+40 more)

### Community 31 - "Vendor JS bundle"
Cohesion: 0.07
Nodes (32): EnsurePasswordChanged, SetLocale, AppServiceProvider, AdminPanelProvider, ClientPanelProvider, BezhanSalleh\FilamentShield\FilamentShieldPlugin, Closure, Filament\Http\Middleware\Authenticate (+24 more)

### Community 32 - "Vendor JS bundle"
Cohesion: 0.06
Nodes (42): as(), At(), ba(), Bs(), bu(), cc(), constructor(), De() (+34 more)

### Community 33 - "Vendor JS bundle"
Cohesion: 0.08
Nodes (17): EditClient, ListClients, ListDailyReports, ListGeneratedDocuments, EditProject, ListProjects, EditSite, ListSites (+9 more)

### Community 34 - "Filament — Project resource"
Cohesion: 0.06
Nodes (39): Ac(), Bl(), clone(), dtFormatter(), eg(), el(), eras(), extract() (+31 more)

### Community 35 - "Vendor JS bundle"
Cohesion: 0.09
Nodes (39): applyStack(), ar(), as(), aspectRatio(), _calculateBarIndexPixels(), _calculateBarValuePixels(), _computeGridLineItems(), countVisibleElements() (+31 more)

### Community 36 - "Vendor JS bundle"
Cohesion: 0.08
Nodes (38): canAcceptDataTransfer(), canDecreaseNestingLevel(), canIncreaseNestingLevel(), compositionControllerDidFocus(), compositionDidRequestChangingSelectionToLocationRange(), createDOMRangeFromPoint(), createLocationRangeFromDOMRange(), decreaseNestingLevel() (+30 more)

### Community 37 - "Vendor JS bundle"
Cohesion: 0.11
Nodes (7): DailyReport, DailyReportStatus, LogOptions, DailyReportPolicy, clientReport(), reportFor(), draftReport()

### Community 38 - "Vendor JS bundle"
Cohesion: 0.12
Nodes (9): PdfReadyNotification, ReportApprovedNotification, ReportPublishedNotification, ReportSubmittedNotification, RevisionRequestedNotification, Illuminate\Bus\Queueable, Illuminate\Contracts\Queue\ShouldQueue, Illuminate\Notifications\Messages\MailMessage (+1 more)

### Community 39 - "Vendor JS bundle"
Cohesion: 0.09
Nodes (10): CreateClient, CreateDailyReport, EditDailyReport, DailyReportStatus, CreateProject, CreateSite, CreateUser, CreateWorker (+2 more)

### Community 40 - "Vendor JS bundle"
Cohesion: 0.07
Nodes (30): contains(), ei(), en(), e(), eu(), formats(), gu(), hi() (+22 more)

### Community 41 - "Vendor JS bundle"
Cohesion: 0.12
Nodes (31): buildOrUpdateElements(), C(), Co(), _dataCheck(), datasetElementScopeKeys(), endOf(), Et(), format() (+23 more)

### Community 42 - "Vendor JS bundle"
Cohesion: 0.09
Nodes (15): a(), ar(), b(), cr(), H(), ji(), L(), Me() (+7 more)

### Community 43 - "Vendor JS bundle"
Cohesion: 0.10
Nodes (30): afterDatasetsUpdate(), buildOrUpdateControllers(), _destroyDatasetMeta(), generateLabels(), getController(), getDatasetMeta(), getDataVisibility(), _getLegendItemAt() (+22 more)

### Community 44 - "Vendor JS bundle"
Cohesion: 0.10
Nodes (29): xt(), cacheViewForObject(), copyUsingObjectMap(), copyUsingObjectsFromDocument(), createAttachmentNodes(), createChildView(), createContainerElement(), createDocumentFragmentForSync() (+21 more)

### Community 45 - "Vendor JS bundle"
Cohesion: 0.10
Nodes (29): afterDatasetsUpdate(), _d(), fa(), generateLabels(), getDatasetMeta(), getDataVisibility(), _getLegendItemAt(), getMaxBorderWidth() (+21 more)

### Community 46 - "Service — DailyReport photos"
Cohesion: 0.09
Nodes (28): Bi-Weekly Payroll Cycle (14-day), Camera-Only Capture Rule (SE + HRD), Client Filament Portal Removal, daily_report_photos (before/after pair), daily_report_revisions (snapshot history), Daily Report State Machine (draft→need_approval→published + revision_requested), daily_report_workers (allocation, NOT payroll source), daily_reports table (shift-based) (+20 more)

### Community 47 - "Vendor JS bundle"
Cohesion: 0.08
Nodes (28): attachmentForFile(), attributesForFile(), canSetCurrentTextAttribute(), compositionShouldAcceptFile(), didChangeAttributes(), getContentType(), getCurrentTextAttributes(), getHeight() (+20 more)

### Community 48 - "Vendor JS bundle"
Cohesion: 0.09
Nodes (27): At(), be(), beforeDraw(), dataset(), ea(), Fa(), fe(), getMaximumSize() (+19 more)

### Community 49 - "Agent docs (AGENTS files)"
Cohesion: 0.11
Nodes (27): buildOrUpdateScales(), cl(), _computeLabelSizes(), D(), E(), ensureScalesHaveIDs(), Eo(), Fo() (+19 more)

### Community 50 - "Vendor JS bundle"
Cohesion: 0.08
Nodes (3): duration(), persistent(), seconds()

### Community 51 - "Livewire locale & support"
Cohesion: 0.17
Nodes (22): B(), C(), D(), H(), I(), J(), O(), U() (+14 more)

### Community 52 - "Node deps (axios/concurrently)"
Cohesion: 0.12
Nodes (23): _a(), add(), al(), beforeUpdate(), _cachedScopes(), cancel(), _createDescriptors(), _descriptors() (+15 more)

### Community 53 - "Vendor JS bundle"
Cohesion: 0.09
Nodes (21): axios, concurrently, laravel-vite-plugin, allowScripts, esbuild@0.28.2, devDependencies, axios, concurrently (+13 more)

### Community 54 - "Enums & database definitions"
Cohesion: 0.19
Nodes (22): da(), fa(), Fi(), fn(), S(), Ii(), je(), Jr() (+14 more)

### Community 55 - "Filament — Client resource"
Cohesion: 0.12
Nodes (22): alpha(), en(), _getUniformDataChanges(), Hi(), interpolate(), Io(), Jo(), Ko() (+14 more)

### Community 56 - "Vendor JS bundle"
Cohesion: 0.13
Nodes (10): LanguageSwitcher, Locale, Carbon, LocaleContext, Locale, Carbon\Carbon, DateTimeInterface, DateTimeZone (+2 more)

### Community 57 - "Vendor JS bundle"
Cohesion: 0.12
Nodes (21): an(), color(), darken(), Dc(), desaturate(), eo(), hexString(), lighten() (+13 more)

### Community 58 - "Vendor JS bundle"
Cohesion: 0.13
Nodes (21): xg(), ac(), Ai(), ca(), ec(), Fc(), G(), getIndexAngle() (+13 more)

### Community 59 - "Vendor JS bundle"
Cohesion: 0.18
Nodes (20): It(), appendAttachmentWithAttributes(), appendBlockForAttributesWithElement(), appendBlockForElement(), appendBlockForTextNode(), appendEmptyBlock(), appendPiece(), appendStringWithAttributes() (+12 more)

### Community 60 - "Vendor JS bundle"
Cohesion: 0.12
Nodes (20): actionIsExternal(), canInvokeAction(), compositionControllerDidBlur(), compositionControllerDidSyncDocumentView(), compositionDidAddAttachment(), compositionDidChangeAttachmentPreviewURL(), compositionDidChangeCurrentAttributes(), compositionDidEditAttachment() (+12 more)

### Community 61 - "Vendor JS bundle"
Cohesion: 0.11
Nodes (20): box(), canBeConsolidatedWith(), canRedo(), canUndo(), compositionControllerDidRender(), createEntry(), fromUCS2String(), getTargetDOMRange() (+12 more)

### Community 62 - "Vendor JS bundle"
Cohesion: 0.20
Nodes (7): DailyReportPhotoService, Illuminate\Contracts\Filesystem\Filesystem, Illuminate\Http\UploadedFile, Intervention\Image\ImageManager, RuntimeException, notAnImage(), photoImage()

### Community 63 - "Filament — DailyReport resource"
Cohesion: 0.15
Nodes (18): ArrowLeft(), ArrowRight(), attachmentManagerDidRequestRemovalOfAttachment(), compositionControllerDidRequestRemovalOfAttachment(), editAttachment(), expandSelectionInDirection(), getAttachmentAtRange(), getExpandedRangeInDirection() (+10 more)

### Community 64 - "Build scripts (node/composer)"
Cohesion: 0.16
Nodes (18): addElements(), buildOrUpdateControllers(), buildOrUpdateElements(), _dataCheck(), _destroy(), _destroyDatasetMeta(), getDataset(), hs() (+10 more)

### Community 65 - "Vendor JS bundle"
Cohesion: 0.12
Nodes (5): [g](), style(), update(), [x](), tt()

### Community 66 - "Composer — laravel deps"
Cohesion: 0.15
Nodes (17): acquireContext(), datasetAnimationScopeKeys(), getContext(), getLineWidthForValue(), ha(), ir(), ja(), Mc() (+9 more)

### Community 67 - "Vendor JS bundle"
Cohesion: 0.15
Nodes (17): addEventListener(), bindEvents(), bindResponsiveEvents(), bindUserEvents(), _checkEventBindings(), cs(), _destroy(), Ei() (+9 more)

### Community 68 - "Vendor JS bundle"
Cohesion: 0.13
Nodes (15): scripts, dev, post-autoload-dump, post-update-cmd, pre-package-uninstall, test, Composer\\Config::disableProcessTimeout, Illuminate\\Foundation\\ComposerScripts::postAutoloadDump (+7 more)

### Community 69 - "Vendor JS bundle"
Cohesion: 0.15
Nodes (15): apply(), q(), B(), kn(), lt(), Me(), mo(), ms() (+7 more)

### Community 70 - "Composer — dompdf dep"
Cohesion: 0.14
Nodes (13): description, extra, laravel, keywords, dont-discover, license, minimum-stability, name (+5 more)

### Community 71 - "Vendor JS bundle"
Cohesion: 0.19
Nodes (5): static, UserFactory, Illuminate\Support\Facades\Hash, Illuminate\Support\Str, Pdo\Mysql

### Community 72 - "Vendor JS bundle"
Cohesion: 0.20
Nodes (14): active(), _animateOptions(), cancel(), _createAnimations(), _createDescriptors(), _descriptors(), kh(), _notify() (+6 more)

### Community 73 - "Vendor JS bundle"
Cohesion: 0.17
Nodes (12): require, barryvdh/laravel-dompdf, bezhansalleh/filament-shield, fakerphp/faker, filament/filament, intervention/image-laravel, laravel/framework, laravel/tinker (+4 more)

### Community 74 - "Vendor JS bundle"
Cohesion: 0.17
Nodes (12): Be(), ei(), ii(), le(), ni(), oi(), r(), ri() (+4 more)

### Community 75 - "Vendor JS bundle"
Cohesion: 0.20
Nodes (11): di(), e(), g(), Ht(), i(), Ie(), Re(), t() (+3 more)

### Community 77 - "Composer — larastan dev deps"
Cohesion: 0.27
Nodes (7): e(), i(), l(), Ni(), o(), t(), u()

### Community 78 - "Vendor JS bundle"
Cohesion: 0.25
Nodes (11): aa(), determineDataLimits(), Dh(), _getLabelBounds(), getMinMax(), _getOtherScale(), getUserBounds(), handleTickRangeOptions() (+3 more)

### Community 79 - "Vendor JS bundle"
Cohesion: 0.20
Nodes (10): require-dev, larastan/larastan, laravel/pail, laravel/pint, laravel/sail, mockery/mockery, nunomaduro/collision, pestphp/pest (+2 more)

### Community 80 - "Composer — setup scripts"
Cohesion: 0.20
Nodes (10): Ce(), De(), Dt(), Fe(), He(), ir(), Mt(), nr() (+2 more)

### Community 81 - "Vendor JS bundle"
Cohesion: 0.36
Nodes (9): dd(), Jl(), lr(), md(), ot(), rd(), uf(), xl() (+1 more)

### Community 82 - "Vendor JS bundle"
Cohesion: 0.25
Nodes (8): post-root-package-install, setup, composer install, npm install, npm run build, @php artisan key:generate, @php artisan migrate --force, @php -r \"file_exists('.env') || copy('.env.example', '.env');\

### Community 83 - "FilamentShield seeder"
Cohesion: 0.25
Nodes (8): dispatch(), dispatchSelf(), dispatchTo(), emit(), emitSelf(), emitTo(), event(), eventData()

### Community 84 - "Composer — pest dev deps"
Cohesion: 0.25
Nodes (8): h(), l(), Q(), Re(), ur(), v(), Z(), ze()

### Community 85 - "DB — schema migrations (early)"
Cohesion: 0.29
Nodes (3): BezhanSalleh\FilamentShield\Support\Utils, ShieldSeeder, Spatie\Permission\PermissionRegistrar

### Community 86 - "DB — schema migrations (report)"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 89 - "Vendor JS bundle"
Cohesion: 0.71
Nodes (6): ensure_env(), log(), provision(), entrypoint.sh script, wait_for_minio(), wait_for_pgsql()

### Community 90 - "DB — activity log table"
Cohesion: 0.29
Nodes (7): actions(), button(), constructor(), grouped(), link(), name(), view()

### Community 92 - "Vendor JS bundle"
Cohesion: 0.40
Nodes (3): Illuminate\Foundation\Testing\TestCase, ExampleTest, TestCase

### Community 96 - "Vendor JS bundle"
Cohesion: 0.47
Nodes (6): St(), En(), Mt(), On(), vr(), Wr()

### Community 97 - "Vendor JS bundle"
Cohesion: 0.70
Nodes (5): AGENTS.md — Build Rules & Fast-Reference, PRD v2/v3 — Architecture Blueprint & Source of Truth, README.md — Project Overview & Tech Stack, SCAFFOLDING.md — Setup / Install Guide, TASKS.md — Development Tasks & Phases

### Community 98 - "Composer — autoload psr-4"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 99 - "Composer — autoload-dev"
Cohesion: 0.40
Nodes (5): autoload-dev, files, psr-4, Tests\\, tests/Support/helpers.php

### Community 100 - "Config — logging"
Cohesion: 0.40
Nodes (4): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler, Monolog\Processor\PsrLogMessageProcessor

### Community 101 - "Vendor JS bundle"
Cohesion: 0.40
Nodes (5): danger(), info(), status(), success(), warning()

### Community 102 - "Vendor JS bundle"
Cohesion: 0.50
Nodes (4): post-create-project-cmd, @php artisan key:generate --ansi, @php artisan migrate --graceful --ansi, @php -r \"file_exists('database/database.sqlite') || touch('database/database.sqlite');\

### Community 103 - "Vendor JS bundle"
Cohesion: 0.50
Nodes (3): Spatie\Activitylog\Actions\CleanActivityLogAction, Spatie\Activitylog\Actions\LogActivityAction, Spatie\Activitylog\Models\Activity

## Knowledge Gaps
- **84 isolated node(s):** `Illuminate\\Foundation\\ComposerScripts::postAutoloadDump`, `@php artisan filament:upgrade`, `@php artisan package:discover --ansi`, `create-testing-database.sh script`, `create-testing-database.sh script` (+79 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **22 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Br()` connect `Vendor JS bundle` to `Vendor JS bundle`, `Vendor JS bundle`, `Vendor JS bundle`, `Vendor JS bundle`, `Vendor JS bundle`?**
  _High betweenness centrality (0.102) - this node is a cross-community bridge._
- **Why does `Ls()` connect `Vendor JS bundle` to `Vendor JS bundle`, `Vendor JS bundle`?**
  _High betweenness centrality (0.053) - this node is a cross-community bridge._
- **Why does `constructor()` connect `Vendor JS bundle` to `Vendor JS bundle`, `Vendor JS bundle`, `Vendor JS bundle`, `Vendor JS bundle`, `Vendor JS bundle`, `Vendor JS bundle`, `Vendor JS bundle`, `Vendor JS bundle`, `Vendor JS bundle`, `Vendor JS bundle`, `Vendor JS bundle`?**
  _High betweenness centrality (0.051) - this node is a cross-community bridge._
- **Are the 3 inferred relationships involving `_update()` (e.g. with `g()` and `f()`) actually correct?**
  _`_update()` has 3 INFERRED edges - model-reasoned connections that need verification._
- **Are the 13 inferred relationships involving `x()` (e.g. with `D()` and `g()`) actually correct?**
  _`x()` has 13 INFERRED edges - model-reasoned connections that need verification._
- **Are the 2 inferred relationships involving `_update()` (e.g. with `g()` and `f()`) actually correct?**
  _`_update()` has 2 INFERRED edges - model-reasoned connections that need verification._
- **What connects `Illuminate\\Foundation\\ComposerScripts::postAutoloadDump`, `@php artisan filament:upgrade`, `@php artisan package:discover --ansi` to the rest of the system?**
  _84 weakly-connected nodes found - possible documentation gaps or missing edges._