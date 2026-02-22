<?php

namespace App\Filament\Widgets;

use App\Models\Account;
use App\Models\JournalItem;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class AccountMovementWidget extends BaseWidget
{
    use InteractsWithPageFilters;


    protected static ?string $heading = 'NET PERGERAKAN AKUN';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        $filters = $this->filters ?? request()->input('filters') ?? [];
        $startDate = $filters['startDate'] ?? now()->startOfYear()->toDateString();
        $endDate = $filters['endDate'] ?? now()->endOfYear()->toDateString();

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        // Reference periods for comparison (optional, but let's stick to the range for the first column)
        $movementSubquery = JournalItem::query()
            ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
            ->select('account_id')
            ->selectRaw("SUM(CASE WHEN journal_entries.transaction_date BETWEEN ? AND ? THEN debit - credit ELSE 0 END) as range_balance", [$start, $end])
            ->selectRaw("SUM(CASE WHEN YEAR(journal_entries.transaction_date) = ? THEN debit - credit ELSE 0 END) as year_balance", [$end->year])
            ->groupBy('account_id');

        return $table
            ->query(
                Account::query()
                    ->where('category', 'Kas & Bank')
                    ->leftJoinSub($movementSubquery, 'movements', 'accounts.id', '=', 'movements.account_id')
                    ->select('accounts.*')
                    ->selectRaw('COALESCE(movements.range_balance, 0) as range_balance')
                    ->selectRaw('COALESCE(movements.year_balance, 0) as year_balance')
                    ->limit(8)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Akun')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('range_balance')
                    ->label('Pergerakan (Range)')
                    ->money('IDR')
                    ->color(fn($state) => $state < 0 ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('year_balance')
                    ->label('Tahun ini (' . (($this->filters['endDate'] ?? null) ? Carbon::parse($this->filters['endDate'])->year : now()->year) . ')')
                    ->money('IDR')
                    ->color(fn($state) => $state < 0 ? 'danger' : 'success'),
            ])
            ->paginated(false);
    }
}
