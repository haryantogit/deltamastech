<div>
    <x-filament-panels::page>
        @php
            $record = $this->record;
            $supplier = $record->supplier;
            $debt = \App\Models\Debt::where('reference', $record->number)->first();

            // Aggregated Attachments Collection
            $allAttachments = collect();

            // 1. Current Invoice Attachments
            if ($record->attachments) {
                foreach ($record->attachments as $att) {
                    $allAttachments->push([
                        'path' => $att,
                        'label' => 'Tagihan (' . $record->number . ')',
                        'source' => 'invoice',
                        'date' => $record->created_at
                    ]);
                }
            }

            // 2. Discover the Transaction Family (finding root PO)
            $rootOrder = $record->purchaseOrder ??
                ($record->purchase_order_id ? \App\Models\PurchaseOrder::find($record->purchase_order_id) : null) ??
                ($record->reference ? \App\Models\PurchaseOrder::where('number', $record->reference)->first() : null);

            if ($rootOrder) {
                // PO Attachments
                if ($rootOrder->attachments) {
                    foreach ($rootOrder->attachments as $att) {
                        $allAttachments->push([
                            'path' => $att,
                            'label' => 'Pesanan (' . $rootOrder->number . ')',
                            'source' => 'order',
                            'date' => $rootOrder->created_at
                        ]);
                    }
                }

                // 3. Deliveries from this PO
                foreach ($rootOrder->deliveries as $delivery) {
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

                // 4. All Invoices from this PO
                foreach ($rootOrder->invoices as $familyInvoice) {
                    if ($familyInvoice->id !== $record->id && $familyInvoice->attachments) {
                        foreach ($familyInvoice->attachments as $att) {
                            $allAttachments->push([
                                'path' => $att,
                                'label' => 'Tagihan (' . $familyInvoice->number . ')',
                                'source' => 'invoice',
                                'date' => $familyInvoice->created_at
                            ]);
                        }
                    }

                    // 5. Payments for invoices in the family
                    $fDebt = \App\Models\Debt::where('reference', $familyInvoice->number)->first();
                    if ($fDebt) {
                        foreach ($fDebt->payments as $fPayment) {
                            if ($fPayment->attachments) {
                                foreach ($fPayment->attachments as $att) {
                                    $allAttachments->push([
                                        'path' => $att,
                                        'label' => 'Pembayaran (' . ($fPayment->number ?? 'N/A') . ')',
                                        'source' => 'payment',
                                        'date' => $fPayment->created_at
                                    ]);
                                }
                            }
                        }
                    }
                }
            } else {
                // Fallback: If no root order found, still check payments for THIS invoice
                if ($debt) {
                    foreach ($debt->payments as $payment) {
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

            // --- Existing Calculations ---
            $invoicePayments = collect();
            if ($debt) {
                foreach ($debt->payments as $payment) {
                    $journalEntry = \App\Models\JournalEntry::where('reference_number', $payment->number)->first();
                    $invoicePayments->push([
                        'id' => $payment->id,
                        'account_id' => $payment->account_id,
                        'account_name' => $payment->account?->name ?? 'Unknown',
                        'amount' => $payment->amount,
                        'date' => $payment->date,
                        'journal_entry_id' => $journalEntry ? $journalEntry->id : null,
                    ]);
                }
            }
            $debtPaymentsTotal = $invoicePayments->sum('amount');

            $invoiceDownPayment = floatval($record->down_payment ?? 0);
            $orderDownPayment = $rootOrder ? floatval($rootOrder->down_payment ?? 0) : 0;
            $effectiveDownPayment = ($invoiceDownPayment > 0) ? $invoiceDownPayment : $orderDownPayment;

            $totalPaid = $debtPaymentsTotal + $effectiveDownPayment;
            $balanceDue = max(0, ($record->total_amount ?? 0) - $totalPaid - ($record->withholding_amount ?? 0));
            if (($record->payment_status ?? '') === 'paid' || ($record->status ?? '') === 'paid') {
                $balanceDue = 0;
            }
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

            .po-badge-submitted,
            .po-badge-approved,
            .po-badge-pending {
                background: #dbeafe;
                color: #1d4ed8;
            }

            .po-badge-paid,
            .po-badge-success,
            .po-badge-delivered {
                background: #d1fae5;
                color: #065f46;
            }

            .po-badge-ordered {
                background: #fef9c3;
                color: #854d0e;
            }

            .po-badge-received,
            .po-badge-billed {
                background: #e0f2fe;
                color: #0369a1;
            }

            .po-badge-shipped {
                background: #dbeafe;
                color: #1e40af;
            }

            .po-badge-confirmed {
                background: #fef2f2;
                color: #dc2626;
            }

            .po-badge-partial {
                background: #fef9c3;
                color: #854d0e;
            }

            .po-badge-cancelled,
            .po-badge-void,
            .po-badge-unpaid,
            .po-badge-overdue {
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

                html,
                body,
                .fi-main,
                .fi-page,
                .fi-main-ctn {
                    background: white !important;
                    color: #111827;
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
                .fi-modal {
                    display: none !important;
                }

                .po-label {
                    font-size: 9px !important;
                    text-transform: uppercase;
                    letter-spacing: 0.05em;
                    color: #6b7280 !important;
                }

                .po-value {
                    font-size: 10px !important;
                }

                h3 {
                    font-size: 12px !important;
                    margin-bottom: 8px !important;
                }

                .po-table th {
                    background-color: #f9fafb !important;
                    font-size: 9px !important;
                    text-transform: uppercase;
                    border-bottom: 2px solid #e5e7eb !important;
                    padding: 6px 10px !important;
                }

                .po-table td {
                    padding: 6px 10px !important;
                    font-size: 10px !important;
                    border-bottom: 1px solid #f3f4f6 !important;
                }
            }

            /* Mobile Responsive Styles */
            .po-grid-2 {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 16px;
            }

            .po-summary-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 32px;
            }

            .po-table-wrapper {
                width: 100%;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            @media (max-width: 768px) {
                .po-info-grid {
                    grid-template-columns: 1fr;
                    gap: 24px;
                    padding: 16px;
                }

                .po-grid-2 {
                    grid-template-columns: 1fr;
                    gap: 16px;
                }

                .po-summary-grid {
                    grid-template-columns: 1fr;
                    gap: 24px;
                }

                .po-card {
                    border-radius: 8px;
                }

                .po-header {
                    padding: 16px;
                }

                .po-summary {
                    padding: 16px;
                }

                .po-header-actions {
                    flex-direction: column;
                    align-items: flex-start !important;
                    gap: 12px;
                }
            }

            /* Account Select Specific Styles */
            .account-select-item {
                padding: 8px 12px;
                cursor: pointer;
                font-size: 13.5px;
                border-radius: 6px;
                transition: all 0.1s;
                display: flex !important;
                flex-direction: row !important;
                align-items: center !important;
                justify-content: space-between !important;
                min-height: 38px;
                color: #334155;
                flex-wrap: nowrap !important;
            }

            .account-select-item:hover {
                background-color: #f8fafc;
            }

            .account-select-item.is-selected {
                background-color: #eff6ff;
                color: #1d4ed8;
                font-weight: 500;
            }

            .account-search-input:focus {
                border-color: #3b82f6 !important;
                box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2) !important;
            }
        </style>

        {{-- Header Actions --}}
        <div class="no-print po-header-actions"
            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                @php
                    // Map status to Indonesian and colors
                    $statusLabel = match ($record->payment_status ?? $record->status) {
                        'draft' => 'Draf',
                        'pending' => 'Menunggu',
                        'approved' => 'Disetujui',
                        'unpaid' => 'Belum Lunas',
                        'partial' => 'Sebagian',
                        'paid' => 'Lunas',
                        'overdue' => 'Jatuh Tempo',
                        'cancelled' => 'Dibatalkan',
                        'posted' => 'Belum Lunas',
                        'void' => 'Batal',
                        default => ucfirst(str_replace('_', ' ', $record->payment_status ?? $record->status)),
                    };

                    $statusColor = match ($record->payment_status ?? $record->status) {
                        'draft' => 'draft',
                        'pending' => 'pending',
                        'approved' => 'approved',
                        'unpaid' => 'unpaid',
                        'partial' => 'partial',
                        'paid' => 'paid',
                        'overdue' => 'overdue',
                        'cancelled' => 'cancelled',
                        'posted' => 'unpaid',
                        'void' => 'void',
                        default => 'gray',
                    };
                @endphp
                <span class="po-badge po-badge-{{ $statusColor }}">
                    {{ $statusLabel }}
                </span>
            </div>
            <div style="display: flex; gap: 8px;">
                <x-filament::button tag="a"
                    href="{{ \App\Filament\Resources\PurchaseInvoiceResource::getUrl('index') }}" color="gray"
                    size="sm">
                    Kembali
                </x-filament::button>
                <x-filament::button color="gray" size="sm" icon="heroicon-o-share">
                    Bagikan
                </x-filament::button>
                <x-filament::button color="gray" size="sm" icon="heroicon-o-printer" onclick="window.print()">
                    Print
                </x-filament::button>
            </div>
        </div>

        {{-- Main Card --}}
        <div class="print-area">
            <div class="po-card">
                {{-- Info Section --}}
                <div class="po-info-grid">
                    {{-- Left: Vendor Info --}}
                    <div>
                        <div class="po-label">Pemasok</div>
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

                    {{-- Right: Invoice Details --}}
                    <div class="po-grid-2">
                        <div>
                            <div class="po-label">No. Tagihan</div>
                            <div class="po-value">{{ $record->number }}</div>
                        </div>
                        <div>
                            <div class="po-label">Tanggal</div>
                            <div class="po-value">{{ $record->date?->format('d/m/Y') ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="po-label">Status</div>
                            @php
                                $payStatusLabel = match ($record->payment_status) {
                                    'unpaid' => 'Belum Lunas',
                                    'partial' => 'Sebagian',
                                    'paid' => 'Lunas',
                                    'overdue' => 'Jatuh Tempo',
                                    'posted' => 'Belum Lunas',
                                    default => ucfirst($record->payment_status ?? $record->status ?? 'Draft'),
                                };

                                $payStatusColor = match ($record->payment_status ?? $record->status) {
                                    'paid' => 'paid',
                                    'partial' => 'partial',
                                    'unpaid', 'overdue', 'posted' => 'unpaid',
                                    default => 'draft',
                                };
                            @endphp
                            <span class="po-badge po-badge-{{ $payStatusColor }}"
                                style="padding: 2px 8px; font-size: 11px;">
                                {{ $payStatusLabel }}
                            </span>
                        </div>
                        <div>
                            <div class="po-label">Jatuh Tempo</div>
                            <div class="po-value">{{ $record->due_date?->format('d/m/Y') ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="po-label">Termin</div>
                            <div class="po-value">{{ $record->paymentTerm?->name ?? 'None' }}</div>
                        </div>
                        <div>
                            <div class="po-label">Gudang</div>
                            <div class="po-value">
                                <a href="#" class="po-value-link">{{ $record->warehouse?->name ?? 'Unassigned' }}</a>
                            </div>
                        </div>
                        <div>
                            <div class="po-label">Nomor Pemesanan</div>
                            <div class="po-value">
                                @if($record->purchaseOrder)
                                    <a href="{{ \App\Filament\Resources\PurchaseOrderResource::getUrl('view', ['record' => $record->purchaseOrder->id]) }}"
                                        class="po-value-link">
                                        {{ $record->purchaseOrder->number }}
                                    </a>
                                @else
                                    -
                                @endif
                            </div>
                        </div>
                        <div>
                            <div class="po-label">Referensi</div>
                            <div class="po-value">
                                {{ $record->reference ?? '-' }}
                            </div>
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
                <div class="po-table-wrapper">
                    <table class="po-table">
                        <thead>
                            <tr>
                                <th style="text-align: left; min-width: 200px;">Produk</th>
                                <th style="text-align: left; min-width: 150px;">Deskripsi</th>
                                <th style="text-align: center; min-width: 80px;">Kuantitas</th>
                                <th style="text-align: center; min-width: 80px;">Satuan</th>
                                <th style="text-align: center; min-width: 80px;">Diskon</th>
                                <th style="text-align: right; min-width: 120px;">Harga</th>
                                <th style="text-align: center; min-width: 100px;">Pajak</th>
                                <th style="text-align: right; min-width: 120px;">Total</th>
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
                                    <td style="text-align: center;">{{ $item->tax_name ?? '-' }}</td>
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
                            <div style="width: 100%; max-width: 320px;">
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
                                        <span style="color: #6b7280;">Biaya Pengiriman</span>
                                        <span
                                            class="po-value">{{ number_format($record->shipping_cost, 0, ',', '.') }}</span>
                                    </div>
                                @endif
                                @if($record->other_cost > 0)
                                    <div class="po-summary-row">
                                        <span style="color: #6b7280;">Biaya Transaksi</span>
                                        <span class="po-value">{{ number_format($record->other_cost, 0, ',', '.') }}</span>
                                    </div>
                                @endif
                                @if($record->withholding_amount > 0)
                                    <div class="po-summary-row">
                                        <span style="color: #6b7280;">Pemotongan</span>
                                        <span class="po-value"
                                            style="color: #dc2626;">-{{ number_format($record->withholding_amount, 0, ',', '.') }}</span>
                                    </div>
                                @endif

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

        </div>



        @php
            $isFullyPaid = $balanceDue <= 0.5;
            $accounts = \App\Models\Account::where('code', 'like', '1-100%')->get();
            $defaultAccount = $accounts->firstWhere('code', '1-10001')?->id ?? $accounts->first()?->id ?? '';
        @endphp

        @if(!$isFullyPaid && !in_array($record->status, ['cancelled', 'void', 'draft']))
            <div style="margin-top: 32px;">
                <form wire:submit.prevent="addPayment">
                    {{ $this->paymentForm }}
                </form>
            </div>
        @endif

        {{-- Related Transactions Section (At the bottom) --}}
        @php
            $relatedDeliveries = $record->purchaseOrder ? $record->purchaseOrder->deliveries : collect();
            $relatedPayments = $debt ? $debt->payments : collect();
            $hasRelated = $record->purchaseOrder || $relatedDeliveries->isNotEmpty() || $relatedPayments->isNotEmpty();
        @endphp

        @if($hasRelated)
            <div class="related-transactions-section" style="margin-top: 32px;">
                <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 16px; color: #374151;">Transaksi Terkait</h3>
                <div class="po-card" style="overflow: hidden;">
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
                            @if($record->purchaseOrder)
                                <tr>
                                    <td class="po-value" style="font-weight: 600;">
                                        <span class="po-badge po-badge-partial">
                                            Pesanan
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ \App\Filament\Resources\PurchaseOrderResource::getUrl('view', ['record' => $record->purchaseOrder->id]) }}"
                                            class="po-value-link">
                                            {{ $record->purchaseOrder->number }}
                                        </a>
                                    </td>
                                    <td>{{ $record->purchaseOrder->date->format('d/m/Y') }}</td>
                                    <td>
                                        @php
                                            $poStatusLabel = match ($record->purchaseOrder->status) {
                                                'draft' => 'Draf',
                                                'ordered' => 'Dipesan',
                                                'received' => 'Diterima',
                                                'billed' => 'Belum Lunas',
                                                'partial_billed' => 'Tagihan Sebagian',
                                                'paid' => 'Selesai',
                                                'cancelled' => 'Dibatalkan',
                                                'closed' => 'Selesai',
                                                default => ucfirst($record->purchaseOrder->status),
                                            };
                                            $poStatusColor = match ($record->purchaseOrder->status) {
                                                'ordered', 'billed', 'partial_billed' => 'dbeafe',
                                                'received', 'paid', 'closed' => 'd1fae5',
                                                'cancelled' => 'fef2f2',
                                                default => 'f3f4f6',
                                            };
                                            $poTextColor = match ($record->purchaseOrder->status) {
                                                'ordered', 'billed', 'partial_billed' => '1d4ed8',
                                                'received', 'paid', 'closed' => '059669',
                                                'cancelled' => 'dc2626',
                                                default => '4b5563',
                                            };
                                         @endphp
                                        <span
                                            class="po-badge po-badge-{{ in_array($record->purchaseOrder->status, ['received', 'paid', 'closed', 'completed']) ? 'success' : ($record->purchaseOrder->status === 'cancelled' ? 'cancelled' : 'confirmed') }}">
                                            {{ $poStatusLabel }}
                                        </span>
                                    </td>
                                    <td style="text-align: right; font-weight: 600;">
                                        Rp {{ number_format($record->purchaseOrder->total_amount, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endif

                            @foreach($relatedDeliveries as $delivery)
                                @php
                                    $deliveryTotal = $delivery->shipping_cost ?? 0;

                                    $dStatusLabel = match ($delivery->status) {
                                        'draft' => 'Draf',
                                        'pending' => 'Menunggu',
                                        'received' => 'Diterima',
                                        'cancelled' => 'Dibatalkan',
                                        default => ucfirst($delivery->status),
                                    };
                                    $dStatusColor = match ($delivery->status) {
                                        'draft' => 'f3f4f6',
                                        'pending' => 'dbeafe',
                                        'received' => 'd1fae5',
                                        'cancelled' => 'fef2f2',
                                        default => 'f3f4f6',
                                    };
                                    $dTextColor = match ($delivery->status) {
                                        'draft' => '4b5563',
                                        'pending' => '1d4ed8',
                                        'received' => '059669',
                                        'cancelled' => 'dc2626',
                                        default => '4b5563',
                                    };
                                @endphp
                                <tr>
                                    <td class="po-value" style="font-weight: 600;">
                                        <span class="po-badge po-badge-shipped">
                                            Pengiriman
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ \App\Filament\Resources\PurchaseDeliveryResource::getUrl('view', ['record' => $delivery->id]) }}"
                                            class="po-value-link">
                                            {{ $delivery->number }}
                                        </a>
                                    </td>
                                    <td>{{ $delivery->date->format('d/m/Y') }}</td>
                                    <td>
                                        <span
                                            class="po-badge po-badge-{{ in_array($delivery->status, ['received', 'shipped', 'delivered', 'completed']) ? 'success' : ($delivery->status === 'cancelled' ? 'cancelled' : 'confirmed') }}">
                                            {{ $dStatusLabel }}
                                        </span>
                                    </td>
                                    <td style="text-align: right; font-weight: 600;">
                                        Rp {{ number_format($deliveryTotal, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach

                            @if($record->down_payment > 0)
                                <tr>
                                    <td class="po-value" style="font-weight: 600;">
                                        <span class="po-badge po-badge-success">
                                            Pembayaran
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ \App\Filament\Resources\PurchaseOrderResource::getUrl('view', ['record' => $record->purchaseOrder->id]) }}"
                                            class="po-value-link">
                                            PP/{{ \Illuminate\Support\Str::after($record->purchaseOrder?->number ?? $record->number, '/') }}
                                        </a>
                                    </td>
                                    <td>{{ $record->purchaseOrder?->date?->format('d/m/Y') ?? $record->date?->format('d/m/Y') }}
                                    </td>
                                    <td>
                                        <span class="po-badge po-badge-success">
                                            Lunas
                                        </span>
                                    </td>
                                    <td style="text-align: right; font-weight: 600;">
                                        Rp {{ number_format($record->down_payment, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endif

                            @foreach($relatedPayments as $payment)
                                <tr>
                                    <td class="po-value" style="font-weight: 600;">
                                        <span class="po-badge po-badge-success">
                                            Pembayaran
                                        </span>
                                    </td>
                                    <td>
                                        @if($payment->number)
                                            @php
                                                $journalEntry = \App\Models\JournalEntry::where('reference_number', $payment->number)->first();
                                            @endphp
                                            @if($journalEntry)
                                                <a href="{{ url('admin/kas-bank/transaction/' . $journalEntry->id . '/detail') }}"
                                                    class="po-value-link">
                                                    {{ $payment->number }}
                                                </a>
                                            @else
                                                <span class="po-value">{{ $payment->number }}</span>
                                            @endif
                                        @else
                                            @php
                                                $journalEntry = \App\Models\JournalEntry::where('reference_number', 'like', '%' . \Carbon\Carbon::parse($payment->date)->format('Y-m-d') . '%')->first();
                                            @endphp
                                            @if($journalEntry)
                                                <a href="{{ url('admin/kas-bank/transaction/' . $journalEntry->id . '/detail') }}"
                                                    class="po-value-link">
                                                    Pembayaran pada {{ \Carbon\Carbon::parse($payment->date)->format('d/m/Y') }}
                                                </a>
                                            @else
                                                <span class="po-value">Pembayaran pada
                                                    {{ \Carbon\Carbon::parse($payment->date)->format('d/m/Y') }}</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($payment->date)->format('d/m/Y') }}</td>
                                    <td>
                                        <span class="po-badge po-badge-success">
                                            Berhasil
                                        </span>
                                    </td>
                                    <td style="text-align: right; font-weight: 600;">
                                        Rp {{ number_format($payment->amount, 0, ',', '.') }}
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
<div class="audit-log-section" style="margin-top: 48px; padding-top: 32px; border-top: 1px solid #e5e7eb;">
    <div style="font-size: 14px; font-weight: 500; margin-bottom: 8px;">
        <a href="#" wire:click.prevent="mountAction('auditLog')" class="po-value-link" style="text-decoration: none;">
            Pantau log perubahan data
        </a>
    </div>
    <div style="display: flex; align-items: center; gap: 6px; font-size: 13px; color: #6b7280;">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
            style="width: 14px; height: 14px;">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
        </svg>
        Terakhir diubah oleh system pada {{ $record->updated_at->format('d M Y H:i') }}
    </div>
</div>
</x-filament-panels::page>