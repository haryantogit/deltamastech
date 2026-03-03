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

    $statusBadge = function ($status) {
        $color = match (strtolower($status)) {
            'lunas', 'paid' => ['bg' => '#dcfce7', 'text' => '#10b981'],
            'terbit', 'posted', 'open' => ['bg' => '#3b82f6', 'text' => 'white'],
            'draf', 'draft' => ['bg' => '#64748b', 'text' => 'white'],
            default => ['bg' => '#ef4444', 'text' => 'white'],
        };
        $label = match (strtolower($status)) {
            'lunas', 'paid' => 'Lunas',
            'terbit', 'posted', 'open' => 'Terbit',
            'draf', 'draft' => 'Draf',
            default => ucfirst($status),
        };
        return '<span style="display:inline-flex;align-items:center;padding:2px 8px;border-radius:9999px;font-size:0.7rem;font-weight:600;color:' . $color['text'] . ';background:' . $color['bg'] . ';">' . $label . '</span>';
    };
@endphp

<x-filament-panels::page>
    <style>
        .delivery-report-container {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        .dark .delivery-report-container {
            background: #111827;
            border-color: #374151;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        .report-table th {
            padding: 0.875rem 1.25rem;
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            text-transform: capitalize;
            background: #f8fafc;
            border-bottom: 2px solid #f1f5f9;
        }

        .dark .report-table th {
            background: #1f2937;
            border-bottom-color: #374151;
            color: #94a3b8;
        }

        .report-table td {
            padding: 0.75rem 1.25rem;
            font-size: 0.8125rem;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
        }

        .dark .report-table td {
            border-bottom-color: #374151;
            color: #e2e8f0;
        }

        .nested-table {
            width: 100%;
            border-collapse: collapse;
            background: #fcfdfe;
        }

        .dark .nested-table {
            background: rgba(255, 255, 255, 0.01) !important;
        }

        .nested-table-header {
            border-bottom: 1px solid #f1f5f9;
        }

        .dark .nested-table-header {
            border-bottom-color: #1f2937;
        }

        .nested-table-row {
            border-bottom: 1px solid #f8fafc;
        }

        .dark .nested-table-row {
            border-bottom-color: rgba(255, 255, 255, 0.05);
        }

        .toggle-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.5rem;
            height: 1.5rem;
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 6px;
            margin-right: 0.5rem;
            font-size: 1rem;
            color: #3b82f6;
            background: rgba(59, 130, 246, 0.1);
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 800;
            line-height: 0;
            user-select: none;
        }

        .toggle-btn:hover {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
            transform: scale(1.05);
        }

        .dark .toggle-btn {
            background: rgba(59, 130, 246, 0.2);
            border-color: rgba(59, 130, 246, 0.3);
            color: #60a5fa;
        }

        .dark .toggle-btn:hover {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        .invoice-link {
            color: #3b82f6;
            font-weight: 700;
            text-decoration: none;
        }

        .product-link {
            color: #3b82f6;
            font-weight: 600;
            text-decoration: none;
        }

        .tag-badge {
            padding: 0.15rem 0.4rem;
            border-radius: 4px;
            font-size: 0.65rem;
            font-weight: 600;
            background: #f1f5f9;
            color: #475569;
            margin-right: 0.25rem;
            border: 1px solid #e2e8f0;
        }

        .dark .tag-badge {
            background: #374151;
            border-color: #4b5563;
            color: #cbd5e1;
        }

        /* Summary Section */
        .summary-section {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
            margin-top: 1.5rem;
        }

        .dark .summary-section {
            background: #111827;
            border-color: #374151;
        }

        .summary-title {
            font-size: 0.9375rem;
            font-weight: 800;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #f1f5f9;
            text-align: right;
        }

        .dark .summary-title {
            border-color: #374151;
        }

        .summary-row {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding: 0.5rem 1.25rem;
            border-bottom: 1px solid #f8fafc;
        }

        .dark .summary-row {
            border-color: rgba(255, 255, 255, 0.03);
        }

        .summary-row-label {
            font-size: 0.8125rem;
            font-weight: 600;
            color: #475569;
            text-align: right;
            min-width: 260px;
        }

        .dark .summary-row-label {
            color: #94a3b8;
        }

        .summary-row-value {
            font-size: 0.8125rem;
            font-weight: 700;
            color: #1e293b;
            text-align: right;
            min-width: 150px;
        }

        .dark .summary-row-value {
            color: #e2e8f0;
        }

        .summary-row.grand {
            border-top: 2px solid #e2e8f0;
            border-bottom: 2px solid #e2e8f0;
        }

        .dark .summary-row.grand {
            border-color: #374151;
        }

        .summary-row.grand .summary-row-label {
            text-decoration: underline;
        }

        .summary-row.grand .summary-row-value {
            text-decoration: underline;
        }

        /* Search row */
        .filter-search-row {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .dark .filter-search-row {
            border-color: #374151;
        }

        .custom-search-container {
            position: relative;
            width: 280px;
        }

        .search-icon-abs {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            width: 1rem;
            height: 1rem;
            color: #94a3b8;
        }

        .custom-search-input {
            width: 100%;
            padding: 0.5rem 0.75rem 0.5rem 2.25rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.8125rem;
            background: white;
            color: #1e293b;
            outline: none;
            transition: border-color 0.2s;
        }

        .dark .custom-search-input {
            background: #1f2937;
            border-color: #374151;
            color: #f1f5f9;
        }

        .custom-search-input:focus {
            border-color: #3b82f6;
        }

        @media print {

            .fi-header-actions,
            .filter-search-row {
                display: none !important;
            }
        }
    </style>

    <div class="delivery-report-container">
        {{-- Search Row --}}
        <div class="filter-search-row">
            <div class="custom-search-container">
                <svg class="search-icon-abs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <input type="text" wire:model.live.debounce.500ms="search" placeholder="Cari"
                    class="custom-search-input">
            </div>
        </div>

        {{-- Table --}}
        <div style="overflow-x: auto;">
            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width: 40px;"></th>
                        <th style="text-align: left;">Nomor Pembelian</th>
                        <th style="text-align: left;">No. Pesanan</th>
                        <th style="text-align: left;">Kontak</th>
                        <th style="text-align: left;">Tanggal</th>
                        <th style="text-align: center;">Status</th>
                        <th style="text-align: left;">Tag</th>
                        <th style="text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $inv)
                        @php $isExpanded = in_array($inv['id'], $expandedInvoices); @endphp
                        <tr wire:key="inv-{{ $inv['id'] }}" wire:click="toggleInvoice({{ $inv['id'] }})"
                            style="cursor: pointer;" class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                            <td>
                                <span class="toggle-btn">{{ $isExpanded ? '−' : '+' }}</span>
                            </td>
                            <td><span class="invoice-link">{{ $inv['number'] }}</span></td>
                            <td style="color: #3b82f6; font-weight: 600;">{{ $inv['po_number'] }}</td>
                            <td style="font-weight: 500;">{{ $inv['supplier_name'] }}</td>
                            <td>{{ $inv['date'] }}</td>
                            <td style="text-align: center;">{!! $statusBadge($inv['status']) !!}</td>
                            <td>
                                @foreach($inv['tags'] as $tag)
                                    <span class="tag-badge"
                                        style="background: {{ $tag->color }}15; color: {{ $tag->color }}; border-color: {{ $tag->color }}40;">
                                        {{ $tag->name }}
                                    </span>
                                @endforeach
                            </td>
                            <td style="text-align: right; font-weight: 700;">{{ $fmt($inv['total_amount']) }}</td>
                        </tr>

                        @if($isExpanded)
                            <tr wire:key="details-{{ $inv['id'] }}">
                                <td colspan="8" style="padding: 0;">
                                    <table class="nested-table">
                                        <thead>
                                            <tr class="nested-table-header">
                                                <th
                                                    style="padding: 0.5rem 1.25rem 0.5rem 4rem; text-align: left; font-size: 0.70rem; color: #94a3b8; font-weight: 600;">
                                                    Produk</th>
                                                <th
                                                    style="padding: 0.5rem 1.25rem; text-align: left; font-size: 0.70rem; color: #94a3b8; font-weight: 600;">
                                                    Kode/SKU</th>
                                                <th
                                                    style="padding: 0.5rem 1.25rem; text-align: right; font-size: 0.70rem; color: #94a3b8; font-weight: 600;">
                                                    Kuantitas</th>
                                                <th
                                                    style="padding: 0.5rem 1.25rem; text-align: left; font-size: 0.70rem; color: #94a3b8; font-weight: 600;">
                                                    Satuan</th>
                                                <th
                                                    style="padding: 0.5rem 1.25rem; text-align: right; font-size: 0.70rem; color: #94a3b8; font-weight: 600;">
                                                    Harga</th>
                                                <th
                                                    style="padding: 0.5rem 1.25rem; text-align: right; font-size: 0.70rem; color: #94a3b8; font-weight: 600;">
                                                    Diskon</th>
                                                <th
                                                    style="padding: 0.5rem 1.25rem; text-align: right; font-size: 0.70rem; color: #94a3b8; font-weight: 600;">
                                                    Pajak</th>
                                                <th
                                                    style="padding: 0.5rem 1.25rem; text-align: right; font-size: 0.70rem; color: #94a3b8; font-weight: 600;">
                                                    Jumlah</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($inv['items'] as $item)
                                                <tr class="nested-table-row">
                                                    <td style="padding: 0.5rem 1.25rem 0.5rem 4rem; font-size: 0.75rem;">
                                                        <span class="product-link">{{ $item['name'] }}</span>
                                                        @if ($item['qty'] < 0)
                                                            <span style="color:#ef4444;font-size:0.7rem;">(Retur)</span>
                                                        @endif
                                                    </td>
                                                    <td style="padding: 0.5rem 1.25rem; font-size: 0.75rem; color: #64748b;">
                                                        {{ $item['sku'] }}
                                                    </td>
                                                    <td style="padding: 0.5rem 1.25rem; text-align: right; font-size: 0.75rem;">
                                                        {{ number_format(abs($item['qty']), 0) }}
                                                    </td>
                                                    <td style="padding: 0.5rem 1.25rem; font-size: 0.75rem; color: #64748b;">
                                                        {{ $item['unit'] }}
                                                    </td>
                                                    <td style="padding: 0.5rem 1.25rem; text-align: right; font-size: 0.75rem;">
                                                        {{ $fmt($item['price']) }}
                                                    </td>
                                                    <td style="padding: 0.5rem 1.25rem; text-align: right; font-size: 0.75rem;">
                                                        {{ $item['discount'] }}%
                                                    </td>
                                                    <td style="padding: 0.5rem 1.25rem; text-align: right; font-size: 0.75rem;">
                                                        {{ $fmt($item['tax']) }}
                                                    </td>
                                                    <td
                                                        style="padding: 0.5rem 1.25rem; text-align: right; font-size: 0.75rem; font-weight: 600;">
                                                        {{ $fmt($item['subtotal']) }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="8" style="padding: 1.5rem 1.25rem;">
                                                    <div style="display: flex; justify-content: flex-end;">
                                                        <div
                                                            style="width: 300px; display: flex; flex-direction: column; gap: 0.5rem;">
                                                            <div
                                                                style="display: flex; justify-content: space-between; font-size: 0.8125rem;">
                                                                <span style="color: #64748b; font-weight: 500;">Sub Total</span>
                                                                <span style="font-weight: 700; color: #1e293b;"
                                                                    class="dark:text-white">{{ $fmt($inv['sub_total']) }}</span>
                                                            </div>
                                                            <div
                                                                style="display: flex; justify-content: space-between; font-size: 0.8125rem;">
                                                                <span style="color: #64748b; font-weight: 500;">Pajak</span>
                                                                <span style="font-weight: 700; color: #1e293b;"
                                                                    class="dark:text-white">{{ $fmt($inv['tax_amount']) }}</span>
                                                            </div>
                                                            <div style="display: flex; justify-content: space-between; font-size: 0.8125rem; border-top: 1px solid #e2e8f0; padding-top: 0.5rem; margin-top: 0.25rem;"
                                                                class="dark:border-gray-700">
                                                                <span style="color: #1e293b; font-weight: 800;"
                                                                    class="dark:text-white">Total</span>
                                                                <span
                                                                    style="color: #3b82f6; font-size: 1rem; font-weight: 700;">{{ $fmt($inv['total_amount']) }}</span>
                                                            </div>
                                                            <div
                                                                style="display: flex; justify-content: space-between; font-size: 0.8125rem;">
                                                                <span style="color: #64748b; font-weight: 500;">Total
                                                                    Dibayar</span>
                                                                <span style="font-weight: 700; color: #1e293b;"
                                                                    class="dark:text-white">{{ $fmt($inv['total_paid']) }}</span>
                                                            </div>
                                                            <div style="display: flex; justify-content: space-between; font-size: 0.8125rem; border-top: 1px solid #e2e8f0; padding-top: 0.5rem; margin-top: 0.25rem;"
                                                                class="dark:border-gray-700">
                                                                <span style="color: #64748b; font-weight: 500;">Sisa
                                                                    Tagihan</span>
                                                                <span
                                                                    style="font-weight: 700; color: #ef4444;">{{ $fmt($inv['balance_due']) }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 4rem; color: #94a3b8;">
                                <div class="flex flex-col items-center">
                                    <svg style="width: 48px; height: 48px; margin-bottom: 1rem; opacity: 0.2;" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span style="font-size: 0.875rem; font-weight: 500;">Tidak ada data untuk periode
                                        ini.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse

                    {{-- Grand Total row --}}
                    <tr style="border-top: 2px solid rgba(128,128,128,0.2);">
                        <td style="padding:16px 14px;" colspan="7"></td>
                        <td style="padding:16px 14px; text-align: right; font-size: 13px; font-weight: 700;"
                            class="text-gray-900 dark:text-gray-100">
                            {{ $fmt($globalStats['total_amount']) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Summary: Total Halaman Ini --}}
    <div class="summary-section">
        <div class="summary-title">Total Halaman Ini</div>
        <div class="summary-row">
            <div class="summary-row-label">Grand Subtotal</div>
            <div class="summary-row-value">{{ $fmt($pageStats['sub_total']) }}</div>
        </div>
        <div class="summary-row">
            <div class="summary-row-label">Total Pajak</div>
            <div class="summary-row-value">{{ $fmt($pageStats['total_tax']) }}</div>
        </div>
        <div class="summary-row grand">
            <div class="summary-row-label">Grand Total</div>
            <div class="summary-row-value">{{ $fmt($pageStats['total_amount']) }}</div>
        </div>
        <div class="summary-row">
            <div class="summary-row-label">Total Pembayaran</div>
            <div class="summary-row-value">{{ $fmt($pageStats['total_paid']) }}</div>
        </div>
        <div class="summary-row">
            <div class="summary-row-label">Total Sisa Tagihan</div>
            <div class="summary-row-value">{{ $fmt($pageStats['balance_due']) }}</div>
        </div>
    </div>

    {{-- Summary: Total Seluruh Halaman --}}
    <div class="summary-section">
        <div class="summary-title">Total Seluruh Halaman</div>
        <div class="summary-row">
            <div class="summary-row-label">Grand Subtotal</div>
            <div class="summary-row-value">{{ $fmt($globalStats['sub_total']) }}</div>
        </div>
        <div class="summary-row">
            <div class="summary-row-label">Total Pajak</div>
            <div class="summary-row-value">{{ $fmt($globalStats['total_tax']) }}</div>
        </div>
        <div class="summary-row grand">
            <div class="summary-row-label">Grand Total</div>
            <div class="summary-row-value">{{ $fmt($globalStats['total_amount']) }}</div>
        </div>
        <div class="summary-row">
            <div class="summary-row-label">Total Pembayaran</div>
            <div class="summary-row-value">{{ $fmt($globalStats['total_paid']) }}</div>
        </div>
        <div class="summary-row">
            <div class="summary-row-label">Total Sisa Tagihan</div>
            <div class="summary-row-value">{{ $fmt($globalStats['balance_due']) }}</div>
        </div>
    </div>

    @if ($paginator->hasPages() || count([5, 10, 20, 50, 100, 'all']) > 1)
        <div style="margin-top: 2rem; margin-bottom: 1rem;" class="pagination-row">
            <x-filament::pagination :paginator="$paginator" :page-options="[5, 10, 20, 50, 100, 'all']"
                current-page-option-property="perPage" />
        </div>
    @endif

    <x-filament-actions::modals />
</x-filament-panels::page>