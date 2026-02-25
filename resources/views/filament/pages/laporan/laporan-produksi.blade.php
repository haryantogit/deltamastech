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

        .date-display {
            display: flex;
            align-items: center;
            padding: 0.5rem 1rem;
            background: white;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            font-size: 0.875rem;
            color: #64748b;
            gap: 0.5rem;
        }

        .dark .date-display {
            background: #1e293b;
            border-color: #334155;
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
            min-width: 1200px;
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

        .trx-link {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
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
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.65rem;
            font-weight: 600;
            background: #f1f5f9;
            color: #475569;
        }

        .dark .tag-badge {
            background: #334155;
            color: #e2e8f0;
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
        $rows = $data['rows'];
    @endphp

    <div class="report-content">
        <div class="report-toolbar">
            <div class="search-container">
                <x-filament::icon icon="heroicon-m-magnifying-glass" class="w-5 h-5 search-icon" />
                <input type="text" wire:model.live.debounce.500ms="search" placeholder="Cari produk..."
                    class="search-input">
            </div>
            <div class="date-display">
                <x-filament::icon icon="heroicon-m-calendar" class="w-4 h-4" />
                {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} —
                {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
            </div>
        </div>

        <div class="report-section">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Produk Jadi</th>
                        <th class="number-col">Kuantitas</th>
                        <th class="number-col">HPP</th>
                        <th class="number-col">Nilai Produksi</th>
                        <th class="number-col">Biaya</th>
                        <th style="width: 100px;">Tanggal</th>
                        <th>Nomor</th>
                        <th>Gudang</th>
                        <th>Tag</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td style="font-weight: 500; color: #3b82f6;">{{ $row->finished_product }}</td>
                            <td class="number-col">{{ number_format($row->quantity, 0, ',', '.') }}</td>
                            <td class="number-col">{{ number_format($row->hpp, 2, ',', '.') }}</td>
                            <td class="number-col" style="font-weight: 600;">
                                {{ number_format($row->production_value, 2, ',', '.') }}</td>
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
                            <td colspan="9" style="text-align: center; color: #94a3b8; padding: 3rem;">
                                Tidak ada data produksi untuk periode ini.
                            </td>
                        </tr>
                    @endforelse

                    @if($rows->count() > 0)
                        <tr class="total-row">
                            <td style="padding-left: 1rem;">Total</td>
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

        @if($rows->count() > 0)
            <div style="text-align: right; color: #94a3b8; font-size: 0.75rem; margin-top: 0.5rem;">
                Total {{ $rows->count() }} pesanan produksi
            </div>
        @endif
    </div>
</x-filament-panels::page>