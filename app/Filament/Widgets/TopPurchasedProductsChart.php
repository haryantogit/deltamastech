<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseInvoiceItem;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TopPurchasedProductsChart extends ChartWidget
{
    protected ?string $heading = 'PEMBELIAN PER PRODUK';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 4;

    protected ?string $maxHeight = '275px';

    protected $listeners = ['update-purchase-overview-filter' => '$refresh'];

    protected function getData(): array
    {
        $filter = request()->query('filter', 'year');
        $now = Carbon::now();

        if ($filter === 'year') {
            $start = $now->copy()->startOfYear();
            $end = $now->copy()->endOfYear();
            $periodLabel = 'Tahun Ini';
        } else {
            $start = $now->copy()->startOfMonth();
            $end = $now->copy()->endOfMonth();
            $periodLabel = 'Bulan Ini';
        }

        $grouping = $this->filter ?? 'product';

        if ($grouping === 'category') {
            $query = PurchaseInvoiceItem::select('products.category_id', DB::raw('SUM(purchase_invoice_items.quantity) as total_qty'))
                ->join('purchase_invoices', 'purchase_invoices.id', '=', 'purchase_invoice_items.purchase_invoice_id')
                ->join('products', 'products.id', '=', 'purchase_invoice_items.product_id');
        } else {
            $query = PurchaseInvoiceItem::select('purchase_invoice_items.product_id', DB::raw('SUM(purchase_invoice_items.quantity) as total_qty'))
                ->join('purchase_invoices', 'purchase_invoices.id', '=', 'purchase_invoice_items.purchase_invoice_id');
        }

        $query->whereBetween('purchase_invoices.date', [$start, $end]);

        $dateLabel = $start->format('d/m/Y') . ' - ' . $end->format('d/m/Y');

        if ($grouping === 'category') {
            $this->heading = "PEMBELIAN PER KATEGORI {$dateLabel}";
            $topItems = $query->groupBy('products.category_id')
                ->orderByDesc('total_qty')
                ->limit(5)
                ->with('product.category')
                ->get();

            $data = $topItems->pluck('total_qty')->toArray();
            $labels = $topItems->map(fn($item) => $item->product->category->name ?? 'Uncategorized')->toArray();
        } else {
            $this->heading = "PEMBELIAN PER PRODUK {$dateLabel}";
            $topItems = $query->groupBy('purchase_invoice_items.product_id')
                ->orderByDesc('total_qty')
                ->limit(5)
                ->with('product')
                ->get();

            $data = $topItems->pluck('total_qty')->toArray();
            $labels = $topItems->map(fn($item) => $item->product->name ?? 'Unknown')->toArray();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total',
                    'data' => $data,
                    'backgroundColor' => [
                        '#f43f5e',
                        '#fbbf24',
                        '#34d399',
                        '#60a5fa',
                        '#a78bfa',
                    ],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getFilters(): ?array
    {
        return [
            'product' => 'Jenis Produk',
            'category' => 'Kategori',
        ];
    }

    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => true,
            'aspectRatio' => 1.5,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
        ];
    }

    public function getDescription(): ?string
    {
        $filter = request()->query('filter', 'year');
        $periodLabel = $filter === 'year' ? 'Tahun Ini' : 'Bulan Ini';
        $grouping = $this->filter ?? 'product';
        return "Top 5 " . ($grouping === 'category' ? 'kategori' : 'produk') . " dengan total kuantitas pembelian {$periodLabel}";
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
