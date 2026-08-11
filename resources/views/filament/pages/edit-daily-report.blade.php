<x-filament-panels::page
    @class([
        'fi-resource-edit-record-page',
        'fi-resource-' . str_replace('/', '-', $this->getResource()::getSlug()),
        'fi-resource-record-' . $record->getKey(),
    ])
>
    <div
        x-data="dailyReportDraftStore({
            initialSavedAt: @js($this->draftLastSavedAt),
            initialFailed: @js($this->draftSaveFailed),
        })"
        x-cloak
        x-show="indicatorVisible"
        @draft-auto-saved.window="setSaved($event.detail.savedAt)"
        @draft-auto-save-failed.window="setRetrying()"
        class="mb-4"
    >
        <template x-if="status === 'retrying'">
            <span class="inline-flex items-center gap-2 rounded-lg bg-danger-50 px-3 py-1.5 text-sm text-danger-700">
                <span class="animate-pulse">●</span>
                Unsaved changes — retrying
            </span>
        </template>
        <template x-if="status === 'saved'">
            <span class="inline-flex items-center rounded-lg bg-success-50 px-3 py-1.5 text-sm text-success-700" x-text="savedLabel"></span>
        </template>
    </div>

    <x-filament-panels::form
        id="form"
        :wire:key="$this->getId() . '.forms.' . $this->getFormStatePath()"
        wire:submit="save"
        :wire:poll="$this->shouldAutoSave() ? '10s saveDraft' : null"
    >
        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="$this->getCachedFormActions()"
            :full-width="$this->hasFullWidthFormActions()"
        />
    </x-filament-panels::form>

    <x-filament-panels::page.unsaved-data-changes-alert />

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('dailyReportDraftStore', (options) => ({
                    savedAt: options.initialSavedAt ?? null,
                    status: options.initialFailed ? 'retrying' : (options.initialSavedAt ? 'saved' : 'idle'),
                    indicatorVisible: Boolean(options.initialSavedAt || options.initialFailed),
                    formSnapshot: {},

                    get savedLabel() {
                        return this.savedAt ? ('Draft Saved at ' + this.savedAt) : '';
                    },

                    captureState() {
                        this.formSnapshot = { at: new Date().toISOString() };

                        return true;
                    },

                    setSaved(savedAt) {
                        this.savedAt = savedAt;
                        this.status = 'saved';
                        this.indicatorVisible = true;
                    },

                    setRetrying() {
                        this.status = 'retrying';
                        this.indicatorVisible = true;
                    },
                }));
            });
        </script>
    @endpush
</x-filament-panels::page>
