<x-filament-panels::page>
    <style>
        /* Hide skeleton/loading placeholders */
        .fi-wi-stats-overview-stat-placeholder,
        [wire\:loading] .fi-wi-stats-overview-stat {
            display: none !important;
        }

        /* Reduce gap between header and content */
        .fi-page-header-main-ctn {
            gap: 12px !important;
        }

        .fi-page-content {
            padding-top: 0 !important;
        }
    </style>

    {{-- Header Widgets (Statistics) --}}
    @if ($showStats)
        <div class="mb-6">
            <x-filament-widgets::widgets :widgets="$this->getHeaderWidgets()" :columns="$this->getHeaderWidgetsColumns()"
                :data="$this->getHeaderWidgetsData()" />
        </div>
    @endif

    {{-- Tabs --}}
    @if (count($this->getTabs()))
        <div class="mb-2">
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