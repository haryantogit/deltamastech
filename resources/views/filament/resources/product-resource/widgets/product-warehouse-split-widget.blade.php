<x-filament-widgets::widget>
    <div class="flex flex-col">
        @livewire(\App\Filament\Resources\ProductResource\Widgets\ProductWarehouseChart::class, ['record' => $record])
        <div style="margin-top: 2rem;">
            @livewire(\App\Filament\Resources\ProductResource\Widgets\ProductStockTableWidget::class, ['record' => $record])
        </div>
    </div>
</x-filament-widgets::widget>