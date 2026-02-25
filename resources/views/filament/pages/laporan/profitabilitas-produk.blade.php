@php
    $viewData = $this->getViewData();
    $results = $viewData['results'];
    $paginator = $viewData['paginator'];
    $pageStats = $viewData['pageStats'];

    $fmt = function ($num) {
        if ($num == 0)
            return '0';
        return number_format($num, 0, ',', '.');
    };

    $fmtPct = function ($num) {
        return number_format($num, 2, ',', '.') . '%';
    };
@endphp

<x-filament-panels::page>
    <style>
        .profit-report-container {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        /* Tabs Style */
        .profit-tabs {
            display: flex;
            gap: 1rem;
            border-bottom: 2px solid #eef2f6;
            padding-bottom: 2px;
        }

        .dark .profit-tabs {
            border-bottom-color: #2d3748;
        }

        .profit-tab-item {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #64748b;
            cursor: pointer;
            transition: all 0.2s;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
        }

        .profit-tab-item:hover {
            color: #3b82f6;
        }

        .profit-tab-item.active {
            color: #3b82f6;
            border-bottom-color: #3b82f6;
        }

        .dark .profit-tab-item.active {
            color: #60a5fa;
            border-bottom-color: #60a5fa;
        }

        /* Filter Row */
        .filter-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .filter-group-left {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }

        .filter-group-right {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }

        .custom-search-container {
            position: relative;
            width: 300px;
        }

        .custom-search-input {
            width: 100%;
            height: 38px;
            padding: 0 1rem 0 2.5rem;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            font-size: 0.8125rem;
            background: white;
            transition: all 0.2s;
        }

        .dark .custom-search-input {
            background: #111827;
            border-color: #374151;
            color: white;
        }

        .custom-search-input:focus {
            border-color: #3b82f6;
            ring: 2px solid rgba(59, 130, 246, 0.1);
            outline: none;
        }

        .search-icon-abs {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            width: 16px;
            height: 16px;
        }

        .date-picker-box {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 2px 8px;
            height: 38px;
        }

        .dark .date-picker-box {
            background: #111827;
            border-color: #374151;
        }

        .date-input-borderless {
            border: none;
            background: transparent;
            font-size: 0.8125rem;
            width: 125px;
            padding: 0;
            color: #475569;
        }

        .dark .date-input-borderless {
            color: #cbd5e1;
        }

        .date-input-borderless:focus {
            outline: none;
            box-shadow: none;
        }

        .filter-btn-fancy {
            height: 38px;
            padding: 0 1rem;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.8125rem;
            font-weight: 600;
            color: #475569;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .dark .filter-btn-fancy {
            background: #111827;
            border-color: #374151;
            color: #cbd5e1;
        }

        .filter-btn-fancy:hover {
            border-color: #cbd5e1;
            background: #f8fafc;
        }

        /* Table Card */
        .profit-table-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .dark .profit-table-card {
            background: #111827;
            border-color: #374151;
        }

        .profit-main-table {
            width: 100%;
            border-collapse: collapse;
        }

        .profit-main-table th {
            text-align: left;
            padding: 1rem;
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }

        .dark .profit-main-table th {
            background: #1f2937;
            border-bottom-color: #374151;
            color: #94a3b8;
        }

        .profit-main-table td {
            padding: 1rem;
            font-size: 0.8125rem;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
        }

        .dark .profit-main-table td {
            border-bottom-color: #374151;
            color: #e2e8f0;
        }

        .summary-footer {
            background: #f8fafc;
            font-weight: 800;
        }

        .dark .summary-footer {
            background: #1f2937;
        }

        .summary-footer td {
            border-top: 2px solid #e2e8f0;
            color: #0f172a;
        }

        .dark .summary-footer td {
            border-top-color: #374151;
            color: white;
        }

        .text-right {
            text-align: right;
        }

        .text-blue-link {
            color: #2563eb;
            font-weight: 700;
        }

        .dark .text-blue-link {
            color: #60a5fa;
        }

        .margin-pill {
            background: #dcfce7;
            color: #166534;
            padding: 2px 8px;
            border-radius: 6px;
            font-style: normal;
            font-weight: 700;
        }

        .dark .margin-pill {
            background: #064e3b;
            color: #4ade80;
        }

        .pagination-container {
            margin-top: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
        }

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
    </style>

    <div class="profit-report-container">
        {{-- Header: Tabs and Search --}}
        <div style="display: flex; justify-content: space-between; align-items: flex-end; gap: 1rem; border-bottom: 2px solid #eef2f6;"
            class="dark:border-gray-800">
            <div class="profit-tabs" style="border-bottom: none;">
                <div @class(['profit-tab-item', 'active' => $activeTab === 'lacak_stok'])
                    wire:click="setTab('lacak_stok')">
                    Produk Lacak Stok
                </div>
                <div @class(['profit-tab-item', 'active' => $activeTab === 'tanpa_lacak_stok'])
                    wire:click="setTab('tanpa_lacak_stok')">
                    Produk Tanpa Lacak Stok
                </div>
                <div @class(['profit-tab-item', 'active' => $activeTab === 'paket']) wire:click="setTab('paket')">
                    Produk Paket
                </div>
            </div>

            <div class="custom-search-container" style="margin-bottom: 8px;">
                <svg class="search-icon-abs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <input type="text" wire:model.live.debounce.500ms="search" placeholder="Cari produk"
                    class="custom-search-input">
            </div>
        </div>

        {{-- Table --}}
        <div class="profit-table-card">
            <table class="profit-main-table">
                <thead>
                    <tr>
                        <th style="min-width: 280px;">Nama Produk</th>
                        <th>Kode/SKU</th>
                        <th class="text-right">Qty</th>
                        <th class="text-right">Total Penjualan</th>
                        <th class="text-right">Total HPP</th>
                        <th class="text-right">Total Profit</th>
                        <th class="text-right">Profit Margin</th>
                        <th class="text-right">Biaya</th>
                        <th class="text-right">Jual Rata-Rata</th>
                        <th class="text-right">HPP Rata-Rata</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($results as $item)
                        <tr>
                            <td class="text-blue-link">{{ $item['name'] }}</td>
                            <td style="font-family: monospace; font-size: 0.75rem;">{{ $item['sku'] ?? '-' }}</td>
                            <td class="text-right">{{ $fmt($item['qty']) }}</td>
                            <td class="text-right">{{ $fmt($item['total_sales']) }}</td>
                            <td class="text-right">{{ $fmt($item['total_hpp']) }}</td>
                            <td class="text-right">{{ $fmt($item['total_profit']) }}</td>
                            <td class="text-right"><i class="margin-pill">{{ $fmtPct($item['profit_margin']) }}</i></td>
                            <td class="text-right">{{ $fmtPct($item['biaya_percent']) }}</td>
                            <td class="text-right">{{ $fmt($item['avg_sell_price']) }}</td>
                            <td class="text-right">{{ $fmt($item['avg_hpp']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-12" style="color: #94a3b8;">Tidak ada data untuk periode
                                ini.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="summary-footer">
                        <td colspan="2">Total</td>
                        <td class="text-right">{{ $fmt($pageStats['qty']) }}</td>
                        <td class="text-right">{{ $fmt($pageStats['total_sales']) }}</td>
                        <td class="text-right">{{ $fmt($pageStats['total_hpp']) }}</td>
                        <td class="text-right">{{ $fmt($pageStats['total_profit']) }}</td>
                        <td colspan="3"></td>
                        <td class="text-right">
                            {{ $results->count() > 0 ? $fmt($results->average('avg_sell_price')) : '0' }}
                        </td>
                    </tr>
                </tfoot>
            </table>
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
    </div>
</x-filament-panels::page>