<?php

namespace App\Filament\Resources\FixedAssetResource\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class FixedAssetTransactionsWidget extends BaseWidget
{
    public ?\Illuminate\Database\Eloquent\Model $record = null;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = null;

    public function table(Table $table): Table
    {
        return $table
            ->query(\App\Models\Product::query()->whereRaw('1 = 0')) // Dummy query
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->formatStateUsing(fn($state) => $state instanceof \Carbon\Carbon ? $state->format('d/m/Y') : $state)
                    ->sortable(),
                Tables\Columns\TextColumn::make('source')
                    ->label('Sumber')
                    ->badge()
                    ->color(fn($record) => match ($record->type ?? 'default') {
                        'purchase' => 'warning',
                        'upgrade' => 'success',
                        'depreciation' => ($record->status ?? '') === 'posted' ? 'info' : 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(50)
                    ->formatStateUsing(
                        fn($state, $record) =>
                        ($record->status ?? '') === 'scheduled'
                        ? $state . ' (Proyeksi)'
                        : $state
                    ),
                Tables\Columns\TextColumn::make('reference')
                    ->label('Referensi')
                    ->default('—')
                    ->color('primary')
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('debit')
                    ->label('Debit')
                    ->formatStateUsing(fn($state) => $state > 0 ? 'Rp ' . number_format($state, 0, ',', '.') : '—')
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('credit')
                    ->label('Kredit')
                    ->formatStateUsing(fn($state) => $state > 0 ? 'Rp ' . number_format($state, 0, ',', '.') : '—')
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Saldo')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->weight('bold')
                    ->alignEnd(),
            ])
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10);
    }

    protected function paginateTableQuery(Builder $query): Paginator
    {
        $transactions = $this->getTransactions();

        $page = request()->get('page', 1);
        $perPage = $this->getTableRecordsPerPage();

        return new LengthAwarePaginator(
            $transactions->forPage($page, $perPage),
            $transactions->count(),
            $perPage,
            $page,
            ['path' => request()->url()]
        );
    }

    protected function getTransactions()
    {
        if (!$this->record) {
            return collect([]);
        }

        $record = $this->record;

        // Calculate all transactions
        $purchasePrice = $record->purchase_price ?? 0;
        $salvageValue = $record->salvage_value ?? 0;
        $usefulLifeYears = $record->useful_life_years ?? 0;
        $usefulLifeMonths = $record->useful_life_months ?? 0;
        $rate = $record->depreciation_rate ?? 0;
        $method = $record->depreciation_method ?? 'straight_line';
        $startDate = $record->depreciation_start_date ? \Carbon\Carbon::parse($record->depreciation_start_date) : null;
        $purchaseDate = $record->purchase_date ? \Carbon\Carbon::parse($record->purchase_date) : null;

        $upgrades = $record->fixedAssetUpgrades()->get();
        $postedDepreciations = $record->fixedAssetDepreciations()->with('journalEntry')->get();
        $postedPeriods = $postedDepreciations->pluck('period')->toArray();

        $monthlyDepreciation = 0;
        $totalMonths = ($usefulLifeYears * 12) + $usefulLifeMonths;

        if ($method === 'straight_line') {
            if ($totalMonths > 0) {
                $monthlyDepreciation = ($purchasePrice - $salvageValue) / $totalMonths;
            } elseif ($rate > 0) {
                $monthlyDepreciation = ($purchasePrice * ($rate / 100)) / 12;
            }
        } else {
            if ($rate > 0) {
                $monthlyDepreciation = ($purchasePrice * ($rate / 100)) / 12;
            }
        }

        $events = collect([]);

        // Purchase transaction
        if ($purchaseDate) {
            $events->push((object) [
                'id' => 'purchase_' . $record->id,
                'date' => $purchaseDate,
                'source' => 'Pembelian',
                'description' => 'Pendaftaran Aset Tetap',
                'reference' => $record->sku ?? '-',
                'debit' => $purchasePrice,
                'credit' => 0,
                'type' => 'purchase',
                'status' => 'posted'
            ]);
        }

        // Upgrades
        foreach ($upgrades as $upgrade) {
            $events->push((object) [
                'id' => 'upgrade_' . $upgrade->id,
                'date' => \Carbon\Carbon::parse($upgrade->date),
                'source' => 'Upgrade',
                'description' => $upgrade->description ?? 'Upgrade Aset',
                'reference' => $upgrade->reference ?? '-',
                'debit' => $upgrade->amount,
                'credit' => 0,
                'type' => 'upgrade',
                'status' => 'posted'
            ]);
        }

        // Posted depreciations
        foreach ($postedDepreciations as $posted) {
            if (!$posted->journalEntry)
                continue;
            $events->push((object) [
                'id' => 'depreciation_' . $posted->id,
                'date' => \Carbon\Carbon::parse($posted->journalEntry->transaction_date),
                'source' => 'Penyusutan',
                'description' => $posted->journalEntry->description,
                'reference' => $posted->journalEntry->reference_number,
                'debit' => 0,
                'credit' => $posted->amount,
                'type' => 'depreciation',
                'status' => 'posted'
            ]);
        }

        // Projected depreciations
        if ($record->has_depreciation && $startDate && $monthlyDepreciation > 0) {
            $currentDate = now();
            $projectionMonths = 12;

            for ($i = 0; $i < ($totalMonths ?: 120); $i++) {
                $deprDate = $startDate->copy()->addMonths($i)->endOfMonth();
                $period = $deprDate->format('Y-m');

                if (in_array($period, $postedPeriods))
                    continue;
                if ($deprDate->gt($currentDate->copy()->addMonths($projectionMonths)))
                    break;

                $events->push((object) [
                    'id' => 'scheduled_' . $period,
                    'date' => $deprDate,
                    'source' => 'Penyusutan',
                    'description' => 'Penyusutan Bulanan (Draft)',
                    'reference' => '-',
                    'debit' => 0,
                    'credit' => $monthlyDepreciation,
                    'type' => 'depreciation',
                    'status' => 'scheduled'
                ]);
            }
        }

        // Sort and calculate running balance
        $events = $events->sortBy(fn($e) => $e->date->timestamp)->values();

        $runningBalance = 0;
        return $events->map(function ($event) use (&$runningBalance) {
            $runningBalance += ($event->debit - $event->credit);
            $event->balance = $runningBalance;
            return $event;
        });
    }
}
