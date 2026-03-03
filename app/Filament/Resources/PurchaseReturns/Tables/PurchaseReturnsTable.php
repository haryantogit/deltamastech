<?php

namespace App\Filament\Resources\PurchaseReturns\Tables;

use Filament\Tables\Table;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use App\Filament\Resources\PurchaseReturns\PurchaseReturnResource;

class PurchaseReturnsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no')
                    ->label('No.')
                    ->rowIndex(),
                TextColumn::make('number')
                    ->label('Nomor')
                    ->searchable()
                    ->sortable()
                    ->color('primary')
                    ->weight('bold')
                    ->url(fn($record) => PurchaseReturnResource::getUrl('view', ['record' => $record]))
                    ->copyable(),
                \Filament\Tables\Columns\TextColumn::make('invoice.number')
                    ->label('Faktur Asal')
                    ->searchable()
                    ->sortable()
                    ->url(fn(\App\Models\PurchaseReturn $record) => $record->purchase_invoice_id ? \App\Filament\Resources\PurchaseInvoiceResource::getUrl('view', ['record' => $record->purchase_invoice_id]) : null)
                    ->color('primary'),
                \Filament\Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Vendor')
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable()
                    ->alignment(\Filament\Support\Enums\Alignment::End),
                \Filament\Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'confirmed' => 'success',
                        default => 'primary',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'confirmed' => 'Disetujui',
                        default => ucfirst($state),
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\ViewAction::make()->label('Lihat'),
                    \Filament\Actions\EditAction::make()->label('Ubah'),
                    \Filament\Actions\DeleteAction::make()->label('Hapus'),
                ])->icon('heroicon-m-ellipsis-vertical'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
