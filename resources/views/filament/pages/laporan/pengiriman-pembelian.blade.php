@php
    $viewData = $this->getViewData();
    $items = $viewData['items'];
    $paginator = $viewData['paginator'];
    $grandTotal = $viewData['grandTotal'];
    $nestedData = $viewData['nestedData'] ?? [];

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
            min-width: 900px;
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

        /* Group headers */
        .group-header-row {
            background: rgba(59, 130, 246, 0.02);
        }

        .dark .group-header-row {
            background: rgba(255, 255, 255, 0.02);
        }

        /* Nested details */
        .nested-table {
            width: 100%;
            border-collapse: collapse;
            background: #fcfdfe;
        }

        .dark .nested-table {
            background: rgba(255, 255, 255, 0.01) !important;
        }

        .nested-table th {
            padding: 0.5rem 1.25rem;
            text-align: left;
            font-size: 0.70rem;
            color: #94a3b8;
            font-weight: 600;
            border-bottom: 1px solid #f1f5f9;
        }

        .dark .nested-table th {
            border-bottom-color: #1f2937;
        }

        .nested-table td {
            padding: 0.5rem 1.25rem;
            font-size: 0.75rem;
            border-bottom: 1px solid #f8fafc;
        }

        .dark .nested-table td {
            border-bottom-color: rgba(255, 255, 255, 0.05);
        }

        /* Links */
        .doc-link {
            color: #3b82f6;
            font-weight: 700;
            text-decoration: none;
        }

        .doc-link:hover {
            text-decoration: underline;
        }

        .blue-label {
            color: #3b82f6;
            font-weight: 600;
        }

        /* Filter Row */
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

            .filter-search-row,
            .pagination-row {
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
                @if($this->viewType === 'pengiriman')
                    <thead>
                        <tr>
                            <th style="text-align: left;">Tanggal</th>
                            <th style="text-align: left;">Kode</th>
                            <th style="text-align: left;">Vendor</th>
                            <th style="text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $i)
                            <tr>
                                <td>{{ $i['date'] }}</td>
                                <td><span class="doc-link">{{ $i['number'] }}</span></td>
                                <td>{{ $i['vendor_name'] }}</td>
                                <td style="text-align: right; font-weight: 700; color: #3b82f6;">{{ $fmt($i['total_value']) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 3rem; color: #94a3b8;">Tidak ada data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                @elseif($this->viewType === 'vendor')
                    <thead>
                        <tr>
                            <th style="text-align: left;">Vendor / Perusahaan</th>
                            <th style="text-align: right;">Total Transaksi</th>
                            <th style="text-align: right;">Total Nilai</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $v)
                            <tr class="group-header-row">
                                <td style="padding-top: 1.25rem; padding-bottom: 0.5rem;">
                                    <div class="blue-label" style="font-size: 0.875rem;">{{ $v['group_name'] }}</div>
                                    <div style="font-size: 0.75rem; color: #64748b;">{{ $v['company_name'] ?? '-' }}</div>
                                </td>
                                <td style="text-align: right; padding-top: 1.25rem; font-weight: 500;">
                                    {{ number_format($v['transaction_count'], 0) }}</td>
                                <td style="text-align: right; padding-top: 1.25rem; font-weight: 700; color: #3b82f6;">
                                    {{ $fmt($v['total_value']) }}</td>
                            </tr>
                            <tr>
                                <td colspan="3" style="padding: 0;">
                                    <table class="nested-table">
                                        <thead>
                                            <tr>
                                                <th style="padding-left: 3rem;">Nomor</th>
                                                <th>Tanggal</th>
                                                <th>Produk</th>
                                                <th>Kode/SKU</th>
                                                <th style="text-align: right;">Kuantitas</th>
                                                <th style="text-align: right;">Harga</th>
                                                <th style="text-align: right;">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($nestedData[$v['group_id']] ?? [] as $line)
                                                <tr>
                                                    <td style="padding-left: 3rem;"><span
                                                            class="doc-link">{{ $line['doc_number'] }}</span></td>
                                                    <td style="color: #64748b;">{{ $line['doc_date'] }}</td>
                                                    <td class="blue-label">{{ $line['product_name'] }}</td>
                                                    <td style="font-family: monospace; color: #64748b;">
                                                        {{ $line['product_sku'] ?? '-' }}</td>
                                                    <td style="text-align: right;">{{ number_format($line['quantity'], 0) }}
                                                        {{ $line['unit_name'] }}</td>
                                                    <td style="text-align: right;">{{ $fmt($line['price']) }}</td>
                                                    <td style="text-align: right; font-weight: 600; color: #3b82f6;">
                                                        {{ $fmt($line['row_total']) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" style="text-align: center; padding: 3rem; color: #94a3b8;">Tidak ada data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                @else {{-- viewType === 'produk' --}}
                    <thead>
                        <tr>
                            <th style="text-align: left;">Produk / SKU</th>
                            <th style="text-align: right;">Total Kuantitas</th>
                            <th style="text-align: right;">Total Nilai</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $p)
                            <tr class="group-header-row">
                                <td style="padding-top: 1.25rem; padding-bottom: 0.5rem;">
                                    <div class="blue-label" style="font-size: 0.875rem;">{{ $p['group_name'] }}</div>
                                    <div style="font-size: 0.75rem; color: #64748b; font-family: monospace;">
                                        {{ $p['product_sku'] ?? '-' }}</div>
                                </td>
                                <td style="text-align: right; padding-top: 1.25rem; font-weight: 500;">
                                    {{ number_format($p['total_qty'], 0) }}</td>
                                <td style="text-align: right; padding-top: 1.25rem; font-weight: 700; color: #3b82f6;">
                                    {{ $fmt($p['total_value']) }}</td>
                            </tr>
                            <tr>
                                <td colspan="3" style="padding: 0;">
                                    <table class="nested-table">
                                        <thead>
                                            <tr>
                                                <th style="padding-left: 3rem;">Nomor</th>
                                                <th>Tanggal</th>
                                                <th>Vendor</th>
                                                <th style="text-align: right;">Kuantitas</th>
                                                <th style="text-align: right;">Harga</th>
                                                <th style="text-align: right;">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($nestedData[$p['group_id']] ?? [] as $line)
                                                <tr>
                                                    <td style="padding-left: 3rem;"><span
                                                            class="doc-link">{{ $line['doc_number'] }}</span></td>
                                                    <td style="color: #64748b;">{{ $line['doc_date'] }}</td>
                                                    <td>{{ $line['vendor_name'] }}</td>
                                                    <td style="text-align: right;">{{ number_format($line['quantity'], 0) }}
                                                        {{ $line['unit_name'] }}</td>
                                                    <td style="text-align: right;">{{ $fmt($line['price']) }}</td>
                                                    <td style="text-align: right; font-weight: 600; color: #3b82f6;">
                                                        {{ $fmt($line['row_total']) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" style="text-align: center; padding: 3rem; color: #94a3b8;">Tidak ada data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                @endif

                <tfoot>
                    <tr style="border-top: 2px solid rgba(128,128,128,0.2); background: #f8fafc;"
                        class="dark:bg-white/5 font-bold">
                        <td colspan="{{ $this->viewType === 'pengiriman' ? 3 : 2 }}" style="padding: 1rem 1.25rem;">
                            Grand Total</td>
                        <td style="text-align: right; padding: 1rem 1.25rem; color: #3b82f6;">{{ $fmt($grandTotal) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
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