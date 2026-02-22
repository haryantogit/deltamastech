<div class="grid grid-cols-3 gap-6 w-full" style="max-width: 100%;">
    <div class="col-span-2">
        @livewire(\App\Filament\Resources\ProductResource\Widgets\ProductTrendChart::class, ['record' => $record])
    </div>
    <div class="col-span-1">
        @livewire(\App\Filament\Resources\ProductResource\Widgets\ProductWarehouseSplitWidget::class, ['record' => $record])
    </div>
</div>