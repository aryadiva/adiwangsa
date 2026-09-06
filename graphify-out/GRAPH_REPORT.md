# Graph Report - adiwangsa  (2026-09-06)

## Corpus Check
- 58 files · ~120,050 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 4661 nodes · 13293 edges · 289 communities (250 shown, 39 thin omitted)
- Extraction: 89% EXTRACTED · 11% INFERRED · 0% AMBIGUOUS · INFERRED: 1512 edges (avg confidence: 0.59)
- Token cost: 14,000 input · 4,200 output

## Community Hubs (Navigation)
- Filament Chart.js Bundle
- Filament Rich Editor Core
- Filament Markdown Editor
- Stats Overview Widget Charts
- Chart.js Scale Layer
- Chart.js Bar Layout Engine
- Projects, Milestones & Sub-Jobs
- Rich Editor Input Handling
- Chart.js Scale Internals
- Markdown Editor Utilities
- Markdown Editor Helpers
- Chart.js Platform Layer
- Payroll Runs & Cycles
- Rich Editor Attributes API
- Community 14
- Shared Enums, DTOs & Attendance
- Community 16
- Community 17
- Community 18
- Community 19
- Queued PDF & Email Jobs
- Community 21
- Community 22
- Community 23
- Community 24
- Community 25
- Community 26
- Community 27
- Community 28
- Community 29
- Daily Reports & Deficit Engine
- Community 31
- Community 32
- Community 33
- Community 34
- Delay Events & DTO Factories
- Community 36
- Community 37
- Community 38
- Community 39
- Phase 8 Task Graph (TASKS.md)
- Community 41
- Admin Resource Pages
- Community 43
- Community 44
- Community 45
- Community 46
- Community 47
- Community 48
- Community 49
- Community 50
- Live Camera Capture Component
- Community 52
- Community 53
- Community 54
- Community 55
- Community 56
- Community 57
- Community 58
- Community 59
- Community 60
- Community 61
- Community 62
- Community 63
- Community 64
- Community 65
- Community 66
- Scheduled Artisan Commands
- Community 68
- Community 69
- Community 70
- Community 71
- Community 72
- Community 73
- Community 74
- Community 75
- Community 76
- Community 77
- Community 78
- Community 79
- Community 80
- Community 81
- Community 82
- Community 83
- Community 84
- Community 85
- Community 86
- Community 87
- Community 88
- Community 89
- Community 90
- Community 91
- Community 92
- Community 93
- Community 94
- Community 95
- Community 96
- Community 97
- Community 98
- Community 99
- Community 100
- Community 101
- Community 102
- Community 103
- Community 104
- Community 105
- Community 106
- Community 107
- Sub-Job Delay Event Resource
- Community 109
- Community 110
- Community 111
- Community 112
- Community 113
- Community 114
- Community 115
- Community 116
- Community 117
- Community 118
- Community 119
- Community 120
- Community 121
- Community 122
- Community 123
- Community 124
- Community 125
- Community 126
- Community 127
- Community 128
- Community 152
- Community 153
- Community 154
- Community 155
- Community 156
- Community 157
- Community 158
- Community 159
- Community 160
- Community 161
- Community 162
- Community 168
- Community 169
- Community 170
- Community 171
- Community 172
- Community 173
- Community 174
- Community 176
- Community 185
- Community 186
- Community 281
- Community 282
- Community 283
- Community 284
- Community 285
- Community 286
- Community 287

## God Nodes (most connected - your core abstractions)
1. `DailyReport` - 94 edges
2. `User` - 91 edges
3. `_update()` - 90 edges
4. `x()` - 87 edges
5. `_update()` - 85 edges
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
- `attachPhotoPair()` --references--> `DailyReport`  [EXTRACTED]
  tests/Feature/DailyReportResourceActionsTest.php → app/Models/DailyReport.php
- `Graphify sync-state query memory` --references--> `Development Tasks (TASKS.md)`  [INFERRED]
  graphify-out/memory/query_20260906_111929_has_graphify_been_synced_with_latest_git_commits.md → TASKS.md
- `publishedReportFixture()` --calls--> `User`  [EXTRACTED]
  tests/Feature/ClientReportEmailTest.php → app/Models/User.php

## Import Cycles
- None detected.

## Hyperedges (group relationships)
- **Deficit carry-forward targeting loop** — tasks_deficitcarryforwardservice, tasks_recompute_daily_targets, tasks_target_delay_warnings, tasks_targetdelaywarningnotification, tasks_daily_reports_shift [EXTRACTED 1.00]
- **Delay cascade and mitigation workflow** — tasks_delaycascadeservice, tasks_sub_job_delays_detect, tasks_sub_job_delay_events, tasks_sub_job_delay_event_state_machine, tasks_subjobdelayeventresource [EXTRACTED 1.00]
- **Camera-only capture chain** — tasks_livecapture, tasks_live_capture_rule, tasks_photo_capture_service, tasks_worker_attendance_resource, tasks_daily_report_photos_pair [EXTRACTED 1.00]
- **Delay detection → cascade → mitigation workflow** — docs_prd_v2_sub_job_delay_events, docs_prd_v2_delay_state_machine, docs_prd_v2_delay_cascade, docs_prd_v2_milestone_sub_jobs [EXTRACTED 0.90]
- **Bi-weekly payroll pipeline (attendance → pay)** — docs_prd_v2_worker_attendance, docs_prd_v2_payroll_runs, docs_prd_v2_payroll_items, docs_prd_v2_biweekly_payroll [EXTRACTED 0.95]
- **Shift-based reporting with target/deficit engine** — docs_prd_v2_daily_reports, docs_prd_v2_milestone_sub_jobs, docs_prd_v2_deficit_carry_forward, docs_prd_v2_daily_report_photos, docs_prd_v2_camera_only_capture [INFERRED 0.85]

## Communities (289 total, 39 thin omitted)

### Community 0 - "Filament Chart.js Bundle"
Cohesion: 0.01
Nodes (148): acquireContext(), addControllers(), addPlugins(), addScales(), alpha(), an(), applyStack(), Au() (+140 more)

### Community 1 - "Filament Rich Editor Core"
Cohesion: 0.02
Nodes (122): A(), activateAttributeIfSupported(), appendStringToTextAtIndex(), applyBlockAttribute(), attachmentDidChangeUploadProgress(), attachmentIsManaged(), attributeChangedCallback(), Ca() (+114 more)

### Community 2 - "Filament Markdown Editor"
Cohesion: 0.04
Nodes (142): Aa(), af(), ai(), al(), An(), ao(), bf(), bo() (+134 more)

### Community 3 - "Stats Overview Widget Charts"
Cohesion: 0.02
Nodes (105): aa(), active(), afterDraw(), an(), _animateOptions(), average(), beforeDatasetDraw(), beforeDatasetsDraw() (+97 more)

### Community 4 - "Chart.js Scale Layer"
Cohesion: 0.02
Nodes (134): _a(), aa(), abutsStart(), after(), afterAutoSkip(), Ag(), Ah(), Ai() (+126 more)

### Community 5 - "Chart.js Bar Layout Engine"
Cohesion: 0.04
Nodes (124): ad(), adjustHitBoxes(), ae(), af(), afterDraw(), C(), _calculateBarValuePixels(), calculateLabelRotation() (+116 more)

### Community 6 - "Projects, Milestones & Sub-Jobs"
Cohesion: 0.04
Nodes (29): MilestoneSubJob, ProjectMilestone, WeightIncompleteNotification, DailyReportPolicy, MilestoneSubJobPolicy, ProjectMilestonePolicy, ProjectPolicy, SitePolicy (+21 more)

### Community 7 - "Rich Editor Input Handling"
Cohesion: 0.03
Nodes (99): attachFiles(), backspace(), beforeinput(), canApplyToDocument(), compositionend(), compositionstart(), compositionupdate(), constructor() (+91 more)

### Community 8 - "Chart.js Scale Internals"
Cohesion: 0.05
Nodes (99): Bt(), getScaleForId(), r(), inRange(), Je(), Ki(), linkScales(), Oc() (+91 more)

### Community 9 - "Markdown Editor Utilities"
Cohesion: 0.07
Nodes (70): $c(), X(), me(), D(), E(), g(), H(), _i() (+62 more)

### Community 10 - "Markdown Editor Helpers"
Cohesion: 0.06
Nodes (95): be(), _a(), Ac(), ad(), Ae(), ar(), as(), Ba() (+87 more)

### Community 11 - "Chart.js Platform Layer"
Cohesion: 0.03
Nodes (88): Ac(), ar(), Bl(), cf(), clone(), constructor(), create(), Dl() (+80 more)

### Community 12 - "Payroll Runs & Cycles"
Cohesion: 0.05
Nodes (20): GeneratePayrollRuns, PayrollRun, static, Carbon, User, Worker, PayrollRunPolicy, RolePolicy (+12 more)

### Community 13 - "Rich Editor Attributes API"
Cohesion: 0.05
Nodes (78): addAttribute(), addAttributeAtRange(), addAttributesAtRange(), addHTMLAttribute(), appendText(), applyBlockAttributeAtRange(), breakFormattedBlock(), consolidate() (+70 more)

### Community 14 - "Community 14"
Cohesion: 0.04
Nodes (78): addBox(), addEventListener(), as(), At(), ba(), Bd(), beforeUpdate(), bindEvents() (+70 more)

### Community 15 - "Shared Enums, DTOs & Attendance"
Cohesion: 0.07
Nodes (18): DailyReportRevision, DailyReportWorker, PayrollItem, TargetDelayWarning, WorkerAttendance, AttendanceService, DomainException, Illuminate\Database\Eloquent\Collection (+10 more)

### Community 16 - "Community 16"
Cohesion: 0.07
Nodes (65): _a(), aa(), ai(), ba(), Be(), br(), T(), Ca() (+57 more)

### Community 17 - "Community 17"
Cohesion: 0.05
Nodes (24): EditDailyReport, DailyReportStatus, LogOptions, Project, Site, SiteFactory, SiteSeeder, DateTimeZone (+16 more)

### Community 18 - "Community 18"
Cohesion: 0.05
Nodes (69): active(), afterBuildTicks(), afterCalculateLabelRotation(), afterDataLimits(), afterFit(), afterSetDimensions(), afterTickToLabelConversion(), afterUpdate() (+61 more)

### Community 19 - "Community 19"
Cohesion: 0.12
Nodes (69): at(), B(), he(), br(), Bt(), ca(), cd(), Cr() (+61 more)

### Community 20 - "Queued PDF & Email Jobs"
Cohesion: 0.06
Nodes (23): SendClientReportEmailJob, DailyReportPublished, static, PdfReadyNotification, ReportApprovedNotification, ReportSubmittedNotification, RevisionRequestedNotification, TargetDelayWarningNotification (+15 more)

### Community 21 - "Community 21"
Cohesion: 0.06
Nodes (64): attachmentManagerDidRequestRemovalOfAttachment(), canSetCurrentAttribute(), canSetCurrentTextAttribute(), compositionControllerDidRequestRemovalOfAttachment(), decreaseListLevel(), didClickAttachment(), didFocus(), dragstart() (+56 more)

### Community 22 - "Community 22"
Cohesion: 0.08
Nodes (17): ReportDataDTO, GeneratedDocumentDownloadController, GeneratePdfJob, GeneratedDocument, PdfDocumentService, DocumentType, GeneratedDocument, PdfReportService (+9 more)

### Community 23 - "Community 23"
Cohesion: 0.05
Nodes (14): Bi(), bn(), Id(), ji(), kd(), on(), qd(), qi() (+6 more)

### Community 24 - "Community 24"
Cohesion: 0.11
Nodes (52): Ae(), ar(), at(), Bi(), I(), c(), H(), d() (+44 more)

### Community 25 - "Community 25"
Cohesion: 0.13
Nodes (54): Cn(), b(), Bt(), Ct(), dn(), Dt(), Ft(), G() (+46 more)

### Community 26 - "Community 26"
Cohesion: 0.06
Nodes (52): addElements(), aspectRatio(), buildOrUpdateControllers(), buildOrUpdateElements(), Ca(), Ce(), co(), _dataCheck() (+44 more)

### Community 27 - "Community 27"
Cohesion: 0.06
Nodes (50): afterDatasetsUpdate(), _calculateBarIndexPixels(), _computeAngle(), countVisibleElements(), _d(), generateLabels(), getDatasetMeta(), getDataVisibility() (+42 more)

### Community 28 - "Community 28"
Cohesion: 0.07
Nodes (49): Ao(), applyStack(), ar(), as(), aspectRatio(), _calculateBarIndexPixels(), _calculateBarValuePixels(), calculateCircumference() (+41 more)

### Community 29 - "Community 29"
Cohesion: 0.07
Nodes (47): _a(), add(), ba(), buildOrUpdateScales(), C(), Co(), _computeLabelSizes(), diff() (+39 more)

### Community 30 - "Daily Reports & Deficit Engine"
Cohesion: 0.08
Nodes (10): DailyReport, DailyReportStatus, User, DeficitCarryForwardService, MilestoneSubJob, Carbon\CarbonInterface, DailyReportSeeder, Illuminate\Database\Eloquent\Relations\HasMany (+2 more)

### Community 31 - "Community 31"
Cohesion: 0.07
Nodes (45): afterBuildTicks(), afterCalculateLabelRotation(), afterDataLimits(), afterFit(), afterSetDimensions(), afterTickToLabelConversion(), afterUpdate(), beforeBuildTicks() (+37 more)

### Community 32 - "Community 32"
Cohesion: 0.06
Nodes (13): Client, ClientPolicy, ClientFactory, DailyReportFactory, MilestoneSubJobFactory, ProjectFactory, static, UserFactory (+5 more)

### Community 33 - "Community 33"
Cohesion: 0.08
Nodes (43): add(), applyKeyboardCommand(), attachmentDidChangeAttributes(), attachmentEditorDidRequestRemovalOfAttachment(), canBeGrouped(), checkValidity(), createCaptionElement(), createContentNodes() (+35 more)

### Community 34 - "Community 34"
Cohesion: 0.07
Nodes (42): cacheViewForObject(), canSyncDocumentView(), compositionDidLoadSnapshot(), copyUsingObjectMap(), copyUsingObjectsFromDocument(), createAttachmentNodes(), createChildView(), createContainerElement() (+34 more)

### Community 35 - "Delay Events & DTO Factories"
Cohesion: 0.09
Nodes (13): static, User, SubJobDelayEvent, SubJobDelayEventPolicy, DelayCascadeService, DelayEventStatus, DocumentType, Illuminate\Support\Collection (+5 more)

### Community 36 - "Community 36"
Cohesion: 0.12
Nodes (20): ClientResource, App\Filament\Resources\ClientResource\Pages, App\Filament\Resources\GeneratedDocumentResource\Pages, App\Filament\Resources\PayrollRunResource\Pages, PayrollItemsRelationManager, App\Filament\Resources\ProjectResource\Pages, ProjectMilestonesRelationManager, App\Filament\Resources\SiteResource\Pages (+12 more)

### Community 37 - "Community 37"
Cohesion: 0.07
Nodes (35): add(), Bi(), _cachedScopes(), chartOptionScopes(), datasetAnimationScopeKeys(), datasetElementScopeKeys(), datasetScopeKeys(), describe() (+27 more)

### Community 38 - "Community 38"
Cohesion: 0.07
Nodes (35): acquireContext(), al(), _cachedScopes(), configure(), createResolver(), datasetAnimationScopeKeys(), datasetElementScopeKeys(), datasetScopeKeys() (+27 more)

### Community 39 - "Community 39"
Cohesion: 0.07
Nodes (33): box(), canBeConsolidatedWith(), canBeGroupedWith(), canDecreaseBlockAttributeLevel(), charAt(), compositionControllerDidRender(), findLineBreakInDirectionFromPosition(), fromCommonAttributesOfObjects() (+25 more)

### Community 40 - "Phase 8 Task Graph (TASKS.md)"
Cohesion: 0.07
Nodes (32): Graphify sync-state query memory, 8.7 Final test suite and regression pass, AttendanceService, Camera-only capture rule, Client report email delivery defaults, CrossPhaseIntegrationTest, Before/After photo pair (daily_report_photos), Shift-based daily_reports (3-column key) (+24 more)

### Community 41 - "Community 41"
Cohesion: 0.10
Nodes (7): Ne(), nt(), Qe(), r(), Se, v(), xe()

### Community 42 - "Admin Resource Pages"
Cohesion: 0.09
Nodes (12): ListClients, ListDailyReports, ListGeneratedDocuments, ListPayrollRuns, ListProjects, ListSites, ListSubJobDelayEvents, ListUsers (+4 more)

### Community 43 - "Community 43"
Cohesion: 0.09
Nodes (14): a(), ar(), b(), cr(), H(), ji(), L(), Me() (+6 more)

### Community 44 - "Community 44"
Cohesion: 0.09
Nodes (28): Bi-Weekly Payroll Cycle (14-day), Camera-Only Capture Rule (SE + HRD), Client Filament Portal Removal, daily_report_photos (before/after pair), daily_report_revisions (snapshot history), Daily Report State Machine (draft→need_approval→published + revision_requested), daily_report_workers (allocation, NOT payroll source), daily_reports table (shift-based) (+20 more)

### Community 45 - "Community 45"
Cohesion: 0.10
Nodes (28): afterAutoSkip(), buildLookupTable(), buildTicks(), _calculatePadding(), computeTickLimit(), _drawArgs(), Fi(), getAllParsedValues() (+20 more)

### Community 46 - "Community 46"
Cohesion: 0.14
Nodes (11): App\Filament\Resources\DailyReportResource\Pages, DailyReportPhotoService, PhotoCaptureService, Filament\Forms\Components\FileUpload, Illuminate\Contracts\Filesystem\Filesystem, Illuminate\Contracts\View\View, Illuminate\Http\UploadedFile, Intervention\Image\ImageManager (+3 more)

### Community 47 - "Community 47"
Cohesion: 0.08
Nodes (9): constructor(), define(), getExtension(), _getTestState(), getType(), registerListeners(), yt(), jn() (+1 more)

### Community 48 - "Community 48"
Cohesion: 0.09
Nodes (27): attachmentForFile(), attributesForFile(), compositionShouldAcceptFile(), didChangeAttributes(), getContentType(), getCurrentTextAttributes(), getHeight(), getHref() (+19 more)

### Community 49 - "Community 49"
Cohesion: 0.15
Nodes (27): da(), En(), Mt(), fa(), Fi(), fn(), S(), Ii() (+19 more)

### Community 50 - "Community 50"
Cohesion: 0.08
Nodes (3): duration(), persistent(), seconds()

### Community 51 - "Live Camera Capture Component"
Cohesion: 0.11
Nodes (8): LiveCapture, static, CreateDailyReport, CreateWorkerAttendance, EditWorkerAttendance, Filament\Forms\Components\Field, Illuminate\Support\Arr, Illuminate\Validation\ValidationException

### Community 52 - "Community 52"
Cohesion: 0.11
Nodes (10): BezhanSalleh\FilamentShield\Support\Utils, DatabaseSeeder, ProjectSeeder, RolePermissionSeeder, ShieldSeeder, User, UserSeeder, WorkerSeeder (+2 more)

### Community 53 - "Community 53"
Cohesion: 0.08
Nodes (25): canAcceptDataTransfer(), compositionControllerDidFocus(), compositionDidRequestChangingSelectionToLocationRange(), createDOMRangeFromLocationRange(), createDOMRangeFromPoint(), createLocationRangeFromDOMRange(), didMouseDown(), domRangeWithinElement() (+17 more)

### Community 54 - "Community 54"
Cohesion: 0.11
Nodes (25): average(), cd(), clear(), cn(), Da(), getCenterPoint(), getDistanceFromCenterForValue(), _getLegendItemAt() (+17 more)

### Community 55 - "Community 55"
Cohesion: 0.11
Nodes (25): tl(), ac(), Ai(), ca(), ec(), Fc(), G(), getBasePixel() (+17 more)

### Community 56 - "Community 56"
Cohesion: 0.13
Nodes (24): afterDatasetsUpdate(), buildOrUpdateControllers(), _destroyDatasetMeta(), generateLabels(), getController(), getDatasetMeta(), getDataVisibility(), getMaxBorderWidth() (+16 more)

### Community 57 - "Community 57"
Cohesion: 0.17
Nodes (22): B(), C(), D(), H(), I(), J(), O(), U() (+14 more)

### Community 58 - "Community 58"
Cohesion: 0.11
Nodes (23): alpha(), be(), ea(), en(), fe(), greyscale(), Hi(), hslString() (+15 more)

### Community 59 - "Community 59"
Cohesion: 0.10
Nodes (19): AdminPanelProvider, BezhanSalleh\FilamentShield\FilamentShieldPlugin, Filament\Http\Middleware\Authenticate, Filament\Http\Middleware\AuthenticateSession, Filament\Http\Middleware\DisableBladeIconComponents, Filament\Http\Middleware\DispatchServingFilamentEvent, Filament\Pages, Filament\Panel (+11 more)

### Community 60 - "Community 60"
Cohesion: 0.09
Nodes (21): axios, concurrently, laravel-vite-plugin, allowScripts, esbuild@0.28.2, devDependencies, axios, concurrently (+13 more)

### Community 61 - "Community 61"
Cohesion: 0.12
Nodes (8): CreateClient, CreateProject, CreateSite, SiteResource, CreateUser, CreateWorker, WorkerResource, Filament\Resources\Pages\CreateRecord

### Community 62 - "Community 62"
Cohesion: 0.11
Nodes (21): actionIsExternal(), canInvokeAction(), compositionControllerDidBlur(), compositionControllerDidSyncDocumentView(), compositionDidAddAttachment(), compositionDidChangeAttachmentPreviewURL(), compositionDidChangeCurrentAttributes(), compositionDidChangeDocument() (+13 more)

### Community 63 - "Community 63"
Cohesion: 0.16
Nodes (21): breaksOnReturn(), canDecreaseNestingLevel(), canIncreaseNestingLevel(), canSetCurrentBlockAttribute(), decreaseBlockAttributeLevel(), decreaseNestingLevel(), formatIndent(), formatOutdent() (+13 more)

### Community 64 - "Community 64"
Cohesion: 0.12
Nodes (21): cancel(), _createDescriptors(), _descriptors(), dl(), Do(), _getLegendItemAt(), getPlugin(), _handleEvent() (+13 more)

### Community 65 - "Community 65"
Cohesion: 0.11
Nodes (20): Yn(), Ge(), chartOptionScopes(), constructor(), data(), describe(), fl(), Fs() (+12 more)

### Community 66 - "Community 66"
Cohesion: 0.14
Nodes (19): ArrowLeft(), ArrowRight(), editAttachment(), expandSelectionInDirection(), findNodeAndOffsetFromLocation(), getAttachmentAtRange(), getExpandedRangeInDirection(), getSignificantNodesForIndex() (+11 more)

### Community 67 - "Scheduled Artisan Commands"
Cohesion: 0.15
Nodes (5): DetectSubJobDelays, PruneMissingPhotos, RecomputeDailyTargets, DailyReportPhoto, Illuminate\Console\Command

### Community 68 - "Community 68"
Cohesion: 0.22
Nodes (18): appendAttachmentWithAttributes(), appendBlockForAttributesWithElement(), appendBlockForElement(), appendBlockForTextNode(), appendEmptyBlock(), appendPiece(), appendStringWithAttributes(), findBlockElementAncestors() (+10 more)

### Community 69 - "Community 69"
Cohesion: 0.14
Nodes (18): Bi(), determineDataLimits(), endOf(), _getLabelBounds(), getMinMax(), _getOtherScale(), getUserBounds(), handleTickRangeOptions() (+10 more)

### Community 70 - "Community 70"
Cohesion: 0.18
Nodes (7): LanguageSwitcher, Locale, Carbon, LocaleContext, Illuminate\View\View, Livewire\Component, Locale

### Community 71 - "Community 71"
Cohesion: 0.18
Nodes (7): EditClient, EditProject, EditSite, EditUser, EditWorker, Filament\Actions, Filament\Resources\Pages\EditRecord

### Community 73 - "Community 73"
Cohesion: 0.12
Nodes (5): [g](), style(), update(), [x](), tt()

### Community 74 - "Community 74"
Cohesion: 0.15
Nodes (3): DailyReportResource, GeneratedDocumentResource, Illuminate\Database\Eloquent\Builder

### Community 75 - "Community 75"
Cohesion: 0.13
Nodes (16): scripts, dev, post-root-package-install, post-update-cmd, pre-package-uninstall, setup, Composer\\Config::disableProcessTimeout, composer install (+8 more)

### Community 76 - "Community 76"
Cohesion: 0.13
Nodes (16): addControllers(), addElements(), addPlugins(), addScales(), buildOrUpdateElements(), _dataCheck(), _each(), Ei() (+8 more)

### Community 77 - "Community 77"
Cohesion: 0.12
Nodes (16): br(), cc(), first(), getPadding(), _getUniformDataChanges(), ii(), jn(), pathSegment() (+8 more)

### Community 78 - "Community 78"
Cohesion: 0.15
Nodes (15): findIndexAndOffsetAtPosition(), getObjectAtIndex(), getObjectAtPosition(), getSplittableListInRange(), insertObjectAtIndex(), insertSplittableListAtIndex(), insertSplittableListAtPosition(), removeObjectAtIndex() (+7 more)

### Community 79 - "Community 79"
Cohesion: 0.15
Nodes (15): apply(), q(), B(), kn(), lt(), Me(), mo(), ms() (+7 more)

### Community 80 - "Community 80"
Cohesion: 0.14
Nodes (13): description, extra, laravel, keywords, dont-discover, license, minimum-stability, name (+5 more)

### Community 81 - "Community 81"
Cohesion: 0.15
Nodes (13): require, barryvdh/laravel-dompdf, bezhansalleh/filament-shield, ext-bcmath, fakerphp/faker, filament/filament, intervention/image-laravel, laravel/framework (+5 more)

### Community 82 - "Community 82"
Cohesion: 0.18
Nodes (12): di(), e(), g(), Ht(), i(), Ie(), Re(), t() (+4 more)

### Community 83 - "Community 83"
Cohesion: 0.18
Nodes (5): ViewPayrollRun, PayrollRunResource, Filament\Notifications\Notification, Filament\Pages\Actions\Action, Filament\Resources\Pages\ViewRecord

### Community 85 - "Community 85"
Cohesion: 0.17
Nodes (12): Be(), ei(), ii(), le(), ni(), oi(), r(), ri() (+4 more)

### Community 86 - "Community 86"
Cohesion: 0.20
Nodes (12): c(), o(), _p(), qp(), s(), Sg(), xt(), Ye() (+4 more)

### Community 87 - "Community 87"
Cohesion: 0.26
Nodes (12): cl(), D(), Eo(), Fo(), He(), hl(), jt(), Ma() (+4 more)

### Community 88 - "Community 88"
Cohesion: 0.27
Nodes (7): e(), i(), l(), Ni(), o(), t(), u()

### Community 89 - "Community 89"
Cohesion: 0.18
Nodes (11): Qt(), dt(), Ge(), h(), mt(), Q(), Re(), ur() (+3 more)

### Community 90 - "Community 90"
Cohesion: 0.24
Nodes (6): SetLocale, Illuminate\Foundation\Application, Illuminate\Foundation\Configuration\Exceptions, Illuminate\Foundation\Configuration\Middleware, Illuminate\Http\Request, Symfony\Component\HttpFoundation\Response

### Community 91 - "Community 91"
Cohesion: 0.24
Nodes (3): SubJobsWeightsTotalRule, WeightValidation, Illuminate\Contracts\Validation\ValidationRule

### Community 92 - "Community 92"
Cohesion: 0.20
Nodes (10): require-dev, larastan/larastan, laravel/pail, laravel/pint, laravel/sail, mockery/mockery, nunomaduro/collision, pestphp/pest (+2 more)

### Community 94 - "Community 94"
Cohesion: 0.20
Nodes (10): Ce(), De(), Dt(), Fe(), He(), ir(), Mt(), nr() (+2 more)

### Community 95 - "Community 95"
Cohesion: 0.24
Nodes (10): ba(), e(), Ip(), It(), lt(), sa(), Wp(), Wt() (+2 more)

### Community 96 - "Community 96"
Cohesion: 0.29
Nodes (10): At(), dataset(), Fa(), getMaximumSize(), index(), mn(), point(), rs() (+2 more)

### Community 97 - "Community 97"
Cohesion: 0.28
Nodes (4): MilestoneStartDateRule, ScheduleValidator, Carbon\Carbon, DateTimeInterface

### Community 99 - "Community 99"
Cohesion: 0.29
Nodes (4): AppServiceProvider, Illuminate\Cache\RateLimiting\Limit, Illuminate\Support\Facades\RateLimiter, Illuminate\Support\ServiceProvider

### Community 100 - "Community 100"
Cohesion: 0.39
Nodes (4): LiveCapture, Closure, Illuminate\Contracts\Validation\InvokableRule, Livewire\Features\SupportFileUploads\TemporaryUploadedFile

### Community 101 - "Community 101"
Cohesion: 0.46
Nodes (8): de(), Fe(), Gt(), j(), je(), le(), vt(), Zp()

### Community 102 - "Community 102"
Cohesion: 0.25
Nodes (8): dispatch(), dispatchSelf(), dispatchTo(), emit(), emitSelf(), emitTo(), event(), eventData()

### Community 103 - "Community 103"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 104 - "Community 104"
Cohesion: 0.71
Nodes (6): ensure_env(), log(), provision(), entrypoint.sh script, wait_for_minio(), wait_for_pgsql()

### Community 105 - "Community 105"
Cohesion: 0.29
Nodes (7): bi(), jp(), ol(), Tp(), xl(), xp(), yl()

### Community 106 - "Community 106"
Cohesion: 0.29
Nodes (7): actions(), button(), constructor(), grouped(), link(), name(), view()

### Community 109 - "Community 109"
Cohesion: 0.40
Nodes (3): Illuminate\Foundation\Testing\TestCase, ExampleTest, TestCase

### Community 110 - "Community 110"
Cohesion: 0.40
Nodes (6): ca(), rl(), Rp(), Sp(), vp(), yp()

### Community 116 - "Community 116"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 117 - "Community 117"
Cohesion: 0.40
Nodes (5): autoload-dev, files, psr-4, Tests\\, tests/Support/helpers.php

### Community 118 - "Community 118"
Cohesion: 0.40
Nodes (4): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler, Monolog\Processor\PsrLogMessageProcessor

### Community 119 - "Community 119"
Cohesion: 0.60
Nodes (5): clickPercent(), getPosition(), mouseUp(), moveplayhead(), timelineClicked()

### Community 120 - "Community 120"
Cohesion: 0.40
Nodes (5): danger(), info(), status(), success(), warning()

### Community 121 - "Community 121"
Cohesion: 0.67
Nodes (4): AGENTS.md — Build Rules & Fast-Reference, PRD v2/v3 — Architecture Blueprint & Source of Truth, README.md — Project Overview & Tech Stack, SCAFFOLDING.md — Setup / Install Guide

### Community 122 - "Community 122"
Cohesion: 0.50
Nodes (4): post-autoload-dump, Illuminate\\Foundation\\ComposerScripts::postAutoloadDump, @php artisan filament:upgrade, @php artisan package:discover --ansi

### Community 123 - "Community 123"
Cohesion: 0.50
Nodes (4): post-create-project-cmd, @php artisan key:generate --ansi, @php artisan migrate --graceful --ansi, @php -r \"file_exists('database/database.sqlite') || touch('database/database.sqlite');\

### Community 124 - "Community 124"
Cohesion: 0.50
Nodes (4): Migration: unique index (site_id, report_date, shift), Migration: add shift/target columns to daily_reports, ReportShift enum (shift_1/shift_2/shift_3), Task 8.2: Shift-Based Daily Reports & Target Engine (COMPLETE)

### Community 125 - "Community 125"
Cohesion: 0.50
Nodes (4): DeficitCarryForwardService (nightly target recompute + warning lifecycle), DeficitCarryForwardTest (carry-forward math, notify-once, 3-column dup rejection), RecomputeDailyTargets command (daily-targets:recompute, scheduled 00:30), Gotcha: deficit recompute must run BEFORE shift warning evaluation (routes/console.php ordering)

### Community 126 - "Community 126"
Cohesion: 0.50
Nodes (3): Illuminate\Foundation\Inspiring, Illuminate\Support\Facades\Artisan, Illuminate\Support\Facades\Schedule

### Community 128 - "Community 128"
Cohesion: 0.67
Nodes (3): test, @php artisan config:clear --ansi, @php artisan test

### Community 152 - "Community 152"
Cohesion: 0.67
Nodes (3): TargetDelayWarning model (first_triggered_at/resolved_at, notify-once), TargetDelayWarningNotification (mail + database), Migration: create target_delay_warnings table

## Knowledge Gaps
- **111 isolated node(s):** `Controller`, `App\\`, `Database\\Factories\\`, `Database\\Seeders\\`, `Tests\\` (+106 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **39 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Br()` connect `Community 82` to `Filament Chart.js Bundle`, `Community 33`, `Chart.js Scale Layer`, `Rich Editor Input Handling`, `Community 19`?**
  _High betweenness centrality (0.065) - this node is a cross-community bridge._
- **Why does `constructor()` connect `Rich Editor Input Handling` to `Filament Rich Editor Core`, `Filament Markdown Editor`, `Community 33`, `Community 39`, `Markdown Editor Helpers`, `Rich Editor Attributes API`, `Community 48`, `Community 82`, `Community 21`, `Community 89`, `Community 95`?**
  _High betweenness centrality (0.039) - this node is a cross-community bridge._
- **Why does `Ls()` connect `Community 58` to `Filament Chart.js Bundle`, `Chart.js Scale Internals`, `Stats Overview Widget Charts`?**
  _High betweenness centrality (0.032) - this node is a cross-community bridge._
- **Are the 3 inferred relationships involving `_update()` (e.g. with `g()` and `f()`) actually correct?**
  _`_update()` has 3 INFERRED edges - model-reasoned connections that need verification._
- **Are the 13 inferred relationships involving `x()` (e.g. with `D()` and `g()`) actually correct?**
  _`x()` has 13 INFERRED edges - model-reasoned connections that need verification._
- **Are the 2 inferred relationships involving `_update()` (e.g. with `g()` and `f()`) actually correct?**
  _`_update()` has 2 INFERRED edges - model-reasoned connections that need verification._
- **What connects `Controller`, `App\\`, `Database\\Factories\\` to the rest of the system?**
  _111 weakly-connected nodes found - possible documentation gaps or missing edges._