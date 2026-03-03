@php
    $viewData = $this->getViewData();
    $results = $viewData['results'];
    $paginator = $viewData['paginator'];
    $grandTotalQty = $viewData['grandTotalQty'];
    $grandTotalAmount = $viewData['grandTotalAmount'];
    $grandAverage = $viewData['grandAverage'];

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
            min-width: 600px;
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
            color: #10b981;
            text-decoration: none;
            font-weight: 600;
        }

        @media print {
            .fi-header-actions {
                display: none !important;
            }
        }
    </style>

    <div class="delivery-report-container">
        <div style="overflow-x: auto;">
            <table class="report-table">
                <thead>
                    <tr>
                        <th style="text-align: left;">Kategori</th>
                        <th style="text-align: right; width: 15%;">Kuantitas</th>
                        <th style="text-align: right; width: 25%;">Jumlah</th>
                        <th style="text-align: right; width: 15%;">Rata-Rata</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($results as $row)
                        <tr>
                            <td><span class="text-link">{{ $row->category_name }}</span></td>
                            <td style="text-align: right;">{{ $fmt($row->total_qty) }}</td>
                            <td style="text-align: right; font-weight: 700;">{{ $fmt($row->total_amount) }}</td>
                            <td style="text-align: right;">
                                {{ $fmt($row->total_qty > 0 ? $row->total_amount / $row->total_qty : 0) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 3rem; color: #94a3b8;">
                                Tidak ada data untuk periode ini.
                            </td>
                        </tr>
                    @endforelse

                    {{-- Total row --}}
                    <tr style="border-top: 2px solid rgba(128,128,128,0.2);">
                        <td style="padding:16px 14px; font-weight: 800;">Total</td>
                        <td style="padding:16px 14px; text-align: right; font-weight: 700;">
                            {{ $fmt($grandTotalQty) }}
                        </td>
                        <td style="padding:16px 14px; text-align: right; font-weight: 700;">
                            {{ $fmt($grandTotalAmount) }}
                        </td>
                        <td style="padding:16px 14px; text-align: right; font-weight: 700;"
                            class="text-gray-900 dark:text-gray-100">
                            {{ $fmt($grandAverage) }}
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