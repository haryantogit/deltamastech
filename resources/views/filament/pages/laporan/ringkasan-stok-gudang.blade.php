@php
    $data = $this->getViewData();
    $warehouses = $data['warehouses'];
    $rows = $data['rows'];
    $warehouseTotals = $data['warehouseTotals'];
    $paginator = $data['paginator'];

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

        .product-name {
            color: #3b82f6;
            font-weight: 700;
            text-decoration: none;
        }

        .product-name:hover {
            text-decoration: underline;
        }

        .number-col {
            text-align: right !important;
        }

        .total-row {
            border-top: 2px solid rgba(128, 128, 128, 0.2);
            background: #f8fafc;
        }

        .dark .total-row {
            background: rgba(255, 255, 255, 0.05);
            border-top-color: #374151;
        }

        .total-row td {
            padding: 1rem 1.25rem !important;
            font-weight: 700;
        }

        .total-highlight {
            color: #3b82f6;
        }

        @media print {

            .fi-header-actions,
            .search-row {
                display: none !important;
            }

            .delivery-report-container {
                border: none !important;
                box-shadow: none !important;
            }
        }
    </style>

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
                        <th style="text-align: left;">Nama Produk</th>
                        <th style="text-align: left;">Kode</th>
                        @foreach($warehouses as $warehouse)
                            <th class="number-col">{{ $warehouse->name }}</th>
                        @endforeach
                        <th class="number-col">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                            <td>
                                <a href="{{ \App\Filament\Resources\ProductResource::getUrl('view', ['record' => $row->id]) }}"
                                    target="_blank" class="product-name">
                                    {{ $row->name }}
                                </a>
                            </td>
                            <td style="color: #64748b;">{{ $row->sku }}</td>
                            @foreach($warehouses as $warehouse)
                                <td class="number-col">
                                    {{ $fmt($row->quantities[$warehouse->id]) }}
                                </td>
                            @endforeach
                            <td class="number-col" style="font-weight: 600;">
                                {{ $fmt($row->total) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($warehouses) + 3 }}"
                                style="text-align: center; padding: 4rem; color: #94a3b8;">
                                <div class="flex flex-col items-center">
                                    <svg style="width: 48px; height: 48px; margin-bottom: 1rem; opacity: 0.2;" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                    <span style="font-size: 0.875rem; font-weight: 500;">Tidak ada data stok produk.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse

                    @if($rows->count() > 0)
                        <tr class="total-row">
                            <td colspan="2">Total</td>
                            @foreach($warehouses as $warehouse)
                                <td class="number-col">{{ $fmt($warehouseTotals[$warehouse->id]) }}</td>
                            @endforeach
                            <td class="number-col total-highlight">
                                {{ $fmt($data['grandTotal']) }}
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    @if ($paginator->hasPages() || count([5, 10, 20, 50, 100, 'all']) > 1)
        <div style="margin-top: 1.5rem; margin-bottom: 1rem;">
            <x-filament::pagination :paginator="$paginator" :page-options="[5, 10, 20, 50, 100, 'all']"
                current-page-option-property="perPage" />
        </div>
    @endif
</x-filament-panels::page>