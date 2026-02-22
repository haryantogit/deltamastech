<?php

namespace App\Filament\Pages;

use App\Models\Account;
use App\Models\JournalItem;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

use Livewire\Attributes\On;

class KasBankPage extends Page
{
    public array $filters = [];

    public function mount(): void
    {
        $this->filters = [
            'startDate' => now()->startOfYear()->toDateString(),
            'endDate' => now()->toDateString(),
        ];
    }

    #[On('filtersUpdated')]
    public function applyFilters(array $filters): void
    {
        $this->filters = $filters;
    }

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-library';

    protected string $view = 'filament.pages.kas-bank-page';

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    protected static ?int $navigationSort = 40;

    protected static string|null $navigationLabel = 'Kas & Bank';
    protected ?string $subheading = null;
    protected static ?string $title = 'Halaman Kas & Bank';
    protected static ?string $slug = 'kas-bank';

    protected function getViewData(): array
    {
        $accounts = Account::where('category', 'Kas & Bank')
            ->where('is_active', true)
            ->get();

        $startDate = $this->filters['startDate'] ?? now()->startOfYear()->toDateString();
        $endDate = $this->filters['endDate'] ?? now()->toDateString();

        $accountsData = $accounts->map(function ($account) use ($endDate) {
            // Get current balance up to selected date
            $itemsQuery = JournalItem::where('account_id', $account->id)
                ->whereHas('journalEntry', function ($query) use ($endDate) {
                    $query->where('transaction_date', '<=', $endDate);
                });

            $debit = (float) $itemsQuery->sum('debit');
            $credit = (float) $itemsQuery->sum('credit');
            $currentBalance = $debit - $credit;

            // Get historical trend (last 6 months leading to end date)
            $trend = [];
            $endCarbon = Carbon::parse($endDate);
            for ($i = 5; $i >= 0; $i--) {
                $date = $endCarbon->copy()->subMonths($i)->endOfMonth();

                $historicalItems = JournalItem::where('account_id', $account->id)
                    ->whereHas('journalEntry', function ($query) use ($date) {
                        $query->where('transaction_date', '<=', $date->format('Y-m-d'));
                    });

                $hDebit = (float) $historicalItems->sum('debit');
                $hCredit = (float) $historicalItems->sum('credit');
                $trend[] = $hDebit - $hCredit;
            }

            return [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'current_balance' => $currentBalance,
                'trend' => $trend,
            ];
        })->values()->toArray();

        return [
            'accounts' => $accountsData,
            'total_balance' => collect($accountsData)->sum('current_balance'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make()
                ->label('Tambah Kas & Bank')
                ->model(Account::class)
                ->form(\App\Filament\Resources\AccountResource::form(
                    \Filament\Schemas\Schema::make()
                )->getComponents())
                ->mutateFormDataUsing(function (array $data): array {
                    $data['category'] = 'Kas & Bank';
                    return $data;
                })
                ->modalHeading('Tambah Akun Kas & Bank')
                ->modalWidth('md')
                ->color('primary')
                ->icon('heroicon-m-plus'),

            \Filament\Actions\Action::make('print')
                ->label('Print')
                ->color('gray')
                ->outlined()
                ->icon('heroicon-m-printer'),

            \Filament\Actions\Action::make('report')
                ->label('Laporan')
                ->color('gray')
                ->outlined()
                ->icon('heroicon-m-document-text'),
        ];
    }

    protected static string|\UnitEnum|null $navigationGroup = null;

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            'Kas & Bank',
        ];
    }
}
