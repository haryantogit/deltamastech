<x-filament-widgets::widget>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; align-items: stretch;">
        {{-- Warehouse Info Card --}}
        <x-filament::section style="position: relative; overflow: hidden; height: 100%; display: flex; flex-direction: column; justify-content: center;">
            {{-- Decorative Icon --}}
            <div style="position: absolute; right: -2rem; top: -2rem; opacity: 0.1; pointer-events: none;">
                 <x-filament::icon icon="heroicon-o-building-office-2" style="width: 12rem; height: 12rem;" />
            </div>

            <div style="position: relative; z-index: 10;">
                <div style="margin-bottom: 2rem;">
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                        <div style="padding: 0.75rem; background: #fff7ed; border-radius: 0.75rem;">
                            <x-filament::icon icon="heroicon-s-building-storefront" style="width: 2rem; height: 2rem; color: #ea580c;" />
                        </div>
                        <div>
                            <h4 style="font-size: 0.7rem; font-weight: 700; color: #ea580c; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.25rem;">DATA GUDANG</h4>
                            <h1 style="font-size: 2.25rem; font-weight: 900; color: #111827; line-height: 1;">
                                {{ $record->name }}
                            </h1>
                        </div>
                    </div>
                    
                    <div style="display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; background: #f3f4f6; border-radius: 9999px; font-size: 0.75rem; font-weight: 700; color: #6b7280; font-family: monospace; border: 1px solid #e5e7eb;">
                        KODE: {{ $record->code ?? 'UA' }}
                    </div>
                </div>

                {{-- Image Preview (Large Square) --}}
                <div style="width: 250px; height: 250px; border: 2px dashed #e5e7eb; border-radius: 1.5rem; overflow: hidden; background: #f9fafb; position: relative;">
                    @if($record->image)
                        <img src="{{ asset('storage/' . $record->image) }}" style="width: 100%; height: 100%; object-fit: cover;">
                    @else
                        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; text-align: center; padding: 1rem;">
                            <x-filament::icon icon="heroicon-o-photo" style="width: 3rem; height: 3rem; color: #9ca3af; margin-bottom: 0.5rem;" />
                            <h3 style="font-size: 0.875rem; font-weight: 700; color: #111827;">Foto Gudang</h3>
                        </div>
                    @endif
                </div>
            </div>
        </x-filament::section>

        {{-- Metrics side --}}
        <div style="height: 100%;">
            @livewire(\App\Filament\Resources\Warehouses\Widgets\WarehouseMetricsWidget::class, ['record' => $record])
        </div>
    </div>
</x-filament-widgets::widget>