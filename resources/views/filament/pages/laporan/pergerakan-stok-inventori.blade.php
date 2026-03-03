@php
    $data = $this->getViewData();
    $summary = $data['summary'];
    $details = $data['details'];
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
            min-width: 1200px;
        }

        .report-table th {
            padding: 0.875rem 1rem;
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
            padding: 0.75rem 1rem;
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

        .product-name {
            color: #3b82f6;
            font-weight: 700;
            text-decoration: none;
        }

        .product-name:hover {
            text-decoration: underline;
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
            padding: 1rem 1rem !important;
            font-weight: 700;
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

        .detail-row {
            background: #fcfcfc;
        }

        .dark .detail-row {
            background: rgba(255, 255, 255, 0.01);
        }

        .detail-card {
            padding: 1rem;
            background: white;
            border: 1px solid #f1f5f9;
            border-radius: 8px;
            margin: 0.5rem 0;
        }

        .dark .detail-card {
            background: #1f2937;
            border-color: #374151;
        }

        .detail-table {
            width: 100%;
            border-collapse: collapse;
        }

        .detail-table th {
            padding: 0.5rem;
            font-size: 0.7rem;
            background: #f1f5f9;
            text-align: left;
        }

        .dark .detail-table th {
            background: #374151;
        }

        .detail-table td {
            padding: 0.5rem;
            font-size: 0.75rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .sub-total-row {
            background: #f8fafc;
            font-style: italic;
        }

        .trx-link {
            color: #3b82f6;
            font-weight: 500;
        }

        .trx-link:hover {
            text-decoration: underline;
        }

        @media print {
            .search-row, .fi-header-actions, .expand-col, .expand-btn {
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
        <div style="padding: 1rem 1.25rem; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: flex-end; align-items: center;"
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
                        <th class="expand-col" style="width: 50px;"></th>
                        <th style="text-align: left;">Nama Produk</th>
                        <th style="text-align: left;">Kategori</th>
                        <th style="text-align: left;">Kode</th>
                        <th class="number-col">Kuantitas Awal</th>
                        <th class="number-col">Pergerakan Qty</th>
                        <th class="number-col">Kuantitas Akhir</th>
                        <th class="number-col">Nilai Awal</th>
                        <th class="number-col">Pergerakan Nilai</th>
                        <th class="number-col">Nilai Akhir</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($summary as $row)
                        <tr wire:key="row-{{ $row->id }}" class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                            <td class="expand-col" style="text-align: center;">
                                <div class="expand-btn" wire:click="toggleRow({{ $row->id }})">
                                    <x-filament::icon 
                                        :icon="in_array($row->id, $expandedRows) ? 'heroicon-m-minus' : 'heroicon-m-plus'" 
                                        class="w-3 h-3" 
                                    />
                                </div>
                            </td>
                            <td>
                                <a href="{{ \App\Filament\Resources\ProductResource::getUrl('view', ['record' => $row->id]) }}"
                                    target="_blank" class="product-name">
                                    {{ $row->name }}
                                </a>
                            </td>
                            <td style="color: #64748b;">{{ $row->category }}</td>
                            <td style="color: #64748b;">{{ $row->sku }}</td>
                            <td class="number-col">{{ $fmt($row->initial_qty) }}</td>
                            <td class="number-col {{ $row->movement_qty < 0 ? 'text-danger-600' : '' }}">
                                {{ $fmt($row->movement_qty) }}
                            </td>
                            <td class="number-col">{{ $fmt($row->final_qty) }}</td>
                            <td class="number-col">{{ $fmt($row->initial_value) }}</td>
                            <td class="number-col">{{ $fmt($row->movement_value) }}</td>
                            <td class="number-col">{{ $fmt($row->final_value) }}</td>
                        </tr>

                        @if(in_array($row->id, $expandedRows))
                            <tr class="detail-row" wire:key="detail-{{ $row->id }}">
                                <td></td>
                                <td colspan="9" style="padding: 0.5rem 1rem 1.5rem 1rem;">
                                    <div class="detail-card">
                                        <table class="detail-table">
                                            <thead>
                                                <tr>
                                                    <th>Tanggal</th>
                                                    <th>Transaksi</th>
                                                    <th>Perusahaan</th>
                                                    <th class="number-col">Pergerakan Qty</th>
                                                    <th class="number-col">Saldo Qty</th>
                                                    <th class="number-col">Harga</th>
                                                    <th class="number-col">Nilai</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr class="sub-total-row">
                                                    <td colspan="3">Saldo Awal</td>
                                                    <td class="number-col">0</td>
                                                    <td class="number-col">{{ $fmt($row->initial_qty) }}</td>
                                                    <td class="number-col">0</td>
                                                    <td class="number-col">{{ $fmt($row->initial_value) }}</td>
                                                </tr>
                                                @foreach($details[$row->id] ?? [] as $m)
                                                    <tr>
                                                        <td>{{ $m->date->format('d/m/Y') }}</td>
                                                        <td>
                                                            <a href="{{ $m->doc_link }}" target="_blank" class="trx-link">
                                                                {{ $m->doc_number }}
                                                            </a>
                                                        </td>
                                                        <td>{{ $m->company }}</td>
                                                        <td class="number-col">{{ $fmt($m->qty_movement) }}</td>
                                                        <td class="number-col">{{ $fmt($m->running_qty) }}</td>
                                                        <td class="number-col">{{ $fmt($m->price) }}</td>
                                                        <td class="number-col">{{ $fmt($m->total_value) }}</td>
                                                    </tr>
                                                @endforeach
                                                <tr class="sub-total-row" style="font-weight: 700; font-style: normal;">
                                                    <td colspan="3">Saldo Akhir</td>
                                                    <td class="number-col"></td>
                                                    <td class="number-col">{{ $fmt($row->final_qty) }}</td>
                                                    <td class="number-col"></td>
                                                    <td class="number-col">{{ $fmt($row->final_value) }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="10" style="text-align: center; padding: 4rem; color: #94a3b8;">
                                <div class="flex flex-col items-center">
                                    <svg style="width: 48px; height: 48px; margin-bottom: 1rem; opacity: 0.2;" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                    <span style="font-size: 0.875rem; font-weight: 500;">Tidak ada data pergerakan stok.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse

                    @if(count($summary) > 0)
                        <tr class="total-row">
                            <td colspan="4">Total</td>
                            <td class="number-col">{{ $fmt($data['totalInitialQty']) }}</td>
                            <td class="number-col">{{ $fmt($data['totalMovementQty']) }}</td>
                            <td class="number-col">{{ $fmt($data['totalFinalQty']) }}</td>
                            <td class="number-col">{{ $fmt($data['totalInitialValue']) }}</td>
                            <td class="number-col">{{ $fmt($data['totalMovementValue']) }}</td>
                            <td class="number-col" style="color: #3b82f6;">{{ $fmt($data['totalFinalValue']) }}</td>
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
