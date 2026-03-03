@php
    $viewData = $this->getViewData();
    $products = $viewData['results'];
    $paginator = $viewData['paginator'];
    $totalCount = $viewData['totalCount'];
    $globalStats = $viewData['globalStats'];

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

        {{-- Table --}}
        <div style="overflow-x: auto;">
            <table class="report-table">
                <thead>
                    <tr>
                        <th style="text-align: left;">Nama Produk</th>
                        <th style="text-align: left;">Kode/SKU</th>
                        <th style="text-align: right;">Harga Saat Ini</th>
                        <th style="text-align: right;">Jumlah Terjual</th>
                        <th style="text-align: right;">Total</th>
                        <th style="text-align: right;">Rata-rata</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td><span class="text-link">{{ $product['name'] }}</span></td>
                            <td style="color: #64748b;">{{ $product['sku'] }}</td>
                            <td style="text-align: right;">{{ $fmt($product['harga_saat_ini']) }}</td>
                            <td style="text-align: right;">{{ $fmt($product['jumlah_terjual']) }}</td>
                            <td style="text-align: right; font-weight: 700;">{{ $fmt($product['total']) }}</td>
                            <td style="text-align: right;">{{ $fmt($product['rata_rata']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 3rem; color: #94a3b8;">
                                Tidak ada data penjualan produk untuk rentang ini.
                            </td>
                        </tr>
                    @endforelse

                    {{-- Total row --}}
                    <tr style="border-top: 2px solid rgba(128,128,128,0.2);">
                        <td style="padding:16px 14px; font-weight: 800;" colspan="3">Total</td>
                        <td style="padding:16px 14px; text-align: right; font-weight: 700;">
                            {{ $fmt($globalStats['qty']) }}
                        </td>
                        <td style="padding:16px 14px; text-align: right; font-weight: 700;">
                            {{ $fmt($globalStats['total']) }}
                        </td>
                        <td style="padding:16px 14px; text-align: right; font-weight: 700;"
                            class="text-gray-900 dark:text-gray-100">
                            {{ $fmt($globalStats['rata_rata']) }}
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