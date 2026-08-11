<x-filament-panels::page>
    <x-filament::section heading="My Projects">
        @if ($projects->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">No projects assigned to your account yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-gray-200 dark:border-white/10">
                        <tr>
                            <th class="py-2 pr-4 font-semibold">Code</th>
                            <th class="py-2 pr-4 font-semibold">Name</th>
                            <th class="py-2 pr-4 font-semibold">Status</th>
                            <th class="py-2 pr-4 font-semibold">Sites</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($projects as $project)
                            <tr class="border-b border-gray-100 dark:border-white/5">
                                <td class="py-2 pr-4">{{ $project->code }}</td>
                                <td class="py-2 pr-4">{{ $project->name }}</td>
                                <td class="py-2 pr-4">
                                    <span class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset
                                        {{ match ($project->status->value) {
                                            'planning' => 'bg-gray-50 text-gray-700 ring-gray-200',
                                            'active' => 'bg-sky-50 text-sky-700 ring-sky-200',
                                            'on_hold' => 'bg-amber-50 text-amber-700 ring-amber-200',
                                            'completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                        } }}">
                                        {{ str($project->status->value)->headline()->toString() }}
                                    </span>
                                </td>
                                <td class="py-2 pr-4">{{ $project->sites_count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>

    <x-filament::section heading="Published Daily Reports">
        @if ($reports->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">No published reports yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-gray-200 dark:border-white/10">
                        <tr>
                            <th class="py-2 pr-4 font-semibold">Date</th>
                            <th class="py-2 pr-4 font-semibold">Site</th>
                            <th class="py-2 pr-4 font-semibold">Weather</th>
                            <th class="py-2 pr-4 font-semibold">Summary</th>
                            <th class="py-2 pr-4 font-semibold">PDF</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reports as $item)
                            <tr class="border-b border-gray-100 dark:border-white/5">
                                <td class="py-2 pr-4">{{ $item['report']->report_date->toDateString() }}</td>
                                <td class="py-2 pr-4">{{ $item['report']->site->name }}</td>
                                <td class="py-2 pr-4">{{ str($item['report']->weather_condition->value)->headline()->toString() }}</td>
                                <td class="py-2 pr-4">{{ \Illuminate\Support\Str::limit($item['report']->work_summary, 80) }}</td>
                                <td class="py-2 pr-4">
                                    @if ($item['pdf_url'])
                                        <a
                                            href="{{ $item['pdf_url'] }}"
                                            class="inline-flex items-center rounded-md bg-primary-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-primary-500"
                                        >Download</a>
                                    @else
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
