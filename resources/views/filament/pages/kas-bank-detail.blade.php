<x-filament-panels::page>
    <style>
        /* Remove top spacing */
        .fi-page-content {
            padding-top: 0 !important;
        }

        /* Hide skeleton/loading placeholders */
        .fi-wi-stats-overview-stat-placeholder,
        [wire\:loading] .fi-wi-stats-overview-stat {
            display: none !important;
        }
    </style>

    {{-- Header Widgets (Statistics) --}}
    <x-filament-widgets::widgets :widgets="$this->getHeaderWidgets()" :columns="$this->getHeaderWidgetsColumns()"
        :data="$this->getHeaderWidgetsData()" />

    {{-- Tabs --}}
    @if (count($this->getTabs()))
        <div class="mb-4">
            <x-filament::tabs>
                @foreach ($this->getTabs() as $key => $tab)
                    <x-filament::tabs.item :active="$this->activeTab === $key" :badge="$tab->getBadge()"
                        wire:click="$set('activeTab', '{{ $key }}')">
                        {{ $tab->getLabel() }}
                    </x-filament::tabs.item>
                @endforeach
            </x-filament::tabs>
        </div>
    @endif

    {{-- Table --}}
    {{ $this->table }}
</x-filament-panels::page>