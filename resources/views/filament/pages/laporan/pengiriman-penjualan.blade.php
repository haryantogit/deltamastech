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

        .group-header-row td {
            padding-top: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .group-total-row td {
            background: #f8fafc;
            font-weight: 700;
            border-top: 1px solid #e2e8f0;
            border-bottom: 2px solid #f1f5f9;
        }

        .dark .group-total-row td {
            background: #1f2937;
            border-top-color: #374151;
            border-bottom-color: #374151;
        }

        .text-link {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 600;
        }

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

        {{-- Tables by Mode --}}
        <div style="overflow-x: auto;">

            @if($groupBy === 'pelanggan')
                <table class="report-table">
                    <thead>
                        <tr>
                            <th style="text-align: left;">Pelanggan</th>
                            <th style="text-align: left;">Nama Produk</th>
                            <th style="text-align: right;">Kuantitas</th>
                            <th style="text-align: right;">Harga</th>
                            <th style="text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($results as $group)
                            {{-- Customer name row --}}
                            <tr class="group-header-row">
                                <td colspan="5">
                                    <span class="text-link">{{ $group['name'] }}</span>
                                </td>
                            </tr>
                            {{-- Product items --}}
                            @foreach($group['items'] as $item)
                                <tr>
                                    <td></td>
                                    <td>
                                        <span class="text-link"
                                            style="font-weight: 400; font-size: 0.75rem;">{{ $item['product_name'] }}</span>
                                    </td>
                                    <td style="text-align: right; color: #64748b; font-size: 0.75rem;">
                                        {{ $fmt($item['quantity'], true) }} {{ $item['unit_name'] }}
                                    </td>
                                    <td style="text-align: right; font-size: 0.75rem;">{{ $fmt($item['actual_price']) }}</td>
                                    <td style="text-align: right; font-size: 0.75rem;">{{ $fmt($item['total_price']) }}</td>
                                </tr>
                            @endforeach
                            {{-- Total per customer --}}
                            <tr class="group-total-row">
                                <td colspan="4" style="font-size: 0.8125rem;">Total</td>
                                <td style="text-align: right; font-size: 0.8125rem;">{{ $fmt($group['total']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 3rem; color: #94a3b8;">Tidak ada data
                                    pelanggan.</td>
                            </tr>
                        @endforelse

                        {{-- Grand Total row --}}
                        <tr style="border-top: 2px solid rgba(128,128,128,0.2); background: #f8fafc;">
                            <td style="padding: 1rem 1.25rem;" colspan="4">
                                <strong>Grand Total</strong>
                            </td>
                            <td style="padding: 1rem 1.25rem; text-align: right; font-weight: 800; font-size: 0.875rem;">
                                {{ $fmt($globalTotal) }}
                            </td>
                        </tr>
                    </tbody>
                </table>

            @elseif($groupBy === 'pengiriman')
                <table class="report-table">
                    <thead>
                        <tr>
                            <th style="text-align: left;">Tanggal</th>
                            <th style="text-align: left;">Kode</th>
                            <th style="text-align: left;">Pelanggan</th>
                            <th style="text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($results as $item)
                            <tr>
                                <td style="color: #64748b;">{{ $item['date'] }}</td>
                                <td><span class="text-link">{{ $item['number'] }}</span></td>
                                <td style="font-weight: 500;">{{ $item['customer_name'] }}</td>
                                <td style="text-align: right; font-weight: 700;">{{ $fmt($item['total']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 3rem; color: #94a3b8;">Tidak ada data
                                    pengiriman.</td>
                            </tr>
                        @endforelse

                        {{-- Grand Total row --}}
                        <tr style="border-top: 2px solid rgba(128,128,128,0.2); background: #f8fafc;">
                            <td style="padding: 1rem 1.25rem;" colspan="3">
                                <strong>Grand Total</strong>
                            </td>
                            <td style="padding: 1rem 1.25rem; text-align: right; font-weight: 800; font-size: 0.875rem;">
                                {{ $fmt($globalTotal) }}
                            </td>
                        </tr>
                    </tbody>
                </table>

            @elseif($groupBy === 'produk')
                <table class="report-table">
                    <thead>
                        <tr>
                            <th style="text-align: left;">Nama Produk</th>
                            <th style="text-align: left;">Tanggal</th>
                            <th style="text-align: left;">Kode</th>
                            <th style="text-align: left;">Pelanggan</th>
                            <th style="text-align: right;">Kuantitas</th>
                            <th style="text-align: right;">Harga</th>
                            <th style="text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($results as $group)
                            {{-- Product name row --}}
                            <tr class="group-header-row">
                                <td colspan="7">
                                    <span class="text-link">{{ $group['name'] }}</span>
                                </td>
                            </tr>
                            {{-- Delivery items --}}
                            @foreach($group['items'] as $item)
                                <tr>
                                    <td></td>
                                    <td style="color: #64748b; font-size: 0.75rem;">
                                        {{ \Carbon\Carbon::parse($item['delivery_date'])->format('d/m/Y') }}
                                    </td>
                                    <td>
                                        <span class="text-link"
                                            style="font-weight: 400; font-size: 0.75rem;">{{ $item['delivery_number'] }}</span>
                                    </td>
                                    <td style="font-size: 0.75rem;">{{ $item['customer_name'] }}</td>
                                    <td style="text-align: right; color: #64748b; font-size: 0.75rem;">
                                        {{ $fmt($item['quantity'], true) }} {{ $item['unit_name'] }}
                                    </td>
                                    <td style="text-align: right; font-size: 0.75rem;">{{ $fmt($item['actual_price']) }}</td>
                                    <td style="text-align: right; font-size: 0.75rem;">{{ $fmt($item['total_price']) }}</td>
                                </tr>
                            @endforeach
                            {{-- Total per product --}}
                            <tr class="group-total-row">
                                <td colspan="6" style="font-size: 0.8125rem;">Total</td>
                                <td style="text-align: right; font-size: 0.8125rem;">{{ $fmt($group['total']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 3rem; color: #94a3b8;">Tidak ada data
                                    produk.</td>
                            </tr>
                        @endforelse

                        {{-- Grand Total row --}}
                        <tr style="border-top: 2px solid rgba(128,128,128,0.2); background: #f8fafc;">
                            <td style="padding: 1rem 1.25rem;" colspan="6">
                                <strong>Grand Total</strong>
                            </td>
                            <td style="padding: 1rem 1.25rem; text-align: right; font-weight: 800; font-size: 0.875rem;">
                                {{ $fmt($globalTotal) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            @endif

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