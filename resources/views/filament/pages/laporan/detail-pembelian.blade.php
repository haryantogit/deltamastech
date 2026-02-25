@php
    $viewData = $this->getViewData();
    $invoices = $viewData['invoices'];
    $paginator = $viewData['paginator'];
    $pageStats = $viewData['pageStats'];
    $globalStats = $viewData['globalStats'];
    $expandedInvoices = $this->expandedInvoices;

    $fmt = function ($num) {
        if ($num == 0)
            return '0';
        return number_format($num, 0, ',', '.');
    };

    $getStatusColor = function ($status) {
        return match (strtolower($status)) {
            'lunas', 'paid' => '#10b981',
            'terbit', 'posted', 'open' => '#3b82f6',
            'draf', 'draft' => '#64748b',
            default => '#ef4444',
        };
    };

    $getStatusLabel = function ($status) {
        return match (strtolower($status)) {
            'lunas', 'paid' => 'Lunas',
            'terbit', 'posted', 'open' => 'Terbit',
            'draf', 'draft' => 'Draf',
            default => ucfirst($status),
        };
    };
@endphp

<x-filament-panels::page>
    <style>
        .purchase-report-section {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            overflow-x: auto;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
            margin-bottom: 2rem;
        }

        .dark .purchase-report-section {
            background: #111827;
            border-color: #374151;
        }

        .purchase-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1200px;
        }

        .purchase-table th {
            padding: 1rem;
            font-size: 0.75rem;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            background: #f8fafc;
            border-bottom: 2px solid #f1f5f9;
            text-align: left;
        }

        .dark .purchase-table th {
            background: #1f2937;
            border-bottom-color: #374151;
        }

        .purchase-table td {
            padding: 0.875rem 1rem;
            font-size: 0.8125rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .dark .purchase-table td {
            border-bottom-color: #374151;
        }

        .row-invoice {
            cursor: pointer;
            transition: background 0.2s;
        }

        .row-invoice:hover {
            background: rgba(59, 130, 246, 0.02);
        }

        .dark .row-invoice:hover {
            background: rgba(255, 255, 255, 0.02);
        }

        .expand-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.25rem;
            height: 1.25rem;
            color: #3b82f6;
            font-weight: 800;
            font-size: 1rem;
        }

        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
            color: white;
        }

        .tag-badge {
            padding: 0.2rem 0.4rem;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 600;
            background: #f1f5f9;
            color: #475569;
            margin-right: 0.25rem;
        }

        .dark .tag-badge {
            background: #374151;
            color: #cbd5e1;
        }

        /* Expanded Details Style */
        .details-wrapper {
            background: rgba(59, 130, 246, 0.01);
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .dark .details-wrapper {
            background: rgba(255, 255, 255, 0.01);
            border-color: #1e293b;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            background: transparent;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        .dark .details-table {
            background: #111827;
            border-color: #374151;
        }

        .details-table th {
            background: rgba(59, 130, 246, 0.04);
            color: #475569;
            padding: 0.75rem;
            font-size: 0.7rem;
            text-transform: uppercase;
        }

        .dark .details-table th {
            background: rgba(255, 255, 255, 0.05);
            color: #94a3b8;
        }

        .details-table td {
            padding: 0.75rem;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.75rem;
        }

        .dark .details-table td {
            border-bottom-color: #1e293b;
        }

        .invoice-summary-grid {
            margin-top: 1.5rem;
            display: flex;
            justify-content: flex-end;
        }

        .summary-box {
            width: 300px;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.8125rem;
        }

        .summary-label {
            color: #64748b;
            font-weight: 500;
        }

        .summary-value {
            font-weight: 700;
            color: #1e293b;
        }

        .dark .summary-value {
            color: white;
        }

        /* Footer Stats */
        .footer-stats-container {
            display: flex;
            justify-content: flex-end;
            gap: 4rem;
            margin-top: 2rem;
        }

        .footer-group {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            min-width: 250px;
        }

        .footer-group-title {
            font-size: 0.875rem;
            font-weight: 800;
            color: #1e293b;
            text-align: right;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .dark .footer-group-title {
            color: white;
        }

        .footer-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.8125rem;
            line-height: 1.5;
        }

        .footer-label {
            color: #64748b;
            font-weight: 600;
            text-align: left;
        }

        .footer-value {
            font-weight: 800;
            color: #1e293b;
            text-align: right;
        }

        .dark .footer-value {
            color: white;
        }

        .text-blue {
            color: #3b82f6 !important;
        }

        /* Pagination Capsule Style */
        .pagination-container {
            margin-top: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>

    <div class="purchase-report-section">
        <table class="purchase-table">
            <thead>
                <tr>
                    <th style="width: 40px;"></th>
                    <th>Nomor</th>
                    <th>No. Pesanan</th>
                    <th>Tanggal</th>
                    <th>Nama Kontak</th>
                    <th>Status</th>
                    <th>Tag</th>
                    <th>Referensi</th>
                    <th style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoices as $invoice)
                    @php
                        $isExpanded = in_array($invoice->id, $expandedInvoices);
                        $debt = \App\Models\Debt::where('reference', $invoice->number)->first();
                        $paid = ($debt ? $debt->payments()->sum('amount') : 0) + ($invoice->down_payment ?? 0);
                    @endphp
                    <tr class="row-invoice" wire:click="toggleInvoice({{ $invoice->id }})">
                        <td>
                            <span class="expand-icon">{{ $isExpanded ? '−' : '+' }}</span>
                        </td>
                        <td style="font-weight: 700;" class="text-[#1e293b] dark:text-white">{{ $invoice->number }}</td>
                        <td style="color: #3b82f6; font-weight: 600;">{{ $invoice->purchaseOrder->number ?? '-' }}</td>
                        <td>{{ $invoice->date->format('d/m/Y') }}</td>
                        <td style="font-weight: 600;">{{ $invoice->supplier->name }}</td>
                        <td>
                            <span class="status-badge" style="background: {{ $getStatusColor($invoice->status) }}">
                                {{ $getStatusLabel($invoice->status) }}
                            </span>
                        </td>
                        <td>
                            @foreach($invoice->tags as $tag)
                                <span class="tag-badge"
                                    style="background: {{ $tag->color }}20; color: {{ $tag->color }}; border: 1px solid {{ $tag->color }}40;">
                                    {{ $tag->name }}
                                </span>
                            @endforeach
                        </td>
                        <td>{{ $invoice->reference ?? '-' }}</td>
                        <td style="text-align: right; font-weight: 700;" class="text-[#1e293b] dark:text-white">
                            {{ $fmt($invoice->total_amount) }}
                        </td>
                    </tr>

                    @if($isExpanded)
                        <tr>
                            <td colspan="9" style="padding: 0;">
                                <div class="details-wrapper">
                                    <table class="details-table">
                                        <thead>
                                            <tr>
                                                <th style="width: 40px;">No.</th>
                                                <th>Kode/SKU</th>
                                                <th>Nama Produk</th>
                                                <th>Deskripsi</th>
                                                <th style="text-align: right;">Kuantitas</th>
                                                <th>Satuan</th>
                                                <th style="text-align: right;">Harga Satuan</th>
                                                <th style="text-align: right;">Diskon (%)</th>
                                                <th style="text-align: right;">Pajak</th>
                                                <th style="text-align: right;">Jumlah</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($invoice->items as $index => $item)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td style="font-family: monospace;">{{ $item->product->sku ?? '-' }}</td>
                                                    <td style="color: #3b82f6; font-weight: 600;">{{ $item->product->name ?? '-' }}
                                                    </td>
                                                    <td style="color: #64748b;">{{ $item->description }}</td>
                                                    <td style="text-align: right;">{{ number_format($item->quantity, 0) }}</td>
                                                    <td>{{ $item->unit->name ?? '-' }}</td>
                                                    <td style="text-align: right;">{{ $fmt($item->unit_price) }}</td>
                                                    <td style="text-align: right;">{{ $item->discount_percent }}%</td>
                                                    <td style="text-align: right;">{{ $fmt($item->tax_amount) }}</td>
                                                    <td style="text-align: right; font-weight: 700;">{{ $fmt($item->total_price) }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>

                                    <div class="invoice-summary-grid">
                                        <div class="summary-box">
                                            <div class="summary-row">
                                                <span class="summary-label">Sub Total</span>
                                                <span class="summary-value">{{ $fmt($invoice->sub_total) }}</span>
                                            </div>
                                            <div class="summary-row">
                                                <span class="summary-label">Pajak</span>
                                                <span class="summary-value">{{ $fmt($invoice->tax_amount) }}</span>
                                            </div>
                                            <div class="summary-row"
                                                style="border-top: 1px solid #e2e8f0; padding-top: 0.5rem; margin-top: 0.25rem;">
                                                <span class="summary-label"
                                                    style="color: #1e293b; font-weight: 800;">Total</span>
                                                <span class="summary-value"
                                                    style="color: #3b82f6; font-size: 1rem;">{{ $fmt($invoice->total_amount) }}</span>
                                            </div>
                                            <div class="summary-row">
                                                <span class="summary-label">Total Dibayar</span>
                                                <span class="summary-value">{{ $fmt($paid) }}</span>
                                            </div>
                                            <div class="summary-row"
                                                style="border-top: 1px solid #e2e8f0; padding-top: 0.5rem; margin-top: 0.25rem;">
                                                <span class="summary-label">Sisa Tagihan</span>
                                                <span class="summary-value"
                                                    style="color: #ef4444;">{{ $fmt($invoice->balance_due) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Bottom Financial Summaries -->
    <div class="footer-stats-container">
        <!-- Total Halaman Ini -->
        <div class="footer-group">
            <div class="footer-group-title">Total Halaman Ini</div>
            <div class="footer-row">
                <span class="footer-label">Grand Subtotal</span>
                <span class="footer-value">{{ $fmt($pageStats['subtotal']) }}</span>
            </div>
            <div class="footer-row">
                <span class="footer-label">Total Pajak</span>
                <span class="footer-value">{{ $fmt($pageStats['tax']) }}</span>
            </div>
            <div class="footer-row">
                <span class="footer-label">Grand Total</span>
                <span class="footer-value text-blue">{{ $fmt($pageStats['total']) }}</span>
            </div>
            <div class="footer-row">
                <span class="footer-label">Total Pembayaran</span>
                <span class="footer-value">{{ $fmt($pageStats['paid']) }}</span>
            </div>
            <div class="footer-row">
                <span class="footer-label">Total Sisa Tagihan</span>
                <span class="footer-value" style="color: #ef4444;">{{ $fmt($pageStats['balance']) }}</span>
            </div>
        </div>

        <!-- Total Seluruh Halaman -->
        <div class="footer-group">
            <div class="footer-group-title">Total Seluruh Halaman</div>
            <div class="footer-row">
                <span class="footer-label">Grand Subtotal</span>
                <span class="footer-value">{{ $fmt($globalStats['subtotal']) }}</span>
            </div>
            <div class="footer-row">
                <span class="footer-label">Total Pajak</span>
                <span class="footer-value">{{ $fmt($globalStats['tax']) }}</span>
            </div>
            <div class="footer-row">
                <span class="footer-label">Grand Total</span>
                <span class="footer-value text-blue">{{ $fmt($globalStats['total']) }}</span>
            </div>
            <div class="footer-row">
                <span class="footer-label">Total Pembayaran</span>
                <span class="footer-value">{{ $fmt($globalStats['paid']) }}</span>
            </div>
            <div class="footer-row">
                <span class="footer-label">Total Sisa Tagihan</span>
                <span class="footer-value" style="color: #ef4444;">{{ $fmt($globalStats['balance']) }}</span>
            </div>
            <div class="footer-row">
                <span class="footer-label">Pembayaran</span>
                <span class="footer-value">{{ $fmt($globalStats['paid']) }}</span>
            </div>
            <div class="footer-row">
                <span class="footer-label">Pembayaran dengan Piutang</span>
                <span class="footer-value">0</span>
            </div>
            <div class="footer-row">
                <span class="footer-label">Total Pembayaran + Piutang</span>
                <span class="footer-value text-blue">{{ $fmt($globalStats['paid']) }}</span>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <div class="pagination-container">
        <div style="font-size: 0.8125rem; color: #64748b; font-weight: 500;">
            Total {{ $paginator->total() }} data
        </div>
        <div>
            {{ $paginator->links() }}
        </div>
    </div>

</x-filament-panels::page>