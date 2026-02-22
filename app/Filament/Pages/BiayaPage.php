<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

use App\Models\Expense;
use Carbon\Carbon;

class BiayaPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected string $view = 'filament.pages.biaya-page';

    protected static ?string $title = 'Halaman Biaya';

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    protected static ?int $navigationSort = 4;

    protected static string|null $navigationLabel = 'Biaya';

    protected static bool $shouldRegisterNavigation = false;

    protected static string|\UnitEnum|null $navigationGroup = null;

    public array $stats = [];
    public string $search = '';
    public string $filterStatus = 'semua';

    public function mount(): void
    {
        $this->stats = $this->getStats();
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\CompanyCashStats::class,
        ];
    }

    public function getExpenses()
    {
        $query = Expense::query()->with(['contact', 'tags']);

        if ($this->search) {
            $query->where(fn($q) => $q->where('reference_number', 'like', '%' . $this->search . '%')
                ->orWhere('memo', 'like', '%' . $this->search . '%')
                ->orWhereHas('contact', fn($q) => $q->where('name', 'like', '%' . $this->search . '%')));
        }

        if ($this->filterStatus !== 'semua') {
            if ($this->filterStatus === 'belum_dibayar') {
                $query->where('is_pay_later', true);
            } elseif ($this->filterStatus === 'lunas') {
                $query->where('is_pay_later', false);
            }
        }

        return $query->orderBy('transaction_date', 'desc')->orderBy('id', 'desc')->paginate(10);
    }

    protected function getStats(): array
    {
        $now = Carbon::now();
        $thisMonth = Expense::whereMonth('transaction_date', $now->month)
            ->whereYear('transaction_date', $now->year)
            ->sum('total_amount');

        $last30Days = Expense::where('transaction_date', '>=', $now->copy()->subDays(30))
            ->sum('total_amount');

        $unpaid = Expense::where('is_pay_later', true)->sum('total_amount');

        // Due soon (next 7 days) if pay later
        $dueSoon = Expense::where('is_pay_later', true)
            ->where('transaction_date', '<', $now->copy()->subDays(30)) // Simple logic for now
            ->sum('total_amount');

        return [
            'bulan_ini' => $thisMonth,
            'last_30_days' => $last30Days,
            'unpaid' => $unpaid,
            'due_soon' => $dueSoon,
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            'Biaya',
        ];
    }
}
