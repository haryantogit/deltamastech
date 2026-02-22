<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseDelivery;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class PurchaseUnfinishedFlowWidget extends Widget
{
    protected string $view = 'filament.widgets.purchase-unfinished-flow-widget';

    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 'full';

    public ?string $filter = null;

    protected $listeners = ['update-purchase-overview-filter' => '$refresh'];

    public function getViewData(): array
    {
        $filter = request()->query('filter', 'year');
        $now = Carbon::now();

        if ($filter === 'year') {
            $startDate = $now->copy()->startOfYear();
            $endDate = $now->copy()->endOfYear();
        } else {
            $startDate = $now->copy()->startOfMonth();
            $endDate = $now->copy()->endOfMonth();
        }

        // 1. Permintaan Pembelian (Draft Purchase Orders)
        $requestCount = PurchaseOrder::where('status', 'draft')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        // 2. Pemesanan (Belum Selesai) - Active orders not completed
        $orderCount = PurchaseOrder::whereNotIn('status', ['completed', 'cancelled', 'draft'])->count();

        // 3. Penerimaan - Total deliveries in period
        $deliveryCount = PurchaseDelivery::whereNotIn('status', ['cancelled', 'draft'])
            ->whereBetween('date', [$startDate, $endDate])
            ->count();

        // 4. Tagihan - Total invoices in period
        $invoiceCount = PurchaseInvoice::whereBetween('date', [$startDate, $endDate])
            ->count();

        // Sub-counts for labels
        $unpaidInvoiceCount = PurchaseInvoice::whereBetween('date', [$startDate, $endDate])
            ->where('status', '!=', 'paid')
            ->count();

        return [
            'requestCount' => $requestCount,
            'orderCount' => $orderCount,
            'deliveryCount' => $deliveryCount,
            'invoiceCount' => $invoiceCount,
            'unpaidInvoiceCount' => $unpaidInvoiceCount,
        ];
    }
}
