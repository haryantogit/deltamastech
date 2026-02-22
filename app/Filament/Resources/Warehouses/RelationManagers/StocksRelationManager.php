<?php

namespace App\Filament\Resources\Warehouses\RelationManagers;

use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StocksRelationManager extends RelationManager
{
    protected static string $relationship = 'stocks';

    protected static ?string $title = 'Rincian Stok';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('product_name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product.name')
            ->columns([
                TextColumn::make('product.name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product.sku')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->color('primary')
                    ->url(fn($record) => "/admin/products/{$record->product_id}"),
                TextColumn::make('quantity')
                    ->label('Qty')
                    ->numeric()
                    ->sortable()
                    ->alignment('right')
                    ->summarize(\Filament\Tables\Columns\Summarizers\Sum::make()->label('Total')),
                TextColumn::make('product.unit.name')
                    ->label('Satuan')
                    ->color('primary')
                    ->badge(),
                TextColumn::make('value')
                    ->label('Nilai')
                    ->getStateUsing(fn($record) => $record->quantity * $record->product->buy_price)
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->alignment('right')
                    ->summarize(
                        \Filament\Tables\Columns\Summarizers\Summarizer::make()
                            ->label('Total')
                            ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                            ->using(fn($query) => $query->join('products', 'stocks.product_id', '=', 'products.id')
                                ->sum(\Illuminate\Support\Facades\DB::raw('stocks.quantity * products.buy_price')))
                    ),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Actions like "Stock Adjustment" usually handled at page level or specific action
            ])
            ->actions([
                // Maybe view product?
            ])
            ->bulkActions([
                //
            ]);
    }
}
