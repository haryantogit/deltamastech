<?php

namespace App\Filament\Resources\ContactResource\Widgets;

use Filament\Widgets\Widget as BaseWidget;
use Illuminate\Database\Eloquent\Model;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ContactTransactionsWidget extends BaseWidget
{
    use WithPagination;

    public ?Model $record = null;

    public $activeTab = 'transaksi';

    public $search = '';

    public $tableRecordsPerPage = 10;

    protected string $view = 'filament.resources.contact-resource.widgets.contact-transactions-widget';

    protected int|string|array $columnSpan = 'full';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function getTabStatsProperty(): array
    {
        $contactId = $this->record->id;

        $salesQuery = DB::table('sales_invoices')->where('contact_id', $contactId);
        $purchaseQuery = DB::table('purchase_invoices')->where('supplier_id', $contactId);
        $expenseQuery = DB::table('expenses')->where('contact_id', $contactId);

        return [
            'transaksi' => [
                'count' => $salesQuery->count() + $purchaseQuery->count() + $expenseQuery->count(),
                'total' => null,
            ],
            'piutang' => [
                'count' => $salesQuery->count(),
                'total' => $salesQuery->sum('total_amount'),
            ],
            'hutang' => [
                'count' => $purchaseQuery->count(),
                'total' => $purchaseQuery->sum('total_amount'),
            ],
        ];
    }

    public function getTransactionsProperty(): LengthAwarePaginator
    {
        $contactId = $this->record->id;

        // Base queries
        $salesInvoices = DB::table('sales_invoices')
            ->where('contact_id', $contactId)
            ->select(['id', 'transaction_date as date', 'invoice_number as number', 'total_amount as amount', DB::raw("'Penjualan' as type_label")]);

        $purchaseInvoices = DB::table('purchase_invoices')
            ->where('supplier_id', $contactId)
            ->select(['id', 'date', 'number', 'total_amount as amount', DB::raw("'Pembelian' as type_label")]);

        $expenses = DB::table('expenses')
            ->where('contact_id', $contactId)
            ->select(['id', 'transaction_date as date', 'reference_number as number', 'total_amount as amount', DB::raw("'Biaya' as type_label")]);

        $unionQueries = [];

        if ($this->activeTab === 'transaksi' || $this->activeTab === 'piutang') {
            $unionQueries[] = $salesInvoices;
        }
        if ($this->activeTab === 'transaksi' || $this->activeTab === 'hutang') {
            $unionQueries[] = $purchaseInvoices;
        }
        if ($this->activeTab === 'transaksi' || $this->activeTab === 'biaya') {
            $unionQueries[] = $expenses;
        }

        if (empty($unionQueries)) {
            return new LengthAwarePaginator(new Collection(), 0, $this->tableRecordsPerPage);
        }

        $query = array_shift($unionQueries);
        foreach ($unionQueries as $subQuery) {
            $query->union($subQuery);
        }

        $resultsQuery = DB::table(DB::raw("({$query->toSql()}) as combined"))
            ->mergeBindings($query);

        if (!empty($this->search)) {
            $resultsQuery->where('number', 'like', '%' . $this->search . '%')
                ->orWhere('type_label', 'like', '%' . $this->search . '%');
        }

        return $resultsQuery
            ->orderBy('date', 'desc')
            ->paginate($this->tableRecordsPerPage);
    }
}
