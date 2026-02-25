@php
    $viewData = $this->getViewData();
    $invoices = $viewData['invoices'];
    $paginator = $viewData['paginator'];
    $pageStats = $viewData['pageStats'];
    $globalStats = $viewData['globalStats'];
    $totalCount = $viewData['totalCount'];
    $expandedInvoices = $this->expandedInvoices;

    $fmt = function ($num) {
        if ($num == 0)
            return '0';
        return number_format($num, 0, ',', '.');
    };

    $fmtMargin = function ($num) {
        return number_format($num, 2, ',', '.') . '%';
    };
@endphp

<x-filament-panels::page>
    <style>
        .profit-invoice-container {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        .dark .profit-invoice-container {
            background: #111827;
            border-color: #374151;
        }

        /* Filter & Search row */
        .filter-search-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #f1f5f9;
            gap: 1rem;
        }

        .dark .filter-search-row {
            border-color: #374151;
        }

        .date-display {
            font-size: 0.8125rem;
            color: #64748b;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
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

        /* Main Table */
        .profit-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }

        .profit-table th {
            padding: 0.875rem 1rem;
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            background: #f8fafc;
            border-bottom: 2px solid #f1f5f9;
            text-align: left;
        }

        .dark .profit-table th {
            background: #1f2937;
            border-bottom-color: #374151;
            color: #94a3b8;
        }

        .profit-table td {
            padding: 0.75rem 1rem;
            font-size: 0.8125rem;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
        }

        .dark .profit-table td {
            border-bottom-color: #374151;
            color: #e2e8f0;
        }

        .row-clickable {
            cursor: pointer;
            transition: background 0.15s;
        }

        .row-clickable:hover {
            background: rgba(59, 130, 246, 0.02);
        }

        .dark .row-clickable:hover {
            background: rgba(255, 255, 255, 0.02);
        }

        .expand-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.25rem;
            height: 1.25rem;
            font-size: 0.875rem;
            font-weight: 700;
            color: #94a3b8;
            border-radius: 50%;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
        }

        .dark .expand-icon {
            border-color: #4b5563;
        }

        .row-clickable:hover .expand-icon {
            color: #3b82f6;
            border-color: #3b82f6;
        }

        /* Invoice Number Link */
        .invoice-link {
            color: #3b82f6;
            font-weight: 700;
            text-decoration: none;
        }

        .invoice-link:hover {
            text-decoration: underline;
        }

        /* Margin Pill */
        .margin-pill {
            display: inline-block;
            padding: 0.15rem 0.5rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            background: #dcfce7;
            color: #16a34a;
        }

        .dark .margin-pill {
            background: #064e3b;
            color: #4ade80;
        }

        .margin-pill.negative {
            background: #fee2e2;
            color: #dc2626;
        }

        .dark .margin-pill.negative {
            background: #450a0a;
            color: #fca5a5;
        }

        /* Expanded Detail */
        .details-wrapper {
            background: rgba(59, 130, 246, 0.01);
            padding: 1.25rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .dark .details-wrapper {
            background: rgba(255, 255, 255, 0.01);
            border-color: #374151;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            border: 1px solid #f1f5f9;
            overflow: hidden;
        }

        .dark .details-table {
            background: #111827;
            border-color: #374151;
        }

        .details-table th {
            background: rgba(59, 130, 246, 0.04);
            color: #475569;
            padding: 0.625rem 0.75rem;
            font-size: 0.7rem;
            text-transform: uppercase;
            font-weight: 700;
        }

        .dark .details-table th {
            background: rgba(255, 255, 255, 0.05);
            color: #94a3b8;
        }

        .details-table td {
            padding: 0.625rem 0.75rem;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.75rem;
        }

        .dark .details-table td {
            border-bottom-color: #1e293b;
        }

        .product-link {
            color: #3b82f6;
            font-weight: 600;
            text-decoration: none;
        }

        .product-link:hover {
            text-decoration: underline;
        }

        /* Summary Footer Rows */
        .summary-footer {
            background: #f8fafc;
        }

        .dark .summary-footer {
            background: #1f2937;
        }

        .summary-footer td {
            font-weight: 700;
            padding: 0.875rem 1rem;
            font-size: 0.8125rem;
        }

        /* Pagination */
        .pagination-container {
            padding: 1rem 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            margin-top: 2rem;
        }

        .pagination-status {
            font-size: 0.8125rem;
            color: #64748b;
            font-weight: 500;
        }

        .per-page-capsule {
            display: flex;
            align-items: center;
            background: rgba(128, 128, 128, 0.05);
            border: 1px solid rgba(128, 128, 128, 0.1);
            border-radius: 10px;
            padding: 0 0.75rem;
            height: 2.25rem;
            gap: 0;
        }

        .per-page-label {
            font-size: 0.75rem;
            color: #64748b;
            font-weight: 500;
            border-right: 1px solid rgba(128, 128, 128, 0.1);
            padding-right: 0.75rem;
            height: 100%;
            display: flex;
            align-items: center;
        }

        .per-page-capsule select {
            background: transparent;
            border: none;
            font-size: 0.8125rem;
            font-weight: 600;
            color: #1e293b;
            outline: none;
            cursor: pointer;
            padding: 0 0.5rem 0 0.75rem;
            margin: 0;
            appearance: none;
            -webkit-appearance: none;
        }

        .dark .per-page-capsule select {
            color: #f1f5f9;
        }

        .numeric-capsule nav {
            display: flex;
            align-items: center;
        }

        .numeric-capsule nav>div:first-child,
        .numeric-capsule nav p,
        .numeric-capsule [class*="hidden sm:flex-1"] {
            display: none !important;
        }

        .numeric-capsule nav div:last-child {
            display: flex !important;
            background: rgba(128, 128, 128, 0.05) !important;
            border: 1px solid rgba(128, 128, 128, 0.1) !important;
            border-radius: 10px !important;
            overflow: hidden !important;
        }

        .numeric-capsule a,
        .numeric-capsule span {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            min-width: 2.5rem !important;
            height: 2.25rem !important;
            padding: 0 0.75rem !important;
            font-size: 0.8125rem !important;
            font-weight: 600 !important;
            color: #1e293b !important;
            border: none !important;
            border-right: 1px solid rgba(128, 128, 128, 0.1) !important;
            background: transparent !important;
            transition: all 0.2s !important;
            text-decoration: none !important;
        }

        .dark .numeric-capsule a,
        .dark .numeric-capsule span {
            color: #f1f5f9 !important;
        }

        .numeric-capsule div:last-child> :last-child {
            border-right: none !important;
        }

        .numeric-capsule a:hover {
            background: rgba(59, 130, 246, 0.05) !important;
            color: #3b82f6 !important;
        }

        .numeric-capsule .active span,
        .numeric-capsule [aria-current="page"] span {
            background: rgba(59, 130, 246, 0.1) !important;
            color: #3b82f6 !important;
            font-weight: 700 !important;
        }

        .numeric-capsule svg {
            width: 1rem !important;
            height: 1rem !important;
        }

        @media print {

            .fi-header-actions,
            .filter-search-row,
            .pagination-container {
                display: none !important;
            }
        }
    </style>

    <div class="profit-invoice-container">
        {{-- Search Row --}}
        <div class="filter-search-row">
            <div class="date-display">
                <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                    </path>
                </svg>
                {{ \Carbon\Carbon::parse($this->startDate)->format('d/m/Y') }} —
                {{ \Carbon\Carbon::parse($this->endDate)->format('d/m/Y') }}
            </div>
            <div class="custom-search-container">
                <svg class="search-icon-abs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <input type="text" wire:model.live.debounce.500ms="search"
                    placeholder="Cari nomor tagihan, referensi, pelanggan..." class="custom-search-input">
            </div>
        </div>

        {{-- Table --}}
        <div style="overflow-x: auto;">
            <table class="profit-table">
                <thead>
                    <tr>
                        <th style="width: 40px;"></th>
                        <th>Nomor Tagihan</th>
                        <th>Referensi</th>
                        <th>Pelanggan</th>
                        <th>Tanggal</th>
                        <th style="text-align: right;">Total Penjualan</th>
                        <th style="text-align: right;">Total HPP</th>
                        <th style="text-align: right;">Total Pemotongan</th>
                        <th style="text-align: right;">Total Profit</th>
                        <th style="text-align: right;">Margin</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $inv)
                        @php $isExpanded = in_array($inv['id'], $expandedInvoices); @endphp
                        <tr class="row-clickable" wire:click="toggleInvoice({{ $inv['id'] }})">
                            <td>
                                <span class="expand-icon">{{ $isExpanded ? '−' : '+' }}</span>
                            </td>
                            <td><span class="invoice-link">{{ $inv['invoice_number'] }}</span></td>
                            <td style="color: #64748b;">{{ $inv['reference'] }}</td>
                            <td style="font-weight: 600;">{{ $inv['contact_name'] }}</td>
                            <td>{{ $inv['date'] }}</td>
                            <td style="text-align: right; font-weight: 600;">{{ $fmt($inv['total_penjualan']) }}</td>
                            <td style="text-align: right;">{{ $fmt($inv['total_hpp']) }}</td>
                            <td style="text-align: right;">{{ $fmt($inv['total_pemotongan']) }}</td>
                            <td style="text-align: right; font-weight: 600;">{{ $fmt($inv['total_profit']) }}</td>
                            <td style="text-align: right;">
                                <span class="margin-pill {{ $inv['margin'] < 0 ? 'negative' : '' }}">
                                    {{ $fmtMargin($inv['margin']) }}
                                </span>
                            </td>
                        </tr>

                        @if($isExpanded)
                            <tr>
                                <td colspan="10" style="padding: 0;">
                                    <div class="details-wrapper">
                                        <table class="details-table">
                                            <thead>
                                                <tr>
                                                    <th>Produk</th>
                                                    <th>Kode/SKU</th>
                                                    <th style="text-align: right;">Kuantitas</th>
                                                    <th style="text-align: right;">Total Penjualan</th>
                                                    <th style="text-align: right;">Total HPP</th>
                                                    <th style="text-align: right;">Total Profit</th>
                                                    <th style="text-align: right;">Margin</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($inv['items'] as $item)
                                                    <tr>
                                                        <td><span class="product-link">{{ $item['name'] }}</span></td>
                                                        <td style="color: #64748b;">{{ $item['sku'] }}</td>
                                                        <td style="text-align: right;">{{ $fmt($item['qty']) }}</td>
                                                        <td style="text-align: right;">{{ $fmt($item['total_penjualan']) }}</td>
                                                        <td style="text-align: right;">{{ $fmt($item['total_hpp']) }}</td>
                                                        <td style="text-align: right; font-weight: 600;">
                                                            {{ $fmt($item['total_profit']) }}</td>
                                                        <td style="text-align: right;">
                                                            <span class="margin-pill {{ $item['margin'] < 0 ? 'negative' : '' }}">
                                                                {{ $fmtMargin($item['margin']) }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="10" style="text-align: center; padding: 3rem; color: #94a3b8;">
                                Tidak ada data untuk periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                <tfoot>
                    {{-- Page Subtotal --}}
                    <tr class="summary-footer">
                        <td colspan="5" style="font-weight: 700;">Subtotal</td>
                        <td style="text-align: right; font-weight: 700;">{{ $fmt($pageStats['total_penjualan']) }}</td>
                        <td style="text-align: right; font-weight: 700;">{{ $fmt($pageStats['total_hpp']) }}</td>
                        <td style="text-align: right;">{{ $fmt($pageStats['total_pemotongan']) }}</td>
                        <td style="text-align: right; font-weight: 700;">{{ $fmt($pageStats['total_profit']) }}</td>
                        <td></td>
                    </tr>

                    {{-- Global Total --}}
                    <tr style="border-top: 2px solid #e2e8f0;" class="dark:border-gray-700">
                        <td colspan="5" style="font-weight: 800; padding: 0.875rem 1rem;">Total</td>
                        <td style="text-align: right; font-weight: 800; padding: 0.875rem 1rem;">
                            {{ $fmt($globalStats['total_penjualan']) }}</td>
                        <td style="text-align: right; font-weight: 800; padding: 0.875rem 1rem;">
                            {{ $fmt($globalStats['total_hpp']) }}</td>
                        <td style="text-align: right; padding: 0.875rem 1rem;">
                            {{ $fmt($globalStats['total_pemotongan']) }}</td>
                        <td style="text-align: right; font-weight: 800; padding: 0.875rem 1rem;">
                            {{ $fmt($globalStats['total_profit']) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Pagination Footer --}}
    <div class="pagination-container">
        <div class="pagination-status">
            Total {{ number_format($totalCount, 0, ',', '.') }} data
        </div>

        <div class="per-page-capsule">
            <span class="per-page-label">per halaman</span>
            <select wire:model.live="perPage">
                <option value="15">15</option>
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="500">500</option>
            </select>
            <svg style="width: 1rem; height: 1rem; color: #64748b; margin-left: -0.25rem;" fill="none"
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </div>

        <div class="numeric-capsule">
            {{ $paginator->links() }}
        </div>
    </div>

</x-filament-panels::page>