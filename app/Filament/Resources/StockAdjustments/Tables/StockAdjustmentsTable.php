<?php

namespace App\Filament\Resources\StockAdjustments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;
use App\Models\StockAdjustment;

class StockAdjustmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(\Illuminate\Database\Eloquent\Builder $query) => $query->with(['warehouse']))
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('number')
                    ->label('Nomor')
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Gudang')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('reason')
                    ->label('Alasan')
                    ->badge()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('total_qty')
                    ->label('Total Qty')
                    ->getStateUsing(fn(StockAdjustment $record): float => (float) $record->items()->sum('quantity'))
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                ])
                    ->icon('heroicon-m-ellipsis-vertical'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
