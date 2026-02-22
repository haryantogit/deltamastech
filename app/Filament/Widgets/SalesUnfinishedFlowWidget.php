<?php

namespace App\Filament\Widgets;

use App\Models\SalesDelivery;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\SalesQuotation;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class SalesUnfinishedFlowWidget extends Widget
{
    protected string $view = 'filament.widgets.sales-unfinished-flow-widget';

    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 'full';

    public ?string $filter = null;

    protected $listeners = ['update-sales-overview-filter' => 'updateFilter'];

    public function updateFilter(string $filter): void
    {
        $this->filter = $filter;
    }

    public function getViewData(): array
    {
        $filter = $this->filter ?? request()->query('filter', 'year');
        $now = Carbon::now();

        if ($filter === 'year') {
            $startDate = $now->copy()->startOfYear();
            $endDate = $now->copy()->endOfYear();
        } else {
            $startDate = $now->copy()->startOfMonth();
            $endDate = $now->copy()->endOfMonth();
        }

        // 1. Pesanan (Penawaran disetujui in this period / Accepted, but not yet converted to Order)
        $quotationCount = SalesQuotation::where('status', 'accepted')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->whereNotExists(function ($query) {
                $query->selectRaw(1)
                    ->from('sales_orders')
                    ->whereColumn('sales_orders.sales_quotation_id', 'sales_quotations.id');
            })
            ->count();

        // 2. Pemesanan (Belum Selesai) - Global backlog
        $orderCount = SalesOrder::whereNotIn('status', ['completed', 'cancelled', 'draft'])->count();

        // 3. Pengiriman (Belum Ditagih - Deliveries without invoices) - Global backlog
        $deliveryUnbilledCount = SalesDelivery::whereNotExists(function ($query) {
            $query->selectRaw(1)
                ->from('sales_invoices')
                ->whereColumn('sales_invoices.sales_order_id', 'sales_deliveries.sales_order_id');
        })
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->count();

        $deliveryUnbilledAmount = SalesDelivery::whereNotExists(function ($query) {
            $query->selectRaw(1)
                ->from('sales_invoices')
                ->whereColumn('sales_invoices.sales_order_id', 'sales_deliveries.sales_order_id');
        })
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->get()
            ->sum(fn($d) => $d->salesOrder->total_amount ?? 0);

        // 4. Tagihan (Jatuh Tempo) - Global backlog
        $overdueCount = SalesInvoice::where('status', '!=', 'paid')
            ->where('due_date', '<', now())
            ->count();
        $overdueAmount = SalesInvoice::where('status', '!=', 'paid')
            ->where('due_date', '<', now())
            ->sum('balance_due');

        return [
            'quotationCount' => $quotationCount,
            'orderCount' => $orderCount,
            'deliveryUnbilled' => $deliveryUnbilledAmount,
            'deliveryUnbilledCount' => $deliveryUnbilledCount,
            'overdueCount' => $overdueCount,
            'overdueAmount' => $overdueAmount,
        ];
    }
}
