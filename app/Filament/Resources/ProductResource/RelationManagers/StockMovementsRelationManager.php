<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class StockMovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'stockMovements';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-arrow-path-rounded-square';

    protected static ?string $title = 'Pergerakan Stok';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe Transaksi')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'in' => 'Masuk',
                        'out' => 'Keluar',
                        'sale' => 'Penjualan',
                        'purchase' => 'Pembelian',
                        'adjustment' => 'Penyesuaian',
                        'adjustment_plus' => 'Penyesuaian (+)',
                        'adjustment_minus' => 'Penyesuaian (-)',
                        'transfer' => 'Transfer',
                        default => ucfirst($state),
                    })
                    ->colors([
                        'success' => ['in', 'purchase', 'adjustment_plus'],
                        'danger' => ['out', 'sale', 'adjustment_minus'],
                        'info' => ['transfer', 'adjustment'],
                    ]),
                Tables\Columns\TextColumn::make('reference_type')
                    ->label('Referensi')
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->reference_type && str_contains($record->reference_type, 'PurchaseInvoiceItem')) {
                            return 'Purchase Invoice';
                        }
                        return $record->reference_type ? class_basename($record->reference_type) . ' #' . $record->reference_id : '-';
                    })
                    ->description(function ($record) {
                        if (!$record->reference)
                            return null;
                        if ($record->reference_type && str_contains($record->reference_type, 'PurchaseInvoiceItem')) {
                            return $record->reference->invoice->number ?? null;
                        }
                        return $record->reference->number ?? $record->reference->code ?? null;
                    })
                    ->url(function ($record) {
                        if (!$record->reference_type || !$record->reference_id)
                            return null;

                        // Handle Purchase Invoice Items (link to parent invoice)
                        if (str_contains($record->reference_type, 'PurchaseInvoiceItem')) {
                            return \App\Filament\Resources\PurchaseInvoiceResource::getUrl('view', ['record' => $record->reference->purchase_invoice_id]);
                        }

                        // Try to match common types
                        // Note: Adjust namespaces to match your actual Resource locations
                        if (str_contains($record->reference_type, 'SalesInvoice')) {
                            return \App\Filament\Resources\SalesInvoiceResource::getUrl('view', ['record' => $record->reference_id]);
                        }
                        if (str_contains($record->reference_type, 'PurchaseInvoice')) {
                            return \App\Filament\Resources\PurchaseInvoiceResource::getUrl('view', ['record' => $record->reference_id]);
                        }
                        if (str_contains($record->reference_type, 'Import')) {
                            // Link to import?
                            return null;
                        }
                        return null;
                    }, shouldOpenInNewTab: true)
                    ->color('primary'),
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Gudang')
                    ->icon('heroicon-o-home-modern')
                    ->placeholder('Tanpa Gudang (Unassigned)'),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Masuk/Keluar')
                    ->numeric()
                    ->badge()
                    ->color(fn($state) => $state > 0 ? 'success' : 'danger')
                    ->formatStateUsing(fn($state) => ($state > 0 ? '+' : '') . number_format($state, 0, ',', '.')),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                //
            ]);
    }
}
