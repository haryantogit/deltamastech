<x-filament-panels::page>
    <style>
        .delivery-report-container {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.01);
        }

        .dark .delivery-report-container {
            background: #111827;
            border-color: #374151;
        }

        .report-table th {
            padding: 0.875rem 1.25rem;
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
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

        .number-col {
            text-align: right !important;
            font-variant-numeric: tabular-nums;
        }

        .trx-link {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 600;
        }

        .trx-link:hover {
            text-decoration: underline;
        }

        .total-row {
            background: #f8fafc;
            font-weight: 700;
        }

        .dark .total-row {
            background: rgba(255, 255, 255, 0.02);
        }

        .tag-badge {
            display: inline-block;
            padding: 0.25rem 0.625rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 600;
            background: #f1f5f9;
            color: #475569;
        }

        .dark .tag-badge {
            background: #334155;
            color: #e2e8f0;
        }

        @media print {

            .search-row,
            .fi-header-actions,
            .pagination-row {
                display: none !important;
            }

            .delivery-report-container {
                border: none !important;
                box-shadow: none !important;
            }
        }
    </style>

    @php
        $data = $this->getViewData();
        $rows = $data['rows'];
        $paginator = $data['paginator'];
    @endphp

    <div class="delivery-report-container">
        {{-- Search Row --}}
        <div style="padding: 1rem 1.25rem; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: flex-end;"
            class="dark:border-gray-800 search-row">
            <div style="position: relative; width: 280px;">
                <svg style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); width: 1rem; height: 1rem; color: #94a3b8;"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <input type="text" wire:model.live.debounce.500ms="search" placeholder="Cari"
                    style="width: 100%; padding: 0.5rem 0.75rem 0.5rem 2.25rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.8125rem; background: white; color: #1e293b; outline: none;"
                    class="dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100">
            </div>
        </div>

        <div style="overflow-x: auto;">
            <table class="report-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th style="text-align: left; padding-left: 1.5rem;">Produk Jadi</th>
                        <th class="number-col">Kuantitas</th>
                        <th class="number-col">HPP</th>
                        <th class="number-col">Nilai Produksi</th>
                        <th class="number-col">Biaya</th>
                        <th style="width: 110px; text-align: left;">Tanggal</th>
                        <th style="text-align: left;">Nomor</th>
                        <th style="text-align: left;">Gudang</th>
                        <th style="text-align: left;">Tag</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02]">
                            <td style="font-weight: 600; color: #3b82f6; padding-left: 1.5rem;">{{ $row->finished_product }}
                            </td>
                            <td class="number-col">{{ number_format($row->quantity, 0, ',', '.') }}</td>
                            <td class="number-col">{{ number_format($row->hpp, 2, ',', '.') }}</td>
                            <td class="number-col" style="font-weight: 600;">
                                {{ number_format($row->production_value, 2, ',', '.') }}
                            </td>
                            <td class="number-col">{{ number_format($row->other_costs, 2, ',', '.') }}</td>
                            <td>{{ $row->date->format('d/m/Y') }}</td>
                            <td>
                                <a href="{{ $row->url }}" target="_blank" class="trx-link">
                                    {{ $row->number }}
                                </a>
                            </td>
                            <td>{{ $row->warehouse }}</td>
                            <td>
                                @if($row->tag != '-')
                                    <span class="tag-badge">{{ $row->tag }}</span>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align: center; color: #94a3b8; padding: 4rem;">
                                Tidak ada data produksi untuk periode ini.
                            </td>
                        </tr>
                    @endforelse

                    @if($rows->count() > 0)
                        <tr class="total-row">
                            <td style="padding-left: 1.5rem;">Total</td>
                            <td class="number-col">{{ number_format($data['totalQty'], 0, ',', '.') }}</td>
                            <td class="number-col">-</td>
                            <td class="number-col">{{ number_format($data['totalProductionValue'], 2, ',', '.') }}</td>
                            <td class="number-col">{{ number_format($data['totalOtherCosts'], 2, ',', '.') }}</td>
                            <td colspan="4"></td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    @if ($paginator->hasPages() || count([5, 10, 20, 50, 100, 'all']) > 1)
        <div style="margin-top: 1.5rem; margin-bottom: 1rem;" class="pagination-row">
            <x-filament::pagination :paginator="$paginator" :page-options="[5, 10, 20, 50, 100, 'all']"
                current-page-option-property="perPage" />
        </div>
    @endif
</x-filament-panels::page>