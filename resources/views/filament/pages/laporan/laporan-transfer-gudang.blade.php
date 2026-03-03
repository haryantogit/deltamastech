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

        /* Tab Filters matching Benchmark */
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
            font-size: 0.8125rem;
            font-weight: 600;
            transition: all 0.2s;
            cursor: pointer;
            white-space: nowrap;
            border: none;
            outline: none;
            background: transparent;
            color: #6b7280;
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

        .custom-tab-item:not(.active):hover {
            color: #374151;
            background-color: #f9fafb;
        }

        .dark .custom-tab-item:not(.active):hover {
            color: #e5e7eb;
            background-color: rgba(255, 255, 255, 0.05);
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1100px;
        }

        .report-table th {
            padding: 0.875rem 1.25rem;
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            text-transform: capitalize;
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

        .number-col {
            text-align: right !important;
            font-variant-numeric: tabular-nums;
        }

        .expand-btn {
            cursor: pointer;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: all 0.2s;
            background: #eff6ff;
            border: 1px solid #dbeafe;
            color: #3b82f6;
        }

        .expand-btn:hover {
            background: #dbeafe;
        }

        .dark .expand-btn {
            background: rgba(59, 130, 246, 0.1);
            border-color: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
        }

        .detail-row {
            background: #fcfcfc;
        }

        .dark .detail-row {
            background: rgba(255, 255, 255, 0.01);
        }

        .detail-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0.5rem 0;
            border: 1px solid #f1f5f9;
        }

        .dark .detail-table {
            border-color: #374151;
        }

        .detail-table th {
            background: #f8fafc;
            color: #64748b;
            font-size: 0.7rem;
            text-transform: capitalize;
            padding: 0.75rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .dark .detail-table th {
            background: #1f2937;
            border-bottom-color: #374151;
            color: #94a3b8;
        }

        .detail-table td {
            padding: 0.75rem;
            font-size: 0.8125rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .dark .detail-table td {
            border-bottom-color: #374151;
        }

        .saldo-row {
            background: #f8fafc;
            font-style: italic;
        }

        .dark .saldo-row {
            background: rgba(255, 255, 255, 0.05);
        }

        .trx-link {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 700;
        }

        .trx-link:hover {
            text-decoration: underline;
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

        .type-badge {
            display: inline-block;
            padding: 0.125rem 0.5rem;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .type-in {
            background: #dcfce7;
            color: #166534;
        }

        .type-out {
            background: #fee2e2;
            color: #991b1b;
        }

        .dark .type-in {
            background: rgba(34, 197, 94, 0.1);
            color: #4ade80;
        }

        .dark .type-out {
            background: rgba(239, 68, 68, 0.1);
            color: #f87171;
        }

        @media print {
            .search-row, .fi-header-actions, .custom-tabs-container, .pagination-row {
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
        $summary = $data['summary'];
        $details = $data['details'];
        $warehouses = $data['warehouses'];
        $paginator = $data['paginator'];
    @endphp

    <div class="report-content">
        {{-- Warehouse Tabs matching Benchmark --}}
        <div class="custom-tabs-container">
            @foreach($warehouses as $warehouse)
                <button 
                    wire:click="setWarehouse({{ $warehouse->id }})"
                    @class([
                        'custom-tab-item',
                        'active' => $warehouseId == $warehouse->id,
                    ])
                >
                    <span>{{ $warehouse->name }}</span>
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
                            <th style="width: 60px;"></th>
                            <th>Nama Produk</th>
                            <th>Kategori</th>
                            <th>Kode</th>
                            <th class="number-col">Kuantitas Awal</th>
                            <th class="number-col">Transfer Masuk</th>
                            <th class="number-col">Transfer Keluar</th>
                            <th class="number-col">Kuantitas Akhir</th>
                            <th class="number-col">Nilai Awal</th>
                            <th class="number-col">Nilai Akhir</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($summary as $row)
                            <tr wire:key="row-{{ $row->id }}" class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                                <td style="text-align: center;">
                                    <div class="expand-btn mx-auto" wire:click="toggleRow({{ $row->id }})">
                                        <x-filament::icon 
                                            :icon="in_array($row->id, $expandedRows) ? 'heroicon-m-minus' : 'heroicon-m-plus'" 
                                            class="w-3 h-3" 
                                        />
                                    </div>
                                </td>
                                <td style="font-weight: 700; color: #3b82f6;">{{ $row->name }}</td>
                                <td style="color: #64748b;">{{ $row->category }}</td>
                                <td style="color: #64748b;">{{ $row->sku }}</td>
                                <td class="number-col">{{ number_format($row->initial_qty, 0, ',', '.') }}</td>
                                <td class="number-col font-semibold" style="color: #16a34a;">
                                    {{ number_format($row->transfer_in, 0, ',', '.') }}
                                </td>
                                <td class="number-col font-semibold" style="color: #dc2626;">
                                    {{ number_format($row->transfer_out, 0, ',', '.') }}
                                </td>
                                <td class="number-col font-semibold">{{ number_format($row->final_qty, 0, ',', '.') }}</td>
                                <td class="number-col">{{ number_format($row->initial_value, 0, ',', '.') }}</td>
                                <td class="number-col font-semibold" style="color: #3b82f6;">{{ number_format($row->final_value, 0, ',', '.') }}</td>
                            </tr>

                            @if(in_array($row->id, $expandedRows))
                                <tr class="detail-row" wire:key="detail-container-{{ $row->id }}">
                                    <td></td>
                                    <td colspan="9" style="padding: 0 1.25rem 1.25rem 1.25rem;">
                                        <table class="detail-table">
                                            <thead>
                                                <tr>
                                                    <th>Tanggal</th>
                                                    <th>Nomor</th>
                                                    <th>Tipe</th>
                                                    <th>Dari Gudang</th>
                                                    <th>Ke Gudang</th>
                                                    <th class="number-col">Kuantitas</th>
                                                    <th class="number-col">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr class="saldo-row">
                                                    <td colspan="5">Saldo Awal</td>
                                                    <td class="number-col">{{ number_format($row->initial_qty, 0, ',', '.') }}</td>
                                                    <td class="number-col">{{ number_format($row->initial_value, 0, ',', '.') }}</td>
                                                </tr>
                                                @foreach($details[$row->id] ?? [] as $m)
                                                    <tr>
                                                        <td>{{ $m->date->format('d/m/Y') }}</td>
                                                        <td>
                                                            <a href="{{ $m->url }}" target="_blank" class="trx-link">
                                                                {{ $m->number }}
                                                            </a>
                                                        </td>
                                                        <td>
                                                            <span class="type-badge {{ $m->type == 'Masuk' ? 'type-in' : 'type-out' }}">
                                                                {{ $m->type }}
                                                            </span>
                                                        </td>
                                                        <td style="color: #64748b;">{{ $m->from }}</td>
                                                        <td style="color: #64748b;">{{ $m->to }}</td>
                                                        <td class="number-col font-medium">{{ number_format($m->qty, 0, ',', '.') }}</td>
                                                        <td class="number-col font-medium">{{ number_format($m->qty * $row->price, 0, ',', '.') }}</td>
                                                    </tr>
                                                @endforeach
                                                <tr class="saldo-row" style="font-weight: 700;">
                                                    <td colspan="5" style="color: #1e293b;" class="dark:text-gray-300">Saldo Akhir</td>
                                                    <td class="number-col">{{ number_format($row->final_qty, 0, ',', '.') }}</td>
                                                    <td class="number-col" style="color: #3b82f6;">{{ number_format($row->final_value, 0, ',', '.') }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="10" style="text-align: center; color: #94a3b8; padding: 4rem;">
                                    <div class="flex flex-col items-center">
                                        <svg style="width: 48px; height: 48px; margin-bottom: 1rem; opacity: 0.2;"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                        </svg>
                                        <span style="font-size: 0.875rem; font-weight: 500;">Tidak ada data transfer untuk gudang dan periode ini.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse

                        @if(count($summary) > 0)
                            <tr class="total-row">
                                <td colspan="4">Total</td>
                                <td class="number-col">{{ number_format($data['totalInitialQty'], 0, ',', '.') }}</td>
                                <td class="number-col" style="color: #16a34a;">{{ number_format($data['totalInQty'], 0, ',', '.') }}</td>
                                <td class="number-col" style="color: #dc2626;">{{ number_format($data['totalOutQty'], 0, ',', '.') }}</td>
                                <td class="number-col">{{ number_format($data['totalFinalQty'], 0, ',', '.') }}</td>
                                <td class="number-col">{{ number_format($data['totalInitialValue'], 0, ',', '.') }}</td>
                                <td class="number-col font-bold" style="color: #3b82f6;">{{ number_format($data['totalFinalValue'], 0, ',', '.') }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        @if ($paginator && ($paginator->hasPages() || count([5, 10, 20, 50, 100, 'all']) > 1))
            <div style="margin-top: 1.5rem; margin-bottom: 1rem;" class="pagination-row">
                <x-filament::pagination :paginator="$paginator" :page-options="[5, 10, 20, 50, 100, 'all']"
                    current-page-option-property="perPage" />
            </div>
        @endif
    </div>
</x-filament-panels::page>

