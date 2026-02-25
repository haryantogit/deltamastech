<x-filament-panels::page>
    <style>
        .report-content {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .report-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .search-container {
            position: relative;
            flex: 1;
            min-width: 250px;
            max-width: 400px;
        }

        .search-input {
            width: 100%;
            padding: 0.5rem 1rem 0.5rem 2.5rem;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: white;
            font-size: 0.875rem;
            outline: none;
            transition: border-color 0.2s;
        }

        .dark .search-input {
            background: #1e293b;
            border-color: #334155;
            color: #f1f5f9;
        }

        .search-input:focus {
            border-color: #3b82f6;
        }

        .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        .report-section {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            overflow-x: auto;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        .dark .report-section {
            background: #111827;
            border-color: #374151;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }

        .report-table th {
            padding: 0.75rem 1rem;
            font-size: 0.7rem;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            background: #f8fafc;
            border-bottom: 2px solid #f1f5f9;
            text-align: left;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .dark .report-table th {
            background: #1f2937;
            border-bottom-color: #374151;
        }

        .report-table td {
            padding: 0.75rem 1rem;
            font-size: 0.75rem;
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
            background: #f8fafc;
            font-weight: 700;
        }

        .dark .total-row {
            background: rgba(255, 255, 255, 0.02);
        }

        .total-highlight {
            background: #eff6ff;
            color: #1e40af;
            font-weight: 600;
        }

        .dark .total-highlight {
            background: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
        }

        .total-column-highlight {
            background: rgba(59, 130, 246, 0.02);
            font-weight: 600;
        }

        .dark .total-column-highlight {
            background: rgba(59, 130, 246, 0.05);
        }

        .warehouse-col {
            min-width: 120px;
        }

        @media print {

            .report-toolbar,
            .fi-header-actions {
                display: none !important;
            }

            .report-section {
                border: none !important;
                box-shadow: none !important;
            }
        }
    </style>

    @php
        $data = $this->getViewData();
        $warehouses = $data['warehouses'];
        $rows = $data['rows'];
        $warehouseTotals = $data['warehouseTotals'];
    @endphp

    <div class="report-content">
        <div class="report-toolbar">
            <div class="search-container">
                <x-filament::icon icon="heroicon-m-magnifying-glass" class="w-5 h-5 search-icon" />
                <input type="text" wire:model.live.debounce.500ms="search" placeholder="Cari produk..."
                    class="search-input">
            </div>
            <div style="font-size: 0.875rem; color: #64748b;">
                Per Tanggal: {{ date('d/m/Y') }}
            </div>
        </div>

        <div class="report-section">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Nama Produk</th>
                        <th>Kode</th>
                        @foreach($warehouses as $warehouse)
                            <th class="number-col warehouse-col">{{ $warehouse->name }}</th>
                        @endforeach
                        <th class="number-col total-highlight">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td style="font-weight: 500; color: #3b82f6;">{{ $row->name }}</td>
                            <td style="color: #64748b;">{{ $row->sku }}</td>
                            @foreach($warehouses as $warehouse)
                                <td class="number-col">
                                    {{ number_format($row->quantities[$warehouse->id], 0, ',', '.') }}
                                </td>
                            @endforeach
                            <td class="number-col total-column-highlight">
                                {{ number_format($row->total, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($warehouses) + 3 }}"
                                style="text-align: center; color: #94a3b8; padding: 3rem;">
                                Tidak ada data stok produk.
                            </td>
                        </tr>
                    @endforelse

                    @if($rows->count() > 0)
                        <tr class="total-row">
                            <td colspan="2">Total</td>
                            @foreach($warehouses as $warehouse)
                                <td class="number-col">{{ number_format($warehouseTotals[$warehouse->id], 0, ',', '.') }}</td>
                            @endforeach
                            <td class="number-col total-highlight">
                                {{ number_format($data['grandTotal'], 0, ',', '.') }}
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        @if($rows->count() > 0)
            <div style="text-align: right; color: #94a3b8; font-size: 0.75rem; margin-top: 0.5rem;">
                Total {{ $rows->count() }} produk
            </div>
        @endif
    </div>
</x-filament-panels::page>