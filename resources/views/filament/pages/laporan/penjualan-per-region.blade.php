@php
    $viewData = $this->getViewData();
    $results = $viewData['results'];
    $paginator = $viewData['paginator'];
    $grandTotalCount = $viewData['grandTotalCount'];
    $grandTotalAmount = $viewData['grandTotalAmount'];

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
            margin-bottom: 1rem;
        }

        .dark .delivery-report-container {
            background: #111827;
            border-color: #374151;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
        }

        .report-table th {
            padding: 1rem 1.25rem;
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

        .total-row {
            background: #f8fafc;
            font-weight: 700;
        }

        .dark .total-row {
            background: rgba(255, 255, 255, 0.02);
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

        @media print {

            .filter-search-row,
            .pagination-row {
                display: none !important;
            }
        }
    </style>

    <div class="report-content">
        <div class="delivery-report-container">
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

            <div style="overflow-x: auto;">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Provinsi</th>
                            <th style="text-align: right;">Jumlah Transaksi</th>
                            <th style="text-align: right;">Total Penjualan</th>
                            <th style="text-align: right;">Rata-rata per Transaksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($results as $row)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                                <td style="font-weight: 600; color: #10b981;">{{ $row->region }}</td>
                                <td style="text-align: right;">{{ number_format($row->transaction_count, 0, ',', '.') }}
                                </td>
                                <td style="text-align: right; font-weight: 700;">{{ $fmt($row->total_amount) }}</td>
                                <td style="text-align: right;">
                                    {{ $fmt($row->transaction_count > 0 ? $row->total_amount / $row->transaction_count : 0) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 3rem; color: #94a3b8;">Tidak ada data.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(count($results) > 0)
                        <tfoot>
                            <tr class="total-row">
                                <td style="padding: 1rem 1.25rem;">TOTAL</td>
                                <td style="text-align: right; padding: 1rem 1.25rem;">
                                    {{ number_format($grandTotalCount, 0, ',', '.') }}
                                </td>
                                <td style="text-align: right; color: #3b82f6; padding: 1rem 1.25rem;">
                                    {{ $fmt($grandTotalAmount) }}</td>
                                <td style="text-align: right; padding: 1rem 1.25rem;">
                                    {{ $grandTotalCount > 0 ? $fmt($grandTotalAmount / $grandTotalCount) : '0' }}
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        @if ($paginator->hasPages() || count([5, 10, 20, 50, 100, 'all']) > 1)
            <div style="margin-top: 2rem; margin-bottom: 1rem;" class="pagination-row">
                <x-filament::pagination :paginator="$paginator" :page-options="[5, 10, 20, 50, 100, 'all']"
                    current-page-option-property="perPage" />
            </div>
        @endif
    </div>
</x-filament-panels::page>