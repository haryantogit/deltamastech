<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use App\Filament\Resources\ExpenseResource;
use App\Models\Expense;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListExpenses extends ListRecords
{
    protected static string $resource = ExpenseResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua')
                ->badge(Expense::count()),
            'unpaid' => Tab::make('Belum Dibayar')
                ->badge(Expense::where('remaining_amount', '>', 0)->whereColumn('remaining_amount', '=', 'total_amount')->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->where('remaining_amount', '>', 0)->whereColumn('remaining_amount', '=', 'total_amount')),
            'partial' => Tab::make('Dibayar Sebagian')
                ->badge(Expense::where('remaining_amount', '>', 0)->whereColumn('remaining_amount', '<', 'total_amount')->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->where('remaining_amount', '>', 0)->whereColumn('remaining_amount', '<', 'total_amount')),
            'paid' => Tab::make('Lunas')
                ->badge(Expense::where('remaining_amount', '<=', 0)->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->where('remaining_amount', '<=', 0)),
            'overdue' => Tab::make('Jatuh Tempo')
                ->badge(Expense::where('remaining_amount', '>', 0)->where('due_date', '<', now())->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->where('remaining_amount', '>', 0)->where('due_date', '<', now())),
            'recurring' => Tab::make('Transaksi Berulang')
                ->badge(Expense::where('is_recurring', true)->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->where('is_recurring', true)),
        ];
    }

    public bool $showCharts = false;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('toggleCharts')
                ->label(fn() => $this->showCharts ? 'Sembunyikan Grafik' : 'Tampilkan Grafik')
                ->icon(fn() => $this->showCharts ? 'heroicon-m-eye-slash' : 'heroicon-m-eye')
                ->color('gray')
                ->action(fn() => $this->showCharts = !$this->showCharts)
                ->button(),
            Actions\Action::make('print')
                ->label('Cetak')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->extraAttributes(['onclick' => 'window.print(); return false;']),
            Actions\CreateAction::make()
                ->label('Tambah Biaya')
                ->icon('heroicon-m-plus')
                ->color('primary'),
        ];
    }

    public function getFooter(): ?\Illuminate\Contracts\View\View
    {
        return view('filament.resources.expense-resource.pages.list-expenses-footer');
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 3;
    }

    protected function getHeaderWidgets(): array
    {
        $widgets = [
            \App\Filament\Widgets\ExpenseStats::class,
        ];

        if ($this->showCharts) {
            $widgets[] = \App\Filament\Widgets\ExpenseCategoryChart::class;
            $widgets[] = \App\Filament\Widgets\ExpenseContactChart::class;
        }

        return $widgets;
    }

    public function getTabsContentComponent(): \Filament\Schemas\Components\Component
    {
        return parent::getTabsContentComponent()
            ->contained(true);
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            'Biaya',
        ];
    }
}
