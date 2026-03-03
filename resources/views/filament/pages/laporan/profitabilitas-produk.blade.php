@php
    $data = $this->getViewData();
    $paginator = $data['paginator'];
    $summary = $data['summary'];
    $grandTotals = $data['grandTotals'];

    $fmt = function ($num) {
        if ($num == 0) return '0';
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

        /* Tab Filters */
        .custom-tabs-container {
            display: flex;
            width: 100%;
            gap: 0.25rem;
            overflow-x: auto;
            border-radius: 0.75rem;
            background-color: white;
            padding: 0.5rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
        }

        .dark .custom-tabs-container {
            background-color: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.1);
        }

        .custom-tab-item {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            border-radius: 0.5rem;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            font-weight: 600;
            transition: all 0.2s;
            cursor: pointer;
            white-space: nowrap;
            border: none;
            outline: none;
        }

        .custom-tab-item.active {
            background-color: #eff6ff !important;
            color: #2563eb !important;
            box-shadow: 0 0 0 1px #dbeafe !important;
        }

        .dark .custom-tab-item.active {
            background-color: rgba(59, 130, 246, 0.1) !important;
            color: #60a5fa !important;
            box-shadow: 0 0 0 1px rgba(59, 130, 246, 0.2) !important;
        }

        .custom-tab-item.inactive {
            color: #6b7280;
        }

        .custom-tab-item.inactive:hover {
            color: #374151;
            background-color: #f9fafb;
        }

        .dark .custom-tab-item.inactive {
            color: #9ca3af;
        }

        .dark .custom-tab-item.inactive:hover {
            color: #e5e7eb;
            background-color: rgba(255, 255, 255, 0.05);
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }

        .report-table th {
            padding: 0.875rem 1.25rem;
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            text-transform: capitalize;
            background: #f8fafc;
            border-bottom: 2px solid #f1f5f9;
            text-align: right;
        }

        .report-table th:first-child, .report-table th:nth-child(2) {
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

        .number-col {
            text-align: right !important;
        }

        .total-row {
            border-top: 2px solid rgba(128,128,128,0.2);
            background: #f8fafc;
            font-weight: 700;
        }

        .dark .total-row {
            background: rgba(255, 255, 255, 0.05);
        }

        .total-row td {
            padding: 1rem 1.25rem !important;
        }

        .margin-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.125rem 0.625rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-success {
            background-color: #f0fdf4;
            color: #16a34a;
        }

        .badge-danger {
            background-color: #fef2f2;
            color: #dc2626;
        }

        @media print {
            .fi-header-actions, .search-row, .custom-tabs-container {
                display: none !important;
            }
            .delivery-report-container {
                border: none !important;
                box-shadow: none !important;
            }
        }
    </style>

    <div class="report-content">
        {{-- Custom Tabs --}}
        <div class="custom-tabs-container">
            @foreach([
                'lacak_stok' => 'Produk Lacak Stok',
                'tanpa_lacak_stok' => 'Produk Tanpa Lacak Stok',
                'paket' => 'Produk Paket',
            ] as $key => $label)
                <button 
                    wire:click="setTab('{{ $key }}')"
                    @class([
                        'custom-tab-item',
                        'active' => $activeTab === $key,
                        'inactive' => $activeTab !== $key,
                    ])
                >
                    <span>{{ $label }}</span>
                </button>
            @endforeach
        </div>

        <div class="delivery-report-container">
            {{-- Search row --}}
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
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Nama Produk</th>
                            <th>Kode/SKU</th>
                            <th class="number-col">Qty</th>
                            <th class="number-col">Total Penjualan</th>
                            <th class="number-col">Total HPP</th>
                            <th class="number-col">Total Profit</th>
                            <th class="number-col">Profit Margin</th>
                            <th class="number-col">Biaya %</th>
                            <th class="number-col">Jual Rata-Rata</th>
                            <th class="number-col">HPP Rata-Rata</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($summary as $row)
                            <tr wire:key="row-{{ $row->id }}" class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                                <td style="font-weight: 700; color: #3b82f6;">{{ $row->name }}</td>
                                <td style="color: #64748b;">{{ $row->sku ?: '-' }}</td>
                                <td class="number-col">{{ $fmt($row->total_qty) }}</td>
                                <td class="number-col font-semibold">{{ $fmt($row->total_sales) }}</td>
                                <td class="number-col">{{ $fmt($row->total_hpp) }}</td>
                                <td class="number-col font-bold" style="color: #16a34a;">{{ $fmt($row->total_profit) }}</td>
                                <td class="number-col">
                                    <span @class([
                                        'margin-badge',
                                        'badge-success' => $row->profit_margin >= 0,
                                        'badge-danger' => $row->profit_margin < 0,
                                    ])>
                                        {{ number_format($row->profit_margin, 2, ',', '.') }}%
                                    </span>
                                </td>
                                <td class="number-col">{{ number_format($row->biaya_percent, 2, ',', '.') }}%</td>
                                <td class="number-col">{{ $fmt($row->avg_sell_price) }}</td>
                                <td class="number-col">{{ $fmt($row->avg_hpp) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" style="text-align: center; color: #94a3b8; padding: 4rem;">
                                    <div class="flex flex-col items-center">
                                        <svg style="width: 48px; height: 48px; margin-bottom: 1rem; opacity: 0.2;"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                        <span style="font-size: 0.875rem; font-weight: 500;">Tidak ada data profitabilitas untuk periode ini.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse

                        @if(count($summary) > 0)
                            <tr class="total-row">
                                <td colspan="2">Total</td>
                                <td class="number-col">{{ $fmt($grandTotals['qty']) }}</td>
                                <td class="number-col">{{ $fmt($grandTotals['sales']) }}</td>
                                <td class="number-col">{{ $fmt($grandTotals['hpp']) }}</td>
                                <td class="number-col font-bold" style="color: #3b82f6;">{{ $fmt($grandTotals['profit']) }}</td>
                                <td colspan="4"></td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        @if ($paginator && ($paginator->hasPages() || count([5, 10, 20, 50, 100, 'all']) > 1))
            <div style="margin-top: 1.5rem; margin-bottom: 1rem;">
                <x-filament::pagination :paginator="$paginator" :page-options="[5, 10, 20, 50, 100, 'all']"
                    current-page-option-property="perPage" />
            </div>
        @endif
    </div>
</x-filament-panels::page>