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

    $fmtMargin = function ($num) {
        return number_format($num, 2, ',', '.') . '%';
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

        /* Tables & Groups */
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

        .nested-row-container {
            background: #f8fafc;
        }

        .dark .nested-row-container {
            background: rgba(255, 255, 255, 0.02) !important;
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

        /* Invoice Link */
        .invoice-link {
            color: #3b82f6;
            font-weight: 700;
            text-decoration: none;
        }

        .invoice-link:hover {
            text-decoration: underline;
        }

        .product-link {
            color: #3b82f6;
            font-weight: 600;
            text-decoration: none;
        }

        .product-link:hover {
            text-decoration: underline;
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
                <input type="text" wire:model.live.debounce.500ms="search"
                    placeholder="Cari" class="custom-search-input">
            </div>
        </div>

        {{-- Table --}}
        <div style="overflow-x: auto;">
            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width: 40px;"></th>
                        <th style="text-align: left;">Nomor Tagihan</th>
                        <th style="text-align: left;">Referensi</th>
                        <th style="text-align: left;">Pelanggan</th>
                        <th style="text-align: left;">Tanggal</th>
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
                        <tr wire:key="inv-{{ $inv['id'] }}" wire:click="toggleInvoice({{ $inv['id'] }})"
                            style="cursor: pointer;">
                            <td>
                                <span class="toggle-btn">{{ $isExpanded ? '−' : '+' }}</span>
                            </td>
                            <td><span class="invoice-link">{{ $inv['invoice_number'] }}</span></td>
                            <td style="color: #64748b;">{{ $inv['reference'] }}</td>
                            <td style="font-weight: 500;">{{ $inv['contact_name'] }}</td>
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
                            <tr wire:key="details-{{ $inv['id'] }}">
                                <td colspan="10" style="padding: 0;">
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
                                                    style="padding: 0.5rem 1.25rem; text-align: right; font-size: 0.70rem; color: #94a3b8; font-weight: 600;">
                                                    Total Penjualan</th>
                                                <th
                                                    style="padding: 0.5rem 1.25rem; text-align: right; font-size: 0.70rem; color: #94a3b8; font-weight: 600;">
                                                    Total HPP</th>
                                                <th
                                                    style="padding: 0.5rem 1.25rem; text-align: right; font-size: 0.70rem; color: #94a3b8; font-weight: 600;">
                                                    Total Profit</th>
                                                <th
                                                    style="padding: 0.5rem 1.25rem; text-align: right; font-size: 0.70rem; color: #94a3b8; font-weight: 600;">
                                                    Margin</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($inv['items'] as $item)
                                                <tr class="nested-table-row">
                                                    <td style="padding: 0.5rem 1.25rem 0.5rem 4rem; font-size: 0.75rem;">
                                                        <span class="product-link">{{ $item['name'] }}</span>
                                                    </td>
                                                    <td style="padding: 0.5rem 1.25rem; font-size: 0.75rem; color: #64748b;">
                                                        {{ $item['sku'] }}</td>
                                                    <td style="padding: 0.5rem 1.25rem; text-align: right; font-size: 0.75rem;">
                                                        {{ $fmt($item['qty']) }}</td>
                                                    <td style="padding: 0.5rem 1.25rem; text-align: right; font-size: 0.75rem;">
                                                        {{ $fmt($item['total_penjualan']) }}</td>
                                                    <td style="padding: 0.5rem 1.25rem; text-align: right; font-size: 0.75rem;">
                                                        {{ $fmt($item['total_hpp']) }}</td>
                                                    <td
                                                        style="padding: 0.5rem 1.25rem; text-align: right; font-size: 0.75rem; font-weight: 600;">
                                                        {{ $fmt($item['total_profit']) }}</td>
                                                    <td style="padding: 0.5rem 1.25rem; text-align: right; font-size: 0.75rem;">
                                                        <span class="margin-pill {{ $item['margin'] < 0 ? 'negative' : '' }}">
                                                            {{ $fmtMargin($item['margin']) }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
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

                    {{-- Grand Total row --}}
                    <tr style="border-top: 2px solid rgba(128,128,128,0.2);">
                        <td style="padding:16px 14px;" colspan="5"></td>
                        <td style="padding:16px 14px; text-align: right; font-size: 13px; font-weight: 700;"
                            class="text-gray-900 dark:text-gray-100">
                            {{ $fmt($globalStats['total_penjualan']) }}
                        </td>
                        <td style="padding:16px 14px; text-align: right; font-size: 13px; font-weight: 700;"
                            class="text-gray-900 dark:text-gray-100">
                            {{ $fmt($globalStats['total_hpp']) }}
                        </td>
                        <td style="padding:16px 14px; text-align: right; font-size: 13px; font-weight: 700;"
                            class="text-gray-900 dark:text-gray-100">
                            {{ $fmt($globalStats['total_pemotongan']) }}
                        </td>
                        <td style="padding:16px 14px; text-align: right; font-size: 13px; font-weight: 700;"
                            class="text-gray-900 dark:text-gray-100">
                            {{ $fmt($globalStats['total_profit']) }}
                        </td>
                        <td style="padding:16px 14px;"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    @if ($paginator->hasPages() || count([5, 10, 20, 50, 100, 'all']) > 1)
        <div style="margin-top: 1.5rem; margin-bottom: 1rem;">
            <x-filament::pagination :paginator="$paginator" :page-options="[5, 10, 20, 50, 100, 'all']"
                current-page-option-property="perPage" />
        </div>
    @endif

    <x-filament-actions::modals />
</x-filament-panels::page>