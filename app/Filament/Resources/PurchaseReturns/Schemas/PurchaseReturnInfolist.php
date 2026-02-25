<?php

namespace App\Filament\Resources\PurchaseReturns\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PurchaseReturnInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('number'),
                TextEntry::make('purchase_invoice_id')
                    ->numeric(),
                TextEntry::make('supplier.name')
                    ->label('Supplier'),
                TextEntry::make('warehouse.name')
                    ->label('Warehouse')
                    ->placeholder('-'),
                TextEntry::make('date')
                    ->date(),
                TextEntry::make('reference')
                    ->placeholder('-'),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                IconEntry::make('tax_inclusive')
                    ->boolean(),
                TextEntry::make('sub_total')
                    ->numeric(),
                TextEntry::make('tax_amount')
                    ->numeric(),
                TextEntry::make('total_amount')
                    ->numeric(),
                TextEntry::make('status'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
