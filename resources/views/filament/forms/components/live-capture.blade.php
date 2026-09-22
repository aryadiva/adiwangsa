<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="filamentLiveCapture({ statePath: @js($getStatePath()) })"
        x-on:livewire:navigated.window="$el._x_dataStack?.[0]?.stopStream?.()"
        {{ $attributes->merge(['class' => 'fi-fo-live-capture']) }}
    >
        <div x-show="!captured" class="space-y-2">
            <video
                x-ref="video"
                x-show="streaming"
                autoplay
                playsinline
                muted
                class="w-full max-w-md rounded-lg border border-gray-200 bg-black dark:border-gray-700"
            ></video>

            <div x-show="!streaming" class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    x-show="hasCamera"
                    @click="start()"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-gray-950 px-3 py-1.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800 dark:bg-white dark:text-gray-950 dark:hover:bg-gray-200"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path d="M1 8.25a1.75 1.75 0 0 1 1.75-1.75h3.26a.25.25 0 0 0 .22-.13L7.27 4.87A1.75 1.75 0 0 1 8.77 4h2.46c.63 0 1.21.34 1.52.87l1.04 1.5c.05.08.13.13.22.13h3.24A1.75 1.75 0 0 1 19 8.25v6.5A1.75 1.75 0 0 1 17.25 16.5H2.75A1.75 1.75 0 0 1 1 14.75v-6.5Z" /><path d="M10 13a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" clip-rule="evenodd" fill-rule="evenodd" /></svg>
                    {{ __('app.component.open_camera') }}
                </button>

                <input
                    x-show="!hasCamera"
                    x-ref="fallback"
                    type="file"
                    accept="image/jpeg,image/png"
                    capture="environment"
                    class="block w-full cursor-pointer rounded-lg border border-gray-300 text-sm text-gray-500 file:mr-3 file:cursor-pointer file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-medium hover:file:bg-gray-200 dark:border-gray-700 dark:bg-gray-900"
                    @change="fromInput($event)"
                />
            </div>

            <button
                type="button"
                x-show="streaming"
                x-cloak
                @click="shoot()"
                class="inline-flex items-center rounded-lg bg-danger-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm transition hover:bg-danger-500"
            >
                {{ __('app.component.capture_photo') }}
            </button>

            <p x-show="error" x-text="error" x-cloak class="text-sm font-medium text-danger-600 dark:text-danger-400"></p>
        </div>

        <div x-show="captured" x-cloak class="space-y-2">
            <img :src="preview" alt="" class="w-full max-w-md rounded-lg border border-gray-200 dark:border-gray-700" />
            <button
                type="button"
                @click="retake()"
                class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
            >
                {{ __('app.component.recapture') }}
            </button>
        </div>

        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            {{ __('app.component.gallery_note') }}
        </p>
    </div>
</x-dynamic-component>
