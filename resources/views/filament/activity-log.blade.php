@php
    /** @var \Illuminate\Support\Collection<int, \Spatie\Activitylog\Models\Activity> $activities */
    /** @var non-empty-string $timezone */
@endphp

<div class="space-y-4">
    @if ($activities->isEmpty())
        <p class="text-sm text-gray-500">No activity has been recorded for this record.</p>
    @else
        <ol class="space-y-3">
            @foreach ($activities as $activity)
                @php
                    $changes = $activity->attribute_changes?->toArray() ?? [];
                    $newStatus = $changes['attributes']['status'] ?? null;
                    $oldStatus = $changes['old']['status'] ?? null;
                @endphp
                <li class="flex items-start justify-between gap-4 border-b border-gray-100 pb-3 last:border-b-0">
                    <div class="min-w-0">
                        <span class="text-sm font-medium text-gray-900">
                            {{ Str::headline($activity->description) }}
                        </span>
                        @if ($oldStatus || $newStatus)
                            <div class="mt-1 text-xs">
                                <span class="text-gray-400">Status:</span>
                                @if ($oldStatus)
                                    <span class="line-through text-gray-400">{{ Str::headline($oldStatus) }}</span>
                                    <span class="text-gray-400">→</span>
                                @endif
                                <span class="font-medium text-gray-900">{{ Str::headline($newStatus ?? $oldStatus) }}</span>
                            </div>
                        @endif
                    </div>
                    <div class="shrink-0 text-right text-xs">
                        <div class="text-gray-900">
                            {{ $activity->causer?->name ?? 'System' }}
                        </div>
                        <div class="mt-0.5 text-gray-400">
                            {{ $activity->created_at?->setTimezone($timezone)->format('Y-m-d H:i:s') }}
                        </div>
                    </div>
                </li>
            @endforeach
        </ol>
    @endif
</div>
