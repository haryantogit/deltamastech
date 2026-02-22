<?php

namespace App\Filament\Resources\ProductResource\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;

class ProductStockTableWidget extends BaseWidget
{
    public ?Model $record = null;

    // This widget will be shown on the view page, so we rely on the record being passed to it.
    // However, widgets in getHeaderWidgets might not automatically get $record unless we handle it or the page passes it.
    // In Filament v3, widgets on View/Edit pages usually get the record if they have the property.

    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Rincian Stok';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                // access the record's stocks
                // If record is null (which happens during mounting sometimes), we return empty.
                // But normally on a ViewPage the widget acts on $this->record
                \App\Models\Stock::query()->where('product_id', $this->record?->id ?? 0)
            )
            ->columns([
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Gudang')
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Qty')
                    ->numeric()
                    ->sortable(),
            ])
            ->paginated(false); // Compact view
    }
}
