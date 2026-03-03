@php
    $viewData = $this->getViewData();
    $products = $viewData['products'];
    $paginator = $viewData['paginator'];
    $nestedData = $viewData['nestedData'];
    $grandTotalQty = $viewData['grandTotalQty'];
    $grandTotalAmount = $viewData['grandTotalAmount'];
    $expandedProducts = $this->expandedProducts;

    $fmt = function ($num) {
        if ($num == 0)
            return '0';
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

        .product-link {
            color: #3b82f6;
            font-weight: 800;
            text-decoration: none;
        }

        .product-link:hover {
            text-decoration: underline;
        }

        .invoice-link {
            color: #3b82f6;
            font-weight: 700;
            text-decoration: none;
        }

        .invoice-link:hover {
            text-decoration: underline;
        }

        /* Filter Search Row */
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
                        <th style="text-align: left;">Nama Produk</th>
                        <th style="text-align: left;">Kode/SKU</th>
                        <th style="text-align: right;">Harga Saat Ini</th>
                        <th style="text-align: right;">Jumlah Dibeli</th>
                        <th style="text-align: right;">Total</th>
                        <th style="text-align: right;">Rata-rata</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $p)
                        @php $isExpanded = in_array($p['product_id'], $expandedProducts); @endphp
                        <tr wire:key="p-{{ $p['product_id'] }}" wire:click="toggleProduct({{ $p['product_id'] }})"
                            style="cursor: pointer;" class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                            <td>
                                <span class="toggle-btn">{{ $isExpanded ? '−' : '+' }}</span>
                            </td>
                            <td><span class="product-link">{{ $p['product_name'] }}</span></td>
                            <td style="font-family: monospace; color: #64748b;">{{ $p['product_sku'] ?? '-' }}</td>
                            <td style="text-align: right;">{{ $fmt($p['current_price']) }}</td>
                            <td style="text-align: right; font-weight: 500;">{{ number_format($p['total_qty'], 0) }}</td>
                            <td style="text-align: right; font-weight: 700;">{{ $fmt($p['total_amount']) }}</td>
                            <td style="text-align: right;">{{ $fmt($p['average_price']) }}</td>
                        </tr>

                        @if($isExpanded)
                            <tr wire:key="details-{{ $p['product_id'] }}">
                                <td colspan="7" style="padding: 0;">
                                    <table class="nested-table">
                                        <thead>
                                            <tr class="nested-table-header">
                                                <th
                                                    style="padding: 0.5rem 1.25rem 0.5rem 4rem; text-align: left; font-size: 0.70rem; color: #94a3b8; font-weight: 600;">
                                                    Nomor Referensi</th>
                                                <th
                                                    style="padding: 0.5rem 1.25rem; text-align: left; font-size: 0.70rem; color: #94a3b8; font-weight: 600;">
                                                    Vendor</th>
                                                <th
                                                    style="padding: 0.5rem 1.25rem; text-align: left; font-size: 0.70rem; color: #94a3b8; font-weight: 600;">
                                                    Tanggal</th>
                                                <th
                                                    style="padding: 0.5rem 1.25rem; text-align: right; font-size: 0.70rem; color: #94a3b8; font-weight: 600;">
                                                    Kuantitas</th>
                                                <th
                                                    style="padding: 0.5rem 1.25rem; text-align: right; font-size: 0.70rem; color: #94a3b8; font-weight: 600;">
                                                    Jumlah</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($nestedData[$p['product_id']] ?? [] as $item)
                                                <tr class="nested-table-row">
                                                    <td style="padding: 0.5rem 1.25rem 0.5rem 4rem; font-size: 0.75rem;">
                                                        <span class="invoice-link">{{ $item['invoice_number'] }}</span>
                                                    </td>
                                                    <td style="padding: 0.5rem 1.25rem; font-size: 0.75rem; color: #1e293b;"
                                                        class="dark:text-white">{{ $item['supplier_name'] }}</td>
                                                    <td style="padding: 0.5rem 1.25rem; font-size: 0.75rem; color: #64748b;">
                                                        {{ $item['invoice_date'] }}
                                                    </td>
                                                    <td style="padding: 0.5rem 1.25rem; text-align: right; font-size: 0.75rem;">
                                                        {{ number_format($item['quantity'], 0) }}
                                                    </td>
                                                    <td
                                                        style="padding: 0.5rem 1.25rem; text-align: right; font-size: 0.75rem; font-weight: 600;">
                                                        {{ $fmt($item['total_price']) }}
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
                            <td colspan="7" style="text-align: center; padding: 4rem; color: #94a3b8;">
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

                    {{-- Total Row --}}
                    <tr style="border-top: 2px solid rgba(128,128,128,0.2); background: #f8fafc;"
                        class="dark:bg-white/5">
                        <td colspan="4" style="padding: 1rem 1.25rem; font-weight: 700;">Total</td>
                        <td style="text-align: right; padding: 1rem 1.25rem; font-weight: 700;">
                            {{ number_format($grandTotalQty, 0) }}
                        </td>
                        <td style="text-align: right; padding: 1rem 1.25rem; color: #3b82f6; font-weight: 700;">
                            {{ $fmt($grandTotalAmount) }}
                        </td>
                        <td style="text-align: right; padding: 1rem 1.25rem; font-weight: 700;">
                            {{ $fmt($grandTotalQty > 0 ? $grandTotalAmount / $grandTotalQty : 0) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    @if ($paginator->hasPages() || count([5, 10, 20, 50, 100, 'all']) > 1)
        <div style="margin-top: 2rem; margin-bottom: 1rem;">
            <x-filament::pagination :paginator="$paginator" :page-options="[5, 10, 20, 50, 100, 'all']"
                current-page-option-property="perPage" />
        </div>
    @endif

    <x-filament-actions::modals />
</x-filament-panels::page>