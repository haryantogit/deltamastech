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

        .ratio-badge {
            display: inline-block;
            padding: 0.125rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            background: #eff6ff;
            color: #2563eb;
            border: 1px solid #dbeafe;
        }

        .dark .ratio-badge {
            background: rgba(37, 99, 235, 0.1);
            color: #60a5fa;
            border-color: rgba(37, 99, 235, 0.2);
        }

        .duration-text {
            color: #64748b;
            font-weight: 600;
        }

        .dark .duration-text {
            color: #94a3b8;
        }

        .notes-section {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid #e2e8f0;
            font-size: 0.8125rem;
            color: #475569;
            margin-top: 2rem;
            line-height: 1.6;
        }

        .dark .notes-section {
            background: rgba(255, 255, 255, 0.02);
            border-color: #374151;
            color: #94a3b8;
        }

        .formula-box {
            background: white;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-weight: 600;
            color: #0f172a;
            display: inline-block;
            margin: 0.25rem 0;
        }

        .dark .formula-box {
            background: #0f172a;
            border-color: #334155;
            color: #f1f5f9;
        }

        @media print {

            .search-row,
            .fi-header-actions,
            .custom-tabs-container,
            .pagination-row,
            .notes-section {
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
        $warehouses = $data['warehouses'];
        $paginator = $data['paginator'];
    @endphp

    <div class="report-content">
        {{-- Warehouse Tabs matching Benchmark --}}
        <div class="custom-tabs-container">
            @foreach($warehouses as $warehouse)
                <button wire:click="setWarehouse({{ $warehouse->id }})" @class([
                    'custom-tab-item',
                    'active' => $warehouseId == $warehouse->id,
                ])>
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
                            <th>Nama Produk</th>
                            <th>Kategori</th>
                            <th>Kode</th>
                            <th class="number-col">Stok Awal</th>
                            <th class="number-col">Stok Akhir</th>
                            <th class="number-col">Stok Rata-rata</th>
                            <th class="number-col">Qty Terjual</th>
                            <th class="number-col">Rasio Perputaran</th>
                            <th class="number-col">Durasi Simpan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($summary as $row)
                            <tr wire:key="row-{{ $row->id }}"
                                class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                                <td style="font-weight: 700; color: #3b82f6;">{{ $row->name }}</td>
                                <td style="color: #64748b;">{{ $row->category }}</td>
                                <td style="color: #64748b;">{{ $row->sku }}</td>
                                <td class="number-col">{{ number_format($row->initial_qty, 0, ',', '.') }}</td>
                                <td class="number-col">{{ number_format($row->final_qty, 0, ',', '.') }}</td>
                                <td class="number-col" style="color: #64748b;">
                                    {{ number_format($row->avg_qty, 1, ',', '.') }}
                                </td>
                                <td class="number-col font-bold" style="color: #1e293b;">
                                    {{ number_format($row->qty_sold, 0, ',', '.') }}
                                </td>
                                <td class="number-col">
                                    <span class="ratio-badge">
                                        {{ number_format($row->ratio, 2, ',', '.') }}x
                                    </span>
                                </td>
                                <td class="number-col">
                                    <span class="duration-text">
                                        {{ number_format($row->duration, 1, ',', '.') }} Hari
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" style="text-align: center; color: #94a3b8; padding: 5rem 0;">
                                    <div
                                        style="display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%;">
                                        <svg style="width: 48px; height: 48px; margin-bottom: 1rem; opacity: 0.2;"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        <span style="font-size: 0.875rem; font-weight: 500;">Tidak ada data perputaran
                                            persediaan untuk gudang dan periode ini.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
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

        <div class="notes-section">
            <h3 style="font-size: 0.875rem; font-weight: 700; color: #1e293b; margin-bottom: 1rem;"
                class="dark:text-gray-100 italic">Keterangan & Rumus:</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                <div>
                    <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                        <span style="font-weight: 700; color: #475569;">Stok Rata-rata:</span>
                        <div class="formula-box">(Stok Awal + Stok Akhir) / 2</div>
                        <p style="margin-top: 0.25rem;">Rata-rata volume persediaan yang tersedia selama periode
                            laporan.</p>
                    </div>
                </div>
                <div>
                    <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                        <span style="font-weight: 700; color: #475569;">Rasio Perputaran (Turnover Ratio):</span>
                        <div class="formula-box">Qty Terjual / Stok Rata-rata</div>
                        <p style="margin-top: 0.25rem;">Menunjukkan berapa kali stok "berputar" atau terjual dalam satu
                            periode. Rasio lebih tinggi berarti pergerakan stok lebih efisien.</p>
                    </div>
                </div>
                <div>
                    <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                        <span style="font-weight: 700; color: #475569;">Durasi Simpan (Days Sales in Inventory):</span>
                        <div class="formula-box">Jumlah Hari Periode / Rasio Perputaran</div>
                        <p style="margin-top: 0.25rem;">Rata-rata jumlah hari suatu barang mengendap di gudang sebelum
                            terjual.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>