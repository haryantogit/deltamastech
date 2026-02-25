@php
    $viewData = $this->getViewData();
    $results = $viewData['results'];
    $paginator = $viewData['paginator'];
    $totalCount = $viewData['totalCount'];
    $globalTotal = $viewData['globalTotal'];
    $groupBy = $viewData['groupBy'];

    $fmt = function ($num, $allowDecimals = false) {
        if ($num == 0)
            return '0';
        if ($allowDecimals && strpos((string) $num, '.') !== false) {
            return rtrim(rtrim(number_format($num, 2, ',', '.'), '0'), ',');
        }
        return number_format($num, 0, ',', '.');
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

        .right-controls {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .custom-search-container {
            position: relative;
            width: 250px;
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

        .mode-select-capsule {
            display: flex;
            align-items: center;
            background: rgba(128, 128, 128, 0.05);
            border: 1px solid rgba(128, 128, 128, 0.1);
            border-radius: 8px;
            height: 2.25rem;
            padding: 0 0.5rem;
        }

        .mode-select-capsule select {
            background: transparent;
            border: none;
            font-size: 0.8125rem;
            font-weight: 500;
            color: #1e293b;
            outline: none;
            cursor: pointer;
            padding-right: 0.5rem;
            appearance: none;
            -webkit-appearance: none;
        }

        .dark .mode-select-capsule select,
        .dark .custom-search-input {
            color: #f1f5f9;
        }

        .date-display {
            font-size: 0.8125rem;
            color: #64748b;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: white;
            border: 1px solid #e2e8f0;
            padding: 0.4rem 0.75rem;
            border-radius: 8px;
        }

        .dark .date-display,
        .dark .custom-search-input {
            background: #1f2937;
            border-color: #374151;
        }

        /* Tables & Groups */
        .report-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }

        .report-table th {
            padding: 0.875rem 1.25rem;
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            background: #f8fafc;
            border-bottom: 2px solid #f1f5f9;
            text-align: left;
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

        /* Group Headers */
        .group-header-row td {
            background: #f8fafc;
            font-weight: 600;
            color: #3b82f6;
            /* Blueish for links/headers */
            padding-top: 1rem;
            padding-bottom: 1rem;
        }

        .dark .group-header-row td {
            background: rgba(59, 130, 246, 0.05);
            color: #60a5fa;
        }

        /* Subtotal Rows */
        .group-total-row td {
            background: #fafafa;
            font-weight: 700;
            border-top: 1px solid #e2e8f0;
            border-bottom: 2px solid #f1f5f9;
        }

        .dark .group-total-row td {
            background: #111827;
            border-top-color: #374151;
            border-bottom-color: #1f2937;
        }

        .grand-total-container {
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 2px solid #e2e8f0;
            background: #f8fafc;
        }

        .dark .grand-total-container {
            border-top-color: #374151;
            background: #111827;
        }

        .grand-total-text {
            font-weight: 800;
            color: #1e293b;
        }

        .grand-total-amount {
            font-weight: 800;
            color: #1e293b;
            font-size: 1.1em;
        }

        .dark .grand-total-text, .dark .grand-total-amount {
            color: #f1f5f9;
        }

        .text-link {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }

        .text-link:hover {
            text-decoration: underline;
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

    <div class="delivery-report-container">
        {{-- Search Row --}}
        <div class="filter-search-row">
            <div class="custom-search-container">
                <svg class="search-icon-abs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <input type="text" wire:model.live.debounce.500ms="search" placeholder="Cari..."
                    class="custom-search-input">
            </div>

            <div class="right-controls">
                <div class="mode-select-capsule">
                    <select wire:model.live="groupBy">
                        <option value="pelanggan">Pelanggan</option>
                        <option value="pengiriman">Pengiriman</option>
                        <option value="produk">Produk</option>
                    </select>
                    <svg style="width: 0.875rem; height: 0.875rem; color: #64748b;" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>

                <div class="date-display">
                    {{ \Carbon\Carbon::parse($this->startDate)->format('d/m/Y') }} —
                    {{ \Carbon\Carbon::parse($this->endDate)->format('d/m/Y') }}
                    <svg style="width: 1rem; height: 1rem; color: #94a3b8;" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                        </path>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Tables by Mode --}}
        <div style="overflow-x: auto;">

            @if($groupBy === 'pelanggan')
                <table class="report-table">
                    <thead>
                        <tr>
                            <th style="width: 40%;">Pelanggan / Nama Produk</th>
                            <th style="text-align: right;">Kuantitas</th>
                            <th style="text-align: right;">Harga</th>
                            <th style="text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($results as $group)
                            <tr class="group-header-row">
                                <td colspan="4"><a href="#" class="text-link">{{ $group['name'] }}</a></td>
                            </tr>
                            @foreach($group['items'] as $item)
                                <tr>
                                    <td style="padding-left: 2.5rem;"><a href="#" class="text-link"
                                            style="font-weight: 400; font-size: 0.75rem;">{{ $item['product_name'] }}</a></td>
                                    <td style="text-align: right; color: #64748b; font-size: 0.75rem;">
                                        {{ $fmt($item['quantity'], true) }} {{ $item['unit_name'] }}
                                    </td>
                                    <td style="text-align: right; font-size: 0.75rem;">{{ $fmt($item['actual_price']) }}</td>
                                    <td style="text-align: right; font-size: 0.75rem;">{{ $fmt($item['total_price']) }}</td>
                                </tr>
                            @endforeach
                            <tr class="group-total-row">
                                <td colspan="3" style="font-size: 0.8125rem;">Total</td>
                                <td style="text-align: right; font-size: 0.8125rem;">{{ $fmt($group['total']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 3rem; color: #94a3b8;">Tidak ada data
                                    pelanggan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @elseif($groupBy === 'pengiriman')
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Kode</th>
                            <th>Pelanggan</th>
                            <th style="text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($results as $item)
                            <tr>
                                <td style="color: #64748b;">{{ $item['date'] }}</td>
                                <td><a href="#" class="text-link">{{ $item['number'] }}</a></td>
                                <td><a href="#" class="text-link" style="font-weight: 400;">{{ $item['customer_name'] }}</a>
                                </td>
                                <td style="text-align: right; font-weight: 500;">{{ $fmt($item['total']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 3rem; color: #94a3b8;">Tidak ada data
                                    pengiriman.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @elseif($groupBy === 'produk')
                <table class="report-table">
                    <thead>
                        <tr>
                            <th style="width: 40%;">Nama Produk / Tanggal</th>
                            <th>Kode</th>
                            <th>Pelanggan</th>
                            <th style="text-align: right;">Kuantitas</th>
                            <th style="text-align: right;">Harga</th>
                            <th style="text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($results as $group)
                            <tr class="group-header-row">
                                <td colspan="6"><a href="#" class="text-link">{{ $group['name'] }}</a></td>
                            </tr>
                            @foreach($group['items'] as $item)
                                <tr>
                                    <td style="padding-left: 2.5rem; color: #64748b; font-size: 0.75rem;">
                                        {{ \Carbon\Carbon::parse($item['delivery_date'])->format('d/m/Y') }}
                                    </td>
                                    <td><a href="#" class="text-link"
                                            style="font-weight: 400; font-size: 0.75rem;">{{ $item['delivery_number'] }}</a></td>
                                    <td><a href="#" class="text-link"
                                            style="font-weight: 400; font-size: 0.75rem;">{{ $item['customer_name'] }}</a></td>
                                    <td style="text-align: right; color: #64748b; font-size: 0.75rem;">
                                        {{ $fmt($item['quantity'], true) }} {{ $item['unit_name'] }}
                                    </td>
                                    <td style="text-align: right; font-size: 0.75rem;">{{ $fmt($item['actual_price']) }}</td>
                                    <td style="text-align: right; font-size: 0.75rem;">{{ $fmt($item['total_price']) }}</td>
                                </tr>
                            @endforeach
                            <tr class="group-total-row">
                                <td colspan="5" style="font-size: 0.8125rem;">Total</td>
                                <td style="text-align: right; font-size: 0.8125rem;">{{ $fmt($group['total']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 3rem; color: #94a3b8;">Tidak ada data
                                    produk.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @endif

            {{-- Grand Total row --}}
            <div class="grand-total-container">
                <span class="grand-total-text">
                    {{ $groupBy === 'pengiriman' ? 'Total' : 'Grand Total' }}
                </span>
                <span class="grand-total-amount">
                    {{ $fmt($globalTotal) }}
                </span>
            </div>
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