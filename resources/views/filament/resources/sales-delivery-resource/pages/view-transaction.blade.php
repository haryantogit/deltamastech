@php
    $record = $this->record;
    $customer = $record->customer;
@endphp

<x-filament-panels::page>
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

        .po-badge-confirmed {
            background: #fef2f2;
            color: #dc2626;
        }

        .po-badge-shipped {
            background: #dbeafe;
            color: #1e40af;
        }

        .po-badge-partial {
            background: #fef9c3;
            color: #854d0e;
        }

        .po-badge-processing {
            background: #dbeafe;
            color: #1e40af;
        }

        .po-badge-packing {
            background: #dbeafe;
            color: #1e40af;
        }

        .po-badge-completed {
            background: #d1fae5;
            color: #059669;
        }

        .po-badge-delivered {
            background: #d1fae5;
            color: #059669;
        }

        .po-badge-success,
        .po-badge-delivered,
        .po-badge-closed,
        .po-badge-paid,
        .po-badge-lunas {
            background: #d1fae5;
            color: #059669;
        }

        .po-badge-completed {
            background: #d1fae5;
            color: #059669;
        }

        .po-badge-cancelled {
            background: #fef2f2;
            color: #dc2626;
        }

        .po-badge-pending,
        .po-badge-draft,
        .po-badge-gray {
            background: #f3f4f6;
            color: #4b5563;
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
            background: #78350f;
            color: #1e40af;
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



            .related-transactions-section {
                page-break-before: always;
                margin-top: 0 !important;
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
    </style>

    {{-- Header Actions --}}
    <div class="no-print"
        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            @php
                $statusIndo = [
                    'draft' => 'Draf',
                    'confirmed' => 'Terkonfirmasi',
                    'shipped' => 'Dikirim',
                    'delivered' => 'Terkirim',
                    'cancelled' => 'Dibatalkan',
                    'packing' => 'Dipacking',
                    'processing' => 'Diproses',
                    'pending' => 'Menunggu',
                ];
                $statusLabel = $statusIndo[$record->status] ?? ucfirst(str_replace('_', ' ', $record->status));
            @endphp
            <span class="po-badge po-badge-{{ $record->status }}">
                {{ $statusLabel }}
            </span>
        </div>
        <div class="flex gap-x-3 items-center">
            <x-filament::button color="gray" tag="a"
                :href="\App\Filament\Resources\SalesDeliveryResource::getUrl('index')" size="sm">
                Kembali
            </x-filament::button>
            <x-filament::button color="gray" size="sm" icon="heroicon-o-printer" onclick="window.print()">
                Print
            </x-filament::button>
            @if(in_array($record->status, ['draft', 'pending']))
                {{ $this->deliverAction }}
            @endif
            @if(in_array($record->status, ['draft', 'pending']))
                {{ $this->cancelAction }}
            @endif
        </div>
    </div>

    {{-- Main Card --}}
    <div class="print-area">
        <div class="po-card">
            {{-- Info Section --}}
            <div class="po-info-grid">
                {{-- Left: Customer Info --}}
                <div>
                    <div class="po-label">Pelanggan</div>
                    <div class="po-value" style="margin-bottom: 8px;">
                        <a href="{{ $customer ? \App\Filament\Resources\ContactResource::getUrl('view', ['record' => $customer->id]) : '#' }}"
                            class="po-value-link" style="font-size: 16px;">
                            {{ $customer?->name ?? '-' }}
                        </a>
                    </div>
                    @if($customer)
                        @if($customer->company)
                            <div class="po-contact-info">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" />
                                </svg>
                                {{ $customer->company }}
                            </div>
                        @endif
                        @if($customer->phone)
                            <div class="po-contact-info">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                                </svg>
                                {{ $customer->phone }}
                            </div>
                        @endif
                        @if($customer->address)
                            <div class="po-contact-info">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                                </svg>
                                {{ $customer->address }}
                            </div>
                        @endif
                    @endif
                </div>

                {{-- Right: Order Details --}}
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div>
                        <div class="po-label">Nomor Pengiriman</div>
                        <div class="po-value">{{ $record->number }}</div>
                    </div>
                    <div>
                        <div class="po-label">Tanggal</div>
                        <div class="po-value">{{ $record->date?->format('d/m/Y') ?? '-' }}</div>
                    </div>
                    <div>
                        <div class="po-label">No. Pesanan</div>
                        <div class="po-value">
                            <a href="{{ $record->salesOrder ? \App\Filament\Resources\SalesOrderResource::getUrl('view', ['record' => $record->salesOrder->id]) : '#' }}"
                                class="po-value-link">
                                {{ $record->salesOrder?->number ?? '-' }}
                            </a>
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
                    <div>
                        <div class="po-label">Ekspedisi</div>
                        <div class="po-value">{{ $record->shippingMethod?->name ?? '-' }}</div>
                    </div>
                    <div>
                        <div class="po-label">No. Resi</div>
                        <div class="po-value">{{ $record->tracking_number ?? '-' }}</div>
                    </div>
                </div>
            </div>

            {{-- Product Table --}}
            <table class="po-table">
                <thead>
                    <tr>
                        <th style="text-align: left;">Produk</th>
                        <th style="text-align: left;">Deskripsi</th>
                        <th style="text-align: center;">Jumlah</th>
                        <th style="text-align: center;">Satuan</th>
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
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" style="text-align: right;">Total Kuantitas</td>
                        <td style="text-align: center;">{{ number_format($totalQty, 0, ',', '.') }}</td>
                        <td colspan="1"></td>
                    </tr>
                </tfoot>
            </table>

            {{-- Summary Section --}}
            <div class="po-summary">
                <div style="margin-bottom: 8px;">
                    <div class="po-label">Catatan</div>
                    <div class="po-value" style="color: #6b7280; font-style: italic;">
                        {{ $record->notes ?? '-' }}
                    </div>
                </div>

                @if(($record->shipping_cost ?? 0) > 0)
                    <div style="display: flex; justify-content: flex-end; margin-top: 16px;">
                        <div style="width: 320px;">
                            <div class="po-summary-row po-summary-total">
                                <span style="color: #6b7280;">Biaya pengiriman</span>
                                <span class="po-value">{{ number_format($record->shipping_cost, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                @endif

            </div>

            {{-- Lampiran Section --}}
            @if(isset($record->attachments) && is_array($record->attachments) && count($record->attachments) > 0)
                <div class="no-print" style="border-top: 1px solid #e5e7eb; padding: 24px;">
                    <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 16px; color: #374151;">Lampiran</h3>
                    <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                        @foreach($record->attachments as $attachment)
                            <div style="width: 200px;">
                                <div style="margin-bottom: 8px;">
                                    <span class="po-badge po-badge-shipped" style="font-size: 11px;">
                                        Pengiriman ({{ $record->number }})
                                    </span>
                                </div>
                                <a href="{{ asset('storage/' . $attachment) }}" target="_blank">
                                    <img src="{{ asset('storage/' . $attachment) }}"
                                        style="width: 100%; height: 150px; object-fit: cover; border-radius: 8px; border: 1px solid #e5e7eb;">
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Related Transactions --}}
        @php
            $relatedInvoices = $record->salesOrder ? $record->salesOrder->invoices : collect();
            $relatedDeliveries = $record->salesOrder ? $record->salesOrder->deliveries->where('id', '!=', $record->id) : collect();
            $hasRelated = $record->salesOrder || $relatedInvoices->isNotEmpty() || $relatedDeliveries->isNotEmpty();
        @endphp

        @if($hasRelated)
            <div class="related-transactions-section" style="margin-top: 32px;">
                <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 16px; color: #374151;">Transaksi Terkait
                </h3>
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
                            @if($record->salesOrder)
                                <tr>
                                    <td class="po-value" style="font-weight: 600;">
                                        <span class="po-badge po-badge-partial">
                                            Pesanan
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ \App\Filament\Resources\SalesOrderResource::getUrl('view', ['record' => $record->salesOrder->id]) }}"
                                            class="po-value-link">
                                            {{ $record->salesOrder->number }}
                                        </a>
                                    </td>
                                    <td>{{ $record->salesOrder->date?->format('d/m/Y') }}</td>
                                    <td>
                                        @php
                                            $orderStatusIndo = [
                                                'draft' => 'Draf',
                                                'ordered' => 'Dipesan',
                                                'confirmed',
                                                'posted' => 'Dikonfirmasi',
                                                'processing' => 'Diproses',
                                                'completed' => 'Selesai',
                                                'shipped' => 'Dikirim',
                                                'delivered' => 'Terkirim',
                                                'cancelled' => 'Dibatalkan',
                                                'pending' => 'Menunggu',
                                            ];
                                        @endphp
                                        <span
                                            class="po-badge po-badge-{{ in_array($record->salesOrder->status, ['completed', 'delivered', 'paid', 'lunas']) ? 'success' : ($record->salesOrder->status === 'cancelled' ? 'cancelled' : 'confirmed') }}">
                                            {{ $orderStatusIndo[$record->salesOrder->status] ?? ucfirst($record->salesOrder->status) }}
                                        </span>
                                    </td>
                                    <td style="text-align: right; font-weight: 600;">
                                        Rp {{ number_format($record->salesOrder->total_amount, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endif

                            @foreach($relatedInvoices as $invoice)
                                <tr>
                                    <td class="po-value" style="font-weight: 600;">
                                        <span class="po-badge po-badge-confirmed">
                                            Tagihan
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ \App\Filament\Resources\SalesInvoiceResource::getUrl('view', ['record' => $invoice->id]) }}"
                                            class="po-value-link">
                                            {{ $invoice->invoice_number }}
                                        </a>
                                    </td>
                                    <td>{{ $invoice->transaction_date?->format('d/m/Y') }}</td>
                                    <td>
                                        @php
                                            $invoiceStatusIndo = [
                                                'paid' => 'Lunas',
                                                'lunas' => 'Lunas',
                                                'unpaid' => 'Belum Lunas',
                                                'partial' => 'Sebagian',
                                                'draft' => 'Draf',
                                                'overdue' => 'Jatuh Tempo',
                                            ];
                                        @endphp
                                        <span
                                            class="po-badge po-badge-{{ in_array($invoice->status, ['paid', 'lunas']) ? 'success' : ($invoice->status === 'partial' ? 'partial' : 'gray') }}">
                                            {{ $invoiceStatusIndo[$invoice->status] ?? ucfirst($invoice->status) }}
                                        </span>
                                    </td>
                                    <td style="text-align: right; font-weight: 600;">
                                        Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach

                            @foreach($relatedDeliveries as $otherDelivery)
                                <tr>
                                    <td class="po-value" style="font-weight: 600;">
                                        <span class="po-badge po-badge-shipped">
                                            Pengiriman
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ \App\Filament\Resources\SalesDeliveryResource::getUrl('view', ['record' => $otherDelivery->id]) }}"
                                            class="po-value-link">
                                            {{ $otherDelivery->number }}
                                        </a>
                                    </td>
                                    <td>{{ $otherDelivery->date?->format('d/m/Y') }}</td>
                                    <td>
                                        <span
                                            class="po-badge po-badge-{{ $otherDelivery->status === 'delivered' ? 'success' : ($otherDelivery->status === 'cancelled' ? 'cancelled' : 'confirmed') }}">
                                            {{ $statusIndo[$otherDelivery->status] ?? ucfirst($otherDelivery->status) }}
                                        </span>
                                    </td>
                                    <td style="text-align: right; font-weight: 600;">
                                        Rp 0
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
    <div class="audit-log-section" style="margin-top: 24px; font-size: 13px; color: #6b7280;">
        <a href="#" class="po-value-link">Pantau log perubahan data</a>
        <p style="margin-top: 8px; display: flex; align-items: center; gap: 6px;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" style="width: 14px; height: 14px;">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Terakhir diubah oleh system pada
            {{ $record->updated_at?->format('d M Y H:i') }}
        </p>
    </div>
</x-filament-panels::page>