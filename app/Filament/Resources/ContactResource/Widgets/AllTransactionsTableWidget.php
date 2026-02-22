<?php

namespace App\Filament\Resources\ContactResource\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Models\SalesInvoice;
use App\Models\PurchaseInvoice;
use App\Models\Expense;

class AllTransactionsTableWidget extends BaseWidget
{
    public $record;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Semua Transaksi';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
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
                    ->limit(50),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Total')
                    ->money('IDR')
                    ->alignEnd()
                    ->sortable(),
            ])
            ->defaultSort('date', 'desc')
            ->paginated([10, 25, 50]);
    }

    protected function getTableQuery(): Builder
    {
        $contactId = $this->record->id;

        // Create a base query from sales invoices
        $query = SalesInvoice::query()
            ->where('contact_id', $contactId)
            ->select([
                'id',
                'transaction_date as date',
                'invoice_number as number',
                DB::raw("'Tagihan Penjualan' as description"),
                'total_amount as amount',
            ]);

        // Union with purchase invoices
        $query->union(
            PurchaseInvoice::query()
                ->where('contact_id', $contactId)
                ->select([
                    'id',
                    'transaction_date as date',
                    'invoice_number as number',
                    DB::raw("'Tagihan Pembelian' as description"),
                    'total_amount as amount',
                ])
        );

        // Union with expenses
        $query->union(
            Expense::query()
                ->where('contact_id', $contactId)
                ->select([
                    'id',
                    'transaction_date as date',
                    'reference_number as number',
                    DB::raw("COALESCE(memo, 'Biaya') as description"),
                    'total_amount as amount',
                ])
        );

        return $query;
    }
}
