<div>
    <x-filament-panels::page>
        @php
            $record = $this->record;
            $supplier = $record->supplier;

            // Aggregated Attachments Collection
            $allAttachments = collect();

            // 1. PO Attachments
            if ($record->attachments) {
                foreach ($record->attachments as $att) {
                    $allAttachments->push([
                        'path' => $att,
                        'label' => 'Pesanan (' . $record->number . ')',
                        'source' => 'order',
                        'date' => $record->created_at
                    ]);
                }
            }

            // 2. Deliveries from this PO
            foreach ($record->deliveries as $delivery) {
                if ($delivery->attachments) {
                    foreach ($delivery->attachments as $att) {
                        $allAttachments->push([
                            'path' => $att,
                            'label' => 'Pengiriman (' . $delivery->number . ')',
                            'source' => 'delivery',
                            'date' => $delivery->created_at
                        ]);
                    }
                }
            }

            // 3. Invoices and Payments from this PO
            $orderPayments = collect();
            foreach ($record->invoices as $invoice) {
                // Invoice Attachments
                if ($invoice->attachments) {
                    foreach ($invoice->attachments as $att) {
                        $allAttachments->push([
                            'path' => $att,
                            'label' => 'Tagihan (' . $invoice->number . ')',
                            'source' => 'invoice',
                            'date' => $invoice->created_at
                        ]);
                    }
                }

                $debt = \App\Models\Debt::where('reference', $invoice->number)->first();
                if ($debt) {
                    foreach ($debt->payments as $payment) {
                        $orderPayments->push([
                            'id' => $payment->id,
                            'account_id' => $payment->account_id,
                            'account_name' => $payment->account?->name ?? 'Unknown',
                            'amount' => $payment->amount,
                            'date' => $payment->date,
                        ]);

                        // Payment Attachments
                        if ($payment->attachments) {
                            foreach ($payment->attachments as $att) {
                                $allAttachments->push([
                                    'path' => $att,
                                    'label' => 'Pembayaran (' . ($payment->number ?? 'N/A') . ')',
                                    'source' => 'payment',
                                    'date' => $payment->created_at
                                ]);
                            }
                        }
                    }
                }
            }

            // Consolidate: Unique by path and sort by date
            $allAttachments = $allAttachments->unique('path')->sortBy('date');

            $totalPayments = $orderPayments->sum('amount');
            $downPayment = floatval($record->down_payment ?? 0);
            $balanceDue = max(0, ($record->total_amount ?? 0) - $downPayment - $totalPayments);
        @endphp

        <style>
            .po-card {
                background: white;
                border-radius: 12px;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                border: 1px solid #e5e7eb;
            }

            .dark .po-card {
                background: #1f2937;
                border-color: #374151;
            }

            .po-header {
                padding: 20px 24px;
                border-bottom: 1px solid #e5e7eb;
            }

            .dark .po-header {
                border-color: #374151;
            }

            .po-info-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 32px;
                padding: 24px;
            }

            .po-label {
                font-size: 12px;
                color: #6b7280;
                margin-bottom: 4px;
                text-transform: uppercase;
                letter-spacing: 0.05em;
            }

            .dark .po-label {
                color: #9ca3af;
            }

            .po-value {
                font-size: 14px;
                font-weight: 500;
                color: #111827;
            }

            .dark .po-value {
                color: #f9fafb;
            }

            .po-value-link {
                color: #1d4ed8;
                text-decoration: none;
            }

            .po-value-link:hover {
                text-decoration: underline;
            }

            .po-table {
                width: 100%;
                border-collapse: collapse;
            }

            .po-table thead {
                background: #f9fafb;
            }

            .dark .po-table thead {
                background: #111827;
            }

            .po-table th {
                padding: 12px 16px;
                font-size: 12px;
                font-weight: 600;
                color: #6b7280;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                border-bottom: 1px solid #e5e7eb;
            }

            .dark .po-table th {
                color: #9ca3af;
                border-color: #374151;
            }

            .po-table td {
                padding: 12px 16px;
                font-size: 14px;
                border-bottom: 1px solid #f3f4f6;
            }

            .dark .po-table td {
                border-color: #374151;
            }

            .po-table tfoot td {
                background: #f9fafb;
                font-weight: 600;
            }

            .dark .po-table tfoot td {
                background: #111827;
            }

            .po-summary {
                padding: 24px;
                border-top: 1px solid #e5e7eb;
            }

            .dark .po-summary {
                border-color: #374151;
            }

            .po-summary-row {
                display: flex;
                justify-content: space-between;
                padding: 8px 0;
            }

            .po-summary-total {
                border-top: 2px solid #e5e7eb;
                margin-top: 8px;
                padding-top: 12px;
            }

            .dark .po-summary-total {
                border-color: #374151;
            }

            .po-badge {
                display: inline-flex;
                align-items: center;
                padding: 4px 12px;
                border-radius: 9999px;
                font-size: 12px;
                font-weight: 600;
            }

            .po-badge-draft {
                background: #f3f4f6;
                color: #4b5563;
            }

            .po-badge-shipped {
                background: #dbeafe;
                color: #1e40af;
            }

            .po-badge-confirmed {
                background: #fef2f2;
                color: #dc2626;
            }

            .po-badge-success,
            .po-badge-paid {
                background: #d1fae5;
                color: #059669;
            }

            .po-badge-partial,
            .po-badge-ordered {
                background: #fef9c3;
                color: #854d0e;
            }

            .po-badge-cancelled,
            .po-badge-void {
                background: #fef2f2;
                color: #dc2626;
            }

            .po-tag {
                display: inline-flex;
                align-items: center;
                padding: 2px 8px;
                border-radius: 4px;
                font-size: 12px;
                font-weight: 500;
                background: #dbeafe;
                color: #1e40af;
                margin-right: 4px;
            }

            .dark .po-tag {
                background: #1e3a8a;
                color: #dbeafe;
            }

            .po-contact-info {
                display: flex;
                align-items: center;
                gap: 6px;
                font-size: 13px;
                color: #6b7280;
                margin-top: 4px;
            }

            .dark .po-contact-info {
                color: #9ca3af;
            }

            .po-contact-info svg {
                width: 14px;
                height: 14px;
                flex-shrink: 0;
            }

            @media print {
                @page {
                    size: A4;
                    margin: 15mm;
                }

                /* Visibility Toggle Strategy */
                body * {
                    visibility: hidden;
                }

                .print-area,
                .print-area * {
                    visibility: visible;
                }

                .print-area {
                    position: absolute;
                    left: 0;
                    top: 0;
                    width: 100%;
                    background: white !important;
                    margin: 0;
                    padding: 0;
                }

                /* Global shadow killer */
                * {
                    box-shadow: none !important;
                    text-shadow: none !important;
                    filter: none !important;
                    backdrop-filter: none !important;
                }

                .fi-sidebar,
                .fi-topbar,
                .fi-header,
                .fi-footer,
                .no-print,
                button,
                .fi-btn,
                .audit-log-section,
                .fi-modal-window,
                .fi-modal-close-overlay,
                .fi-modal-trigger,
                .fi-modal,
                aside,
                header,
                nav,
                .fi-modal-overlay,
                .fi-backdrop {
                    display: none !important;
                }

                html,
                body,
                .fi-main,
                .fi-page,
                .fi-main-ctn,
                .fi-section,
                .fi-header,
                .fi-topbar,
                .po-card {
                    background-color: #ffffff !important;
                    background: #ffffff !important;
                    color: #111827 !important;
                    box-shadow: none !important;
                    border: none !important;
                }

                /* Override Dark Mode */
                .dark,
                html.dark,
                body.dark,
                .dark .fi-main,
                .dark .fi-page,
                .dark .po-card {
                    background-color: #ffffff !important;
                    background: white !important;
                    color: #111827 !important;
                    box-shadow: none !important;
                }

                body {
                    padding: 0 !important;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                    font-family: 'Inter', sans-serif;
                }

                /* Visibility Toggle Strategy */
                body * {
                    visibility: hidden;
                }

                .print-area,
                .print-area * {
                    visibility: visible;
                }

                .print-area {
                    position: absolute;
                    left: 0;
                    top: 0;
                    width: 100%;
                    background: white !important;
                }

                .fi-sidebar,
                .fi-topbar,
                .fi-header,
                .fi-footer,
                .no-print,
                button,
                .fi-btn,
                .audit-log-section,
                .fi-modal-window,
                .fi-modal-close-overlay,
                .fi-modal-trigger,
                .fi-modal,
                aside,
                header,
                nav,
                .fi-modal-overlay,
                .fi-backdrop {
                    display: none !important;
                }

                /* Ensure content containers are clean */
                .fi-section,
                .fi-card,
                .po-card {
                    background: white !important;
                    box-shadow: none !important;
                    border: none !important;
                }

                .related-transactions-section {
                    page-break-before: always;
                    margin-top: 0 !important;
                }

                .fi-main,
                .fi-main-ctn {
                    padding: 0 !important;
                    margin: 0 !important;
                }

                .po-card {
                    box-shadow: none !important;
                    border: none !important;
                    border-radius: 0 !important;
                    padding: 0 !important;
                    background: white !important;
                    break-inside: avoid;
                }

                * {
                    box-shadow: none !important;
                    text-shadow: none !important;
                    filter: none !important;
                    backdrop-filter: none !important;
                }

                .po-label {
                    font-size: 9px !important;
                    text-transform: uppercase;
                    letter-spacing: 0.05em;
                    color: #6b7280 !important;
                    background: transparent !important;
                }

                .po-value {
                    font-size: 10px !important;
                    color: #111827 !important;
                    background: transparent !important;
                }

                h3 {
                    font-size: 12px !important;
                    margin-bottom: 8px !important;
                    background: transparent !important;
                }

                .po-table th {
                    background-color: #f9fafb !important;
                    font-size: 9px !important;
                    text-transform: uppercase;
                    border-bottom: 2px solid #e5e7eb !important;
                    padding: 6px 10px !important;
                    color: #6b7280 !important;
                }

                .po-table td {
                    padding: 6px 10px !important;
                    font-size: 10px !important;
                    border-bottom: 1px solid #f3f4f6 !important;
                    background-color: #ffffff !important;
                    color: #111827 !important;
                }

                /* Global shadow killer */
                * {
                    box-shadow: none !important;
                    text-shadow: none !important;
                }
            }

            .po-summary-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 32px;
            }

            @media (max-width: 768px) {
                .po-summary-grid {
                    grid-template-columns: 1fr;
                    gap: 24px;
                }
            }

            /* Mobile Responsiveness */
            @media (max-width: 640px) {
                .po-info-grid {
                    grid-template-columns: 1fr !important;
                    gap: 24px !important;
                }

                .po-header {
                    padding: 16px !important;
                }

                .po-summary {
                    padding: 16px !important;
                }

                .po-summary-grid {
                    grid-template-columns: 1fr;
                    gap: 24px;
                }

                .po-table th,
                .po-table td {
                    white-space: nowrap;
                }

                .po-summary-container {
                    width: 100% !important;
                }

                .po-header-actions {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 12px;
                }

                .po-header-actions div:last-child {
                    display: flex;
                    flex-direction: column;
                    width: 100%;
                    gap: 8px !important;
                }

                .po-header-actions div:last-child>* {
                    width: 100% !important;
                    justify-content: center !important;
                }
            }
        </style>

        {{-- Header Actions --}}
        <div class="no-print po-header-actions"
            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                @php
                    $actualBalance = ($record->total_amount ?? 0) - ($record->paid_amount ?? 0);
                    $isFinishedState = $actualBalance <= 0.5 && ($record->total_amount > 0);

                    $displayStatus = $record->status;
                    if ($isFinishedState && !in_array($record->status, ['cancelled', 'void', 'draft'])) {
                        $displayStatus = 'closed';
                    }

                    $statusLabel = match ($displayStatus) {
                        'draft' => 'Draf',
                        'approved' => 'Dipesan',
                        'ordered' => 'Dipesan',
                        'received' => 'Diterima',
                        'billed' => 'Tagihan',
                        'partial_billed' => 'Tagihan Sebagian',
                        'paid' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                        'void' => 'Batal',
                        'closed' => 'Selesai',
                        default => ucfirst(str_replace('_', ' ', $record->status)),
                    };

                    $statusColor = match ($displayStatus) {
                        'draft' => 'draft',
                        'approved' => 'ordered',
                        'ordered' => 'ordered',
                        'received' => 'received',
                        'billed' => 'billed',
                        'partial_billed' => 'partial_billed',
                        'paid' => 'paid',
                        'cancelled' => 'cancelled',
                        'void' => 'void',
                        'closed' => 'closed',
                        default => 'gray',
                    };
                @endphp
                <span class="po-badge po-badge-{{ $statusColor }}">
                    {{ $statusLabel }}
                </span>
            </div>
            <div style="display: flex; gap: 8px;">
                <x-filament::button tag="a" href="{{ \App\Filament\Resources\PurchaseOrderResource::getUrl('index') }}"
                    color="gray" size="sm">
                    Kembali
                </x-filament::button>
                <x-filament::button color="gray" size="sm" icon="heroicon-o-share">
                    Bagikan
                </x-filament::button>
                <x-filament::button color="gray" size="sm" icon="heroicon-o-printer" onclick="window.print()">
                    Print
                </x-filament::button>
                @if($record->status === 'draft')
                    {{ $this->approve }}
                    {{ $this->reject }}
                @endif
                @if(!in_array($displayStatus, ['draft', 'cancelled', 'closed', 'completed', 'paid', 'void']))
                    <x-filament::button color="primary" size="sm"
                        href="{{ \App\Filament\Resources\PurchaseInvoiceResource::getUrl('create', ['purchase_order_id' => $record->id]) }}"
                        tag="a">
                        + Buat Tagihan
                    </x-filament::button>
                    <x-filament::button color="success" size="sm"
                        href="{{ \App\Filament\Resources\PurchaseDeliveryResource::getUrl('create', ['purchase_order_id' => $record->id]) }}"
                        tag="a">
                        + Buat Pengiriman
                    </x-filament::button>
                @endif
            </div>
        </div>

        {{-- Main Card --}}
        <div class="print-area">
            <div class="po-card">
                {{-- Info Section --}}
                <div class="po-info-grid">
                    {{-- Left: Vendor Info --}}
                    <div>
                        <div class="po-label">Vendor</div>
                        <div class="po-value" style="margin-bottom: 8px;">
                            <a href="{{ $supplier ? \App\Filament\Resources\ContactResource::getUrl('view', ['record' => $supplier->id]) : '#' }}"
                                class="po-value-link" style="font-size: 16px;">
                                {{ $supplier?->name ?? '-' }}
                            </a>
                        </div>
                        @if($supplier)
                            @if($supplier->company)
                                <div class="po-contact-info">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" />
                                    </svg>
                                    {{ $supplier->company }}
                                </div>
                            @endif
                            @if($supplier->phone)
                                <div class="po-contact-info">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                                    </svg>
                                    {{ $supplier->phone }}
                                </div>
                            @endif
                            @if($supplier->address)
                                <div class="po-contact-info">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                                    </svg>
                                    {{ $supplier->address }}
                                </div>
                            @endif
                        @endif
                    </div>

                    {{-- Right: Order Details --}}
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div>
                            <div class="po-label">Nomor</div>
                            <div class="po-value">{{ $record->number }}</div>
                        </div>
                        <div>
                            <div class="po-label">Tgl Transaksi</div>
                            <div class="po-value">
                                {{ $record->date ? \Carbon\Carbon::parse($record->date)->format('d/m/Y') : '-' }}
                            </div>
                        </div>
                        <div>
                            <div class="po-label">Tgl Jatuh Tempo</div>
                            <div class="po-value">
                                {{ $record->due_date ? \Carbon\Carbon::parse($record->due_date)->format('d/m/Y') : '-' }}
                            </div>
                        </div>
                        <div>
                            <div class="po-label">Gudang</div>
                            <div class="po-value">
                                <a href="#" class="po-value-link">{{ $record->warehouse?->name ?? 'Unassigned' }}</a>
                            </div>
                        </div>
                        <div>
                            <div class="po-label">Referensi</div>
                            <div class="po-value">{{ $record->reference ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="po-label">Tag</div>
                            <div style="margin-top: 4px;">
                                @forelse($record->tags as $tag)
                                    <span class="po-tag">{{ $tag->name }}</span>
                                @empty
                                    <span class="po-value">-</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Product Table --}}
                <div style="overflow-x: auto;">
                    <table class="po-table">
                        <thead>
                            <tr>
                                <th style="text-align: left;">Produk</th>
                                <th style="text-align: left;">Deskripsi</th>
                                <th style="text-align: center;">Kuantitas</th>
                                <th style="text-align: center;">Satuan</th>
                                <th style="text-align: center;">Diskon</th>
                                <th style="text-align: right;">Harga</th>
                                <th style="text-align: center;">Pajak</th>
                                <th style="text-align: right;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $totalQty = 0; @endphp
                            @foreach($record->items as $item)
                                @php $totalQty += $item->quantity; @endphp
                                <tr>
                                    <td>
                                        <a href="{{ $item->product ? \App\Filament\Resources\ProductResource::getUrl('view', ['record' => $item->product->id]) : '#' }}"
                                            class="po-value-link">
                                            {{ $item->product?->sku ?? '' }} - {{ $item->product?->name ?? '-' }}
                                        </a>
                                    </td>
                                    <td style="color: #6b7280;">{{ $item->description ?? '-' }}</td>
                                    <td style="text-align: center;">{{ number_format($item->quantity, 0, ',', '.') }}</td>
                                    <td style="text-align: center;">{{ $item->unit?->name ?? '-' }}</td>
                                    <td style="text-align: center;">{{ $item->discount_percent ?? 0 }}%</td>
                                    <td style="text-align: right;">{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                    <td style="text-align: center;">{{ $item->tax?->name ?? '-' }}</td>
                                    <td style="text-align: right; font-weight: 600;">
                                        {{ number_format($item->total_price, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2" style="text-align: right;">Total Kuantitas</td>
                                <td style="text-align: center;">{{ number_format($totalQty, 0, ',', '.') }}</td>
                                <td colspan="5"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {{-- Summary Section --}}
                <div class="po-summary">
                    <div class="po-summary-grid">
                        {{-- Notes (Left) --}}
                        <div>
                            @if($record->notes)
                                <div class="po-label" style="margin-bottom: 8px;">Catatan</div>
                                <div class="po-value" style="color: #6b7280; white-space: pre-wrap;">{{ $record->notes }}
                                </div>
                            @endif
                        </div>

                        {{-- Totals (Right) --}}
                        <div style="display: flex; justify-content: flex-end;">
                            <div class="po-summary-container" style="width: 320px;">
                                <div class="po-summary-row">
                                    <span style="color: #6b7280;">Sub total</span>
                                    <span
                                        class="po-value">{{ number_format($record->sub_total ?? 0, 0, ',', '.') }}</span>
                                </div>
                                @if(($record->items->sum('tax_amount') ?? 0) > 0)
                                    <div class="po-summary-row">
                                        <span style="color: #6b7280;">PPN</span>
                                        <span
                                            class="po-value">{{ number_format($record->items->sum('tax_amount'), 0, ',', '.') }}</span>
                                    </div>
                                @endif
                                @if($record->discount_amount > 0)
                                    <div class="po-summary-row">
                                        <span style="color: #6b7280;">Diskon</span>
                                        <span class="po-value"
                                            style="color: #dc2626;">-{{ number_format($record->discount_amount, 0, ',', '.') }}</span>
                                    </div>
                                @endif
                                @if($record->shipping_cost > 0)
                                    <div class="po-summary-row">
                                        <span style="color: #6b7280;">Biaya pengiriman</span>
                                        <span
                                            class="po-value">{{ number_format($record->shipping_cost, 0, ',', '.') }}</span>
                                    </div>
                                @endif
                                @if($record->other_cost > 0)
                                    <div class="po-summary-row">
                                        <span style="color: #6b7280;">Biaya lainnya</span>
                                        <span class="po-value">{{ number_format($record->other_cost, 0, ',', '.') }}</span>
                                    </div>
                                @endif
                                <div class="po-summary-row po-summary-total">
                                    <span style="font-weight: 600; font-size: 15px;">Total</span>
                                    <span
                                        style="font-weight: 700; font-size: 16px;">{{ number_format($record->total_amount ?? 0, 0, ',', '.') }}</span>
                                </div>
                                @if($downPayment > 0)
                                    <div class="po-summary-row">
                                        <span style="font-weight: 500; font-size: 15px; color: #ea580c;">Pembayaran
                                            Pemesanan</span>
                                        <span
                                            style="font-weight: 600; font-size: 15px; color: #059669;">{{ number_format($downPayment, 0, ',', '.') }}</span>
                                    </div>
                                @endif
                                @foreach($orderPayments as $payment)
                                    <div class="po-summary-row">
                                        <a href="{{ url('admin/accounts/' . $payment['account_id'] . '/transaction/' . $payment['id']) }}"
                                            style="font-weight: 500; font-size: 15px; color: #2563eb; text-decoration: none;">
                                            Pembayaran {{ $payment['account_name'] }}
                                        </a>
                                        <span
                                            style="font-weight: 600; font-size: 15px; color: #059669;">{{ number_format($payment['amount'], 0, ',', '.') }}</span>
                                    </div>
                                @endforeach
                                <div class="po-summary-row po-summary-total">
                                    <span style="font-weight: 700; font-size: 18px;">Sisa Tagihan</span>
                                    <span
                                        style="font-weight: 700; font-size: 18px; color: #1d4ed8;">{{ number_format($balanceDue, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Attachments Section --}}
                <div class="no-print" style="border-top: 1px solid #e5e7eb; padding: 24px;">
                    <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 16px; color: #374151;">Lampiran</h3>
                    @if($allAttachments->isNotEmpty())
                        <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                            @foreach ($allAttachments as $attachment)
                                <div style="width: 200px;">
                                    <div style="margin-bottom: 8px;">
                                        @php
                                            $bColor = match ($attachment['source']) {
                                                'invoice' => 'confirmed',
                                                'order' => 'partial',
                                                'delivery' => 'shipped',
                                                'payment' => 'success',
                                                default => 'gray',
                                            };
                                        @endphp
                                        <span class="po-badge po-badge-{{ $bColor }}" style="font-size: 11px;">
                                            {{ $attachment['label'] }}
                                        </span>
                                    </div>
                                    <a href="{{ asset('storage/' . $attachment['path']) }}" target="_blank">
                                        <img src="{{ asset('storage/' . $attachment['path']) }}"
                                            style="width: 100%; height: 150px; object-fit: cover; border-radius: 8px; border: 1px solid #e5e7eb;">
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div style="color: #9ca3af; font-style: italic;">Tidak ada lampiran.</div>
                    @endif
                </div>
            </div>

            {{-- Related Transactions Section --}}
            @php
                $relatedTransactions = collect();

                // 1. Deliveries
                // 1. Deliveries
                foreach ($record->deliveries as $delivery) {
                    // For delivery, total is just shipping cost
                    $deliveryTotal = $delivery->shipping_cost ?? 0;

                    $relatedTransactions->push([
                        'type' => 'PENGIRIMAN PEMBELIAN',
                        'number' => $delivery->number,
                        'date' => $delivery->date,
                        'status' => $delivery->status,
                        'shipping_cost' => $delivery->shipping_cost ?? 0,
                        'total' => $deliveryTotal,
                        'url' => \App\Filament\Resources\PurchaseDeliveryResource::getUrl('view', ['record' => $delivery->id])
                    ]);
                }

                // 2. Invoices
                foreach ($record->invoices as $invoice) {
                    $relatedTransactions->push([
                        'type' => 'TAGIHAN PEMBELIAN',
                        'number' => $invoice->number,
                        'date' => $invoice->date,
                        'status' => $invoice->payment_status ?? $invoice->status,
                        'shipping_cost' => $invoice->shipping_cost ?? 0,
                        'total' => $invoice->total_amount,
                        'url' => \App\Filament\Resources\PurchaseInvoiceResource::getUrl('view', ['record' => $invoice->id])
                    ]);

                    // 3. Payments for these Invoices
                    $debt = \App\Models\Debt::where('reference', $invoice->number)->first();
                    if ($debt) {
                        foreach ($debt->payments as $payment) {
                            $relatedTransactions->push([
                                'type' => 'PEMBAYARAN',
                                'number' => $payment->number ?? ('Pembayaran on ' . ($payment->date ? \Carbon\Carbon::parse($payment->date)->format('d/m/Y') : 'N/A')),
                                'date' => $payment->date,
                                'status' => 'success',
                                'shipping_cost' => 0,
                                'total' => $payment->amount,
                                'url' => ($journalEntry = \App\Models\JournalEntry::where('reference_number', $payment->number)->first())
                                    ? url('admin/kas-bank/transaction/' . $journalEntry->id . '/detail')
                                    : '#'
                            ]);
                        }
                    }
                }

                // 4. Down Payment
                if ($record->down_payment > 0) {
                    $relatedTransactions->push([
                        'type' => 'PEMBAYARAN DP PEMESANAN PEMBELIAN',
                        'number' => 'DP/' . $record->number,
                        'date' => $record->date,
                        'status' => 'success',
                        'shipping_cost' => 0,
                        'total' => $record->down_payment,
                        'url' => '#'
                    ]);
                }

                $relatedTransactions = $relatedTransactions->sortByDesc('date');
            @endphp

            @if($relatedTransactions->isNotEmpty())
                <div class="related-transactions-section" style="margin-top: 32px;">
                    <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 16px; color: #374151;">Transaksi Terkait
                    </h3>
                    <div class="po-card" style="overflow-x: auto;">
                        <table class="po-table">
                            <thead>
                                <tr>
                                    <th style="text-align: left;">Tipe Transaksi</th>
                                    <th style="text-align: left;">Nomor</th>
                                    <th style="text-align: left;">Tanggal</th>
                                    <th style="text-align: left;">Status</th>
                                    <th style="text-align: right;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($relatedTransactions as $trans)
                                    <tr>
                                        <td class="po-value" style="font-weight: 600;">
                                            @php
                                                $pType = match ($trans['type']) {
                                                    'PENGIRIMAN PEMBELIAN' => ['label' => 'Pengiriman', 'color' => 'shipped'],
                                                    'TAGIHAN PEMBELIAN' => ['label' => 'Tagihan', 'color' => 'confirmed'],
                                                    'PEMBAYARAN', 'PEMBAYARAN DP PEMESANAN PEMBELIAN' => ['label' => 'Pembayaran', 'color' => 'success'],
                                                    default => ['label' => $trans['type'], 'color' => 'partial'],
                                                };
                                            @endphp
                                            <span class="po-badge po-badge-{{ $pType['color'] }}">
                                                {{ $pType['label'] }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($trans['url'] !== '#')
                                                <a href="{{ $trans['url'] }}" class="po-value-link">
                                                    {{ $trans['number'] }}
                                                </a>
                                            @else
                                                <span class="po-value">{{ $trans['number'] }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $trans['date'] ? \Carbon\Carbon::parse($trans['date'])->format('d/m/Y') : '-' }}
                                        </td>
                                        <td>
                                            @php
                                                $tLabel = match ($trans['status']) {
                                                    'draft' => 'Draf',
                                                    'ordered' => 'Dipesan',
                                                    'received' => 'Diterima',
                                                    'billed' => 'Tagihan',
                                                    'partial_billed' => 'Tagihan Sebagian',
                                                    'paid', 'lunas' => 'Lunas',
                                                    'success' => 'Berhasil',
                                                    'closed' => 'Selesai',
                                                    'cancelled' => 'Dibatalkan',
                                                    'unpaid', 'posted' => 'Belum Lunas',
                                                    default => ucfirst(str_replace('_', ' ', $trans['status'])),
                                                };
                                                $tColor = match ($trans['status']) {
                                                    'draft' => 'draft',
                                                    'ordered' => 'ordered',
                                                    'received', 'paid', 'lunas', 'closed', 'success' => 'success',
                                                    'billed', 'unpaid', 'partial_billed' => 'confirmed',
                                                    'cancelled' => 'cancelled',
                                                    default => 'gray',
                                                };
                                            @endphp
                                            <span class="po-badge po-badge-{{ $tColor }}">
                                                {{ $tLabel }}
                                            </span>
                                        </td>
                                        <td style="text-align: right; font-weight: 600;">
                                            Rp {{ number_format($trans['total'] ?? 0, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

        </div>

        {{-- Audit Log --}}
        <div class="audit-log-section" style="margin-top: 32px; padding-top: 24px; border-top: 1px solid #e5e7eb;">
            <div style="font-size: 14px; font-weight: 500; margin-bottom: 8px;">
                <a href="#" wire:click.prevent="mountAction('auditLog')" class="po-value-link"
                    style="text-decoration: none;">
                    Pantau log perubahan data
                </a>
            </div>
            <div style="display: flex; align-items: center; gap: 6px; font-size: 13px; color: #6b7280;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" style="width: 14px; height: 14px;">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Terakhir diubah oleh {{ $record->updatedBy?->name ?? 'system' }} pada
                {{ $record->updated_at?->format('d M Y H:i') ?? '-' }}
            </div>
        </div>

    </x-filament-panels::page>