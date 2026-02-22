<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFilters;
use Livewire\Attributes\On;

class Dashboard extends BaseDashboard
{
    use HasFilters;

    protected static ?string $navigationLabel = 'Beranda';
    protected static ?int $navigationSort = 1;

    public function getTitle(): string
    {
        return 'Beranda';
    }

    public function getBreadcrumbs(): array
    {
        return [
            '/' => 'Beranda',
        ];
    }

    #[On('filtersUpdated')]
    public function applyFilters(array $filters): void
    {
        $this->filters = $filters;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('Cetak')
                ->icon('heroicon-m-printer')
                ->color('gray')
                ->extraAttributes(['onclick' => 'window.print()']),
            Action::make('share')
                ->label('Bagikan')
                ->icon('heroicon-m-share')
                ->color('info'),
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\DashboardFilter::class,
        ];
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\DebtReceivableChart::class,
            \App\Filament\Widgets\PurchaseSalesChart::class,
            \App\Filament\Widgets\AccountMovementWidget::class,
            \App\Filament\Widgets\UnpaidDebtChart::class,
            \App\Filament\Widgets\CashBalanceChart::class,
            \App\Filament\Widgets\MoneyInOutChart::class,
            \App\Filament\Widgets\ExpenseBreakdownChart::class,
            \App\Filament\Widgets\ProfitLossChart::class,
            \App\Filament\Widgets\SalesChart::class,
            \App\Filament\Widgets\BankBcaCorporateChart::class,
            \App\Filament\Widgets\BankBcaOperasionalChart::class,
            \App\Filament\Widgets\BankBriChart::class,
            \App\Filament\Widgets\KasPenjualanOnlineChart::class,
            \App\Filament\Widgets\KasShopeeChart::class,
            \App\Filament\Widgets\KasLazadaChart::class,
        ];
    }
}
