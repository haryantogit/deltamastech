<?php

namespace App\Filament\Resources\ContactResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'salesInvoices';

    protected static ?string $title = 'Transaksi';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $this->getTransactionsQuery($query))
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('number')
                    ->label('Transaksi')
                    ->searchable()
                    ->weight('bold')
                    ->color('primary'),
                Tables\Columns\TextColumn::make('description')
                    ->label('Keterangan')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Total')
                    ->money('IDR')
                    ->alignEnd()
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
            ])
            ->defaultSort('date', 'desc')
            ->paginated([10, 25, 50]);
    }

    protected function getTransactionsQuery(Builder $query): Builder
    {
        $contactId = $this->getOwnerRecord()->id;

        // Use a raw query to combine all transactions
        return $query->fromSub(function ($query) use ($contactId) {
            $query->from('sales_invoices')
                ->where('contact_id', $contactId)
                ->select([
                    'id',
                    'transaction_date as date',
                    'invoice_number as number',
                    DB::raw("'Tagihan Penjualan' as description"),
                    'total_amount as amount',
                ])
                ->union(
                    DB::table('purchase_invoices')
                        ->where('contact_id', $contactId)
                        ->select([
                            'id',
                            'transaction_date as date',
                            'invoice_number as number',
                            DB::raw("'Tagihan Pembelian' as description"),
                            'total_amount as amount',
                        ])
                )
                ->union(
                    DB::table('expenses')
                        ->where('contact_id', $contactId)
                        ->select([
                            'id',
                            'transaction_date as date',
                            'reference_number as number',
                            DB::raw("COALESCE(memo, 'Biaya') as description"),
                            'total_amount as amount',
                        ])
                );
        }, 'transactions');
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
