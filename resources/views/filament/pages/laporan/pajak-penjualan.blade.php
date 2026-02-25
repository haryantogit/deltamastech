<x-filament-panels::page>
    <style>
        .report-section {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            overflow-x: auto;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
            margin-bottom: 2rem;
        }

        .dark .report-section {
            background: #111827;
            border-color: #374151;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        .report-table th {
            padding: 1rem;
            font-size: 0.75rem;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            background: #f8fafc;
            border-bottom: 2px solid #f1f5f9;
            text-align: left;
        }

        .dark .report-table th {
            background: #1f2937;
            border-bottom-color: #374151;
        }

        .report-table td {
            padding: 0.875rem 1rem;
            font-size: 0.8125rem;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
        }

        .dark .report-table td {
            border-bottom-color: #374151;
            color: #e2e8f0;
        }

        .total-row {
            background: #f8fafc;
            font-weight: 700;
        }

        .dark .total-row {
            background: rgba(255, 255, 255, 0.02);
        }

        .expand-btn {
            cursor: pointer;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s;
            background: #3b82f6;
            color: white;
            border: none;
        }

        .expand-btn:hover {
            background: #2563eb;
            transform: scale(1.1);
        }

        .dark .expand-btn {
            background: #3b82f6;
        }

        .dark .expand-btn:hover {
            background: #2563eb;
        }

        .detail-header {
            background: #fcfcfc !important;
            border-top: 1px solid #f1f5f9;
        }

        .dark .detail-header {
            background: rgba(255, 255, 255, 0.02) !important;
            border-top-color: #374151;
        }

        .detail-row {
            background: white;
        }

        .dark .detail-row {
            background: #111827;
        }

        .detail-row td {
            padding: 0.75rem 1rem !important;
            font-size: 0.75rem !important;
            color: #1e293b !important;
        }

        .dark .detail-row td {
            color: #e2e8f0 !important;
        }

        .trx-link {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }

        .trx-link:hover {
            text-decoration: underline;
        }
    </style>

    @php
        $viewData = $this->getViewData();
        $sales = $viewData['sales'];
        $purchase = $viewData['purchase'];
        $salesDetails = $viewData['salesDetails'];
        $purchaseDetails = $viewData['purchaseDetails'];
        $totalNet = $viewData['totalNet'];
        $totalTax = $viewData['totalTax'];
    @endphp

    <div class="report-content">
        <div class="filter-ribbon">
            <div class="date-badge" x-on:click="$dispatch('open-modal', { id: 'fi-modal-action-filter' })">
                <x-filament::icon icon="heroicon-m-calendar" class="w-4 h-4 mr-2" />
                {{ \Carbon\Carbon::parse($startDate)->format('d/M/Y') }} —
                {{ \Carbon\Carbon::parse($endDate)->format('d/M/Y') }}
            </div>
        </div>

        <div class="report-section">
            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width: 40px;"></th>
                        <th>PPN(11%)</th>
                        <th style="text-align: right;">Net</th>
                        <th style="text-align: right;">Pajak</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Penjualan --}}
                    <tr>
                        <td style="vertical-align: middle; text-align: center;">
                            <div class="flex justify-center">
                                <div class="expand-btn" wire:click="toggleRow('sales')">
                                    <x-filament::icon 
                                        :icon="in_array('sales', $expandedRows) ? 'heroicon-m-minus' : 'heroicon-m-plus'" 
                                        class="w-3 h-3" 
                                    />
                                </div>
                            </div>
                        </td>
                        <td style="font-weight: 500;">Penjualan</td>
                        <td style="text-align: right;">{{ number_format($sales->net ?? 0, 0, ',', '.') }}</td>
                        <td style="text-align: right;">{{ number_format($sales->tax ?? 0, 0, ',', '.') }}</td>
                    </tr>

                    @if(in_array('sales', $expandedRows))
                        <tr class="detail-header">
                            <td></td>
                            <td style="font-weight: 600; font-size: 0.75rem; color: #475569;">Tanggal</td>
                            <td style="font-weight: 600; font-size: 0.75rem; color: #475569;">Transaksi</td>
                            <td style="font-weight: 600; font-size: 0.75rem; color: #475569; text-align: right;">Total</td>
                        </tr>
                        @forelse($salesDetails as $detail)
                            <tr class="detail-row">
                                <td></td>
                                <td>{{ \Carbon\Carbon::parse($detail->date)->format('d/m/Y') }}</td>
                                <td>
                                    <a href="{{ \App\Filament\Resources\SalesInvoiceResource::getUrl('view', ['record' => $detail->id]) }}" 
                                       target="_blank" class="trx-link">
                                        Tagihan Penjualan {{ $detail->number }}
                                    </a>
                                </td>
                                <td style="text-align: right;">{{ number_format($detail->total, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr class="detail-row">
                                <td></td>
                                <td colspan="3" style="text-align: center; color: #94a3b8; font-size: 0.75rem;">
                                    Tidak ada data detil.
                                </td>
                            </tr>
                        @endforelse
                    @endif

                    {{-- Pembelian --}}
                    <tr>
                        <td style="vertical-align: middle; text-align: center;">
                            <div class="flex justify-center">
                                <div class="expand-btn" wire:click="toggleRow('purchase')">
                                    <x-filament::icon 
                                        :icon="in_array('purchase', $expandedRows) ? 'heroicon-m-minus' : 'heroicon-m-plus'" 
                                        class="w-3 h-3" 
                                    />
                                </div>
                            </div>
                        </td>
                        <td style="font-weight: 500;">Pembelian</td>
                        <td style="text-align: right;">{{ number_format($purchase->net ?? 0, 0, ',', '.') }}</td>
                        <td style="text-align: right;">{{ number_format($purchase->tax ?? 0, 0, ',', '.') }}</td>
                    </tr>

                    @if(in_array('purchase', $expandedRows))
                        <tr class="detail-header">
                            <td></td>
                            <td style="font-weight: 600; font-size: 0.75rem; color: #475569;">Tanggal</td>
                            <td style="font-weight: 600; font-size: 0.75rem; color: #475569;">Transaksi</td>
                            <td style="font-weight: 600; font-size: 0.75rem; color: #475569; text-align: right;">Total</td>
                        </tr>
                        @forelse($purchaseDetails as $detail)
                            <tr class="detail-row">
                                <td></td>
                                <td>{{ \Carbon\Carbon::parse($detail->date)->format('d/m/Y') }}</td>
                                <td>
                                    <a href="{{ \App\Filament\Resources\PurchaseInvoiceResource::getUrl('view', ['record' => $detail->id]) }}" 
                                       target="_blank" class="trx-link">
                                        Tagihan Pembelian {{ $detail->number }}
                                    </a>
                                </td>
                                <td style="text-align: right;">{{ number_format($detail->total, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr class="detail-row">
                                <td></td>
                                <td colspan="3" style="text-align: center; color: #94a3b8; font-size: 0.75rem;">
                                    Tidak ada data detil.
                                </td>
                            </tr>
                        @endforelse
                    @endif

                    {{-- Summary Total --}}
                    <tr class="total-row">
                        <td></td>
                        <td style="font-weight: 700;">Total</td>
                        <td style="text-align: right; font-weight: 700;">{{ number_format($totalNet, 0, ',', '.') }}</td>
                        <td style="text-align: right; font-weight: 700;">{{ number_format($totalTax, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="report-section">
            <table class="report-table">
                <tbody>
                    <tr class="total-row">
                        <td style="width: 40px;"></td>
                        <td>Total</td>
                        <td style="text-align: right;">{{ number_format($totalNet, 0, ',', '.') }}</td>
                        <td style="text-align: right;">{{ number_format($totalTax, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>