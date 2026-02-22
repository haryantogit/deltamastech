<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;

class StocksRelationManager extends RelationManager
{
    protected static string $relationship = 'stocks';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-building-storefront';

    protected static ?string $title = 'Stok Gudang';

    protected static ?string $recordTitleAttribute = 'warehouse.name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Read-only, no form needed
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('warehouse.name')
            ->columns([
                TextColumn::make('warehouse.name')
                    ->label('Warehouse')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-building-storefront'),

                BadgeColumn::make('quantity')
                    ->label('Quantity')
                    ->numeric()
                    ->sortable()
                    ->color(fn($record) => match (true) {
                        $record->quantity > 10 => 'success',
                        $record->quantity >= 1 && $record->quantity <= 10 => 'warning',
                        default => 'danger',
                    }),

                TextColumn::make('product.unit.name')
                    ->label('Unit')
                    ->default('-'),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Stock adjustments handled elsewhere
            ])
            ->actions([
                // View only
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('warehouse.name', 'asc')
            ->emptyStateHeading('No stock records')
            ->emptyStateDescription('This product has no stock in any warehouse yet.')
            ->emptyStateIcon('heroicon-o-cube');
    }
}
