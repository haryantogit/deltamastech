<div class="space-y-6">
    @php
        // Fetch activities here or pass them from the Action
        // If passed from Action, it's likely in $record->activities or similar if eager loaded
        // But for a custom view in a modal, we might need to access the record.
        // The record is passed to the view variables. 
        // Let's assume $record is passed.
        $activities = \Spatie\Activitylog\Models\Activity::where('subject_type', $record->getMorphClass())
            ->where('subject_id', $record->id)
            ->latest()
            ->get();
    @endphp

    @forelse ($activities as $activity)
        <div class="relative pl-8 sm:pl-12">
            <!-- Timeline line -->
            <div class="absolute left-2 top-0 bottom-0 w-px bg-gray-200 dark:bg-gray-700"></div>

            <!-- Icon -->
            <div class="absolute left-0 top-1">
                <div
                    class="h-5 w-5 rounded-full border-2 border-white dark:border-gray-900 bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                    {{-- Simple dot for now, or use heroicons if needed --}}
                    @if($activity->description == 'created')
                        <div class="h-2 w-2 rounded-full bg-green-500"></div>
                    @elseif($activity->description == 'updated')
                        <div class="h-2 w-2 rounded-full bg-blue-500"></div>
                    @else
                        <div class="h-2 w-2 rounded-full bg-gray-500"></div>
                    @endif
                </div>
            </div>

            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start">
                <!-- Content -->
                <div>
                    <div class="text-xs text-gray-500 mb-1">
                        {{ $activity->created_at->diffForHumans() }}
                    </div>
                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ ucfirst($activity->description) }} oleh <span
                            class="font-bold">{{ $activity->causer->name ?? 'System' }}</span>
                    </div>

                    {{-- Details --}}
                    <div class="mt-1 text-xs text-gray-500">
                        <div class="flex flex-col gap-1">
                            @if($activity->properties['attributes'] ?? false)
                                @foreach($activity->properties['attributes'] as $key => $value)
                                    @if($key !== 'updated_at')
                                        <div>
                                            <span class="font-semibold">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                            {{ is_array($value) ? json_encode($value) : $value }}
                                        </div>
                                    @endif
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <!-- Metadata -->
            <div class="mt-2 text-xs text-gray-400 flex flex-col gap-0.5">
                @if($activity->properties['ip'] ?? false)
                    <div>IP: {{ $activity->properties['ip'] }}</div>
                @endif
                <div>{{ $activity->created_at->format('d/m/Y H:i') }}</div>
            </div>
        </div>
    @empty
        <div class="text-center text-gray-500 py-4">Tidak ada log aktivitas.</div>
    @endforelse
</div>