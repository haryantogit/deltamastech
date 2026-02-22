<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\PurchaseInvoiceItem;

class PurchaseHistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'purchaseInvoiceItems';

    protected static ?string $title = 'Riwayat Pembelian';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-shopping-cart';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('invoice.number')
                    ->label('No. Tagihan')
                    ->searchable()
                    ->sortable()
                    ->color('primary')
                    ->url(fn(PurchaseInvoiceItem $record) => \App\Filament\Resources\PurchaseInvoiceResource::getUrl('view', ['record' => $record->purchase_invoice_id])),

                Tables\Columns\TextColumn::make('invoice.supplier.name')
                    ->label('Pemasok')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Qty')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('unit_price')
                    ->label('Harga Satuan')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_price')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),
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
