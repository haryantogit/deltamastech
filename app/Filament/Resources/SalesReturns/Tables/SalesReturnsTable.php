<?php

namespace App\Filament\Resources\SalesReturns\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Table;

class SalesReturnsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('no')
                    ->label('No.')
                    ->rowIndex(),
                \Filament\Tables\Columns\TextColumn::make('number')
                    ->label('Nomor')
                    ->searchable()
                    ->sortable()
                    ->color('primary')
                    ->weight('bold')
                    ->url(fn($record) => route('filament.admin.resources.sales-returns.view', $record)),
                \Filament\Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->label('Faktur Asal')
                    ->searchable()
                    ->sortable()
                    ->url(fn(\App\Models\SalesReturn $record) => $record->sales_invoice_id ? \App\Filament\Resources\SalesInvoiceResource::getUrl('view', ['record' => $record->sales_invoice_id]) : null)
                    ->color('primary'),
                \Filament\Tables\Columns\TextColumn::make('contact.name')
                    ->label('Pelanggan')
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
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\ViewAction::make()->label('Lihat'),
                    \Filament\Actions\EditAction::make()->label('Ubah'),
                    \Filament\Actions\DeleteAction::make()->label('Hapus'),
                ])->icon('heroicon-m-ellipsis-vertical'),
            ])
            ->toolbarActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ])
                    ->icon('heroicon-m-ellipsis-vertical'),
            ]);
    }
}
