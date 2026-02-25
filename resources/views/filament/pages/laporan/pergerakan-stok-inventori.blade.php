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

        .expand-btn {
            cursor: pointer;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
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
            background: #f1f5f9;
            font-size: 0.65rem;
            padding: 0.5rem;
        }

        .dark .detail-table th {
            background: #374151;
        }

        .detail-table td {
            padding: 0.5rem;
            font-size: 0.7rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .dark .detail-table td {
            border-bottom-color: #374151;
        }

        .trx-link {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }

        .trx-link:hover {
            text-decoration: underline;
        }

        .saldo-row {
            background: #f8fafc;
            font-style: italic;
        }

        .saldo-akhir {
            font-weight: 600;
        }

        .dark .saldo-row {
            background: rgba(255, 255, 255, 0.05);
        }

        .total-row {
            background: #f8fafc;
            font-weight: 700;
        }

        .dark .total-row {
            background: rgba(255, 255, 255, 0.02);
        }

        @media print {
            .report-toolbar, .fi-header-actions {
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
        $summary = $data['summary'];
        $details = $data['details'];
    @endphp

    <div class="report-content">
        <div class="report-toolbar">
            <div class="search-container">
                <x-filament::icon icon="heroicon-m-magnifying-glass" class="w-5 h-5 search-icon" />
                <input type="text" 
                       wire:model.live.debounce.500ms="search" 
                       placeholder="Cari produk..." 
                       class="search-input">
            </div>
            <div class="date-display">
                <x-filament::icon icon="heroicon-m-calendar" class="w-4 h-4" />
                {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
            </div>
        </div>

        <div class="report-section">
            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width: 40px;"></th>
                        <th>Nama Produk</th>
                        <th>Kategori</th>
                        <th>Kode</th>
                        <th class="number-col">Kuantitas Awal</th>
                        <th class="number-col">Pergerakan Kuantitas</th>
                        <th class="number-col">Kuantitas Akhir</th>
                        <th class="number-col">Nilai Awal</th>
                        <th class="number-col">Pergerakan Nilai</th>
                        <th class="number-col">Nilai Akhir</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($summary as $row)
                        <tr wire:key="row-{{ $row->id }}">
                            <td style="text-align: center;">
                                <div class="expand-btn" wire:click="toggleRow({{ $row->id }})">
                                    <x-filament::icon 
                                        :icon="in_array($row->id, $expandedRows) ? 'heroicon-m-minus' : 'heroicon-m-plus'" 
                                        class="w-3 h-3" 
                                    />
                                </div>
                            </td>
                            <td style="font-weight: 500; color: #3b82f6;">{{ $row->name }}</td>
                            <td style="color: #64748b;">{{ $row->category }}</td>
                            <td style="color: #64748b;">{{ $row->sku }}</td>
                            <td class="number-col">{{ number_format($row->initial_qty, 0, ',', '.') }}</td>
                            <td class="number-col {{ $row->movement_qty < 0 ? 'text-danger-600' : '' }}">
                                {{ number_format($row->movement_qty, 0, ',', '.') }}
                            </td>
                            <td class="number-col">{{ number_format($row->final_qty, 0, ',', '.') }}</td>
                            <td class="number-col">{{ number_format($row->initial_value, 0, ',', '.') }}</td>
                            <td class="number-col">{{ number_format($row->movement_value, 0, ',', '.') }}</td>
                            <td class="number-col">{{ number_format($row->final_value, 0, ',', '.') }}</td>
                        </tr>

                        @if(in_array($row->id, $expandedRows))
                            <tr class="detail-row" wire:key="detail-container-{{ $row->id }}">
                                <td></td>
                                <td colspan="9" style="padding: 0 1rem 1rem 1rem;">
                                    <table class="detail-table">
                                        <thead>
                                            <tr>
                                                <th>Tanggal</th>
                                                <th>Transaksi</th>
                                                <th>Perusahaan</th>
                                                <th class="number-col">Pergerakan Kuantitas</th>
                                                <th class="number-col">Kuantitas</th>
                                                <th class="number-col">Harga Jual/Beli</th>
                                                <th class="number-col">Nilai</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr class="saldo-row">
                                                <td colspan="3">Saldo Awal</td>
                                                <td class="number-col">0</td>
                                                <td class="number-col">{{ number_format($row->initial_qty, 0, ',', '.') }}</td>
                                                <td class="number-col">0</td>
                                                <td class="number-col">{{ number_format($row->initial_value, 0, ',', '.') }}</td>
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
                                                    <td class="number-col">{{ number_format($m->qty_movement, 0, ',', '.') }}</td>
                                                    <td class="number-col">{{ number_format($m->running_qty, 0, ',', '.') }}</td>
                                                    <td class="number-col">{{ number_format($m->price, 0, ',', '.') }}</td>
                                                    <td class="number-col">{{ number_format($m->total_value, 0, ',', '.') }}</td>
                                                </tr>
                                            @endforeach
                                            <tr class="saldo-row saldo-akhir">
                                                <td colspan="3">Saldo Akhir</td>
                                                <td class="number-col"></td>
                                                <td class="number-col">{{ number_format($row->final_qty, 0, ',', '.') }}</td>
                                                <td class="number-col"></td>
                                                <td class="number-col">{{ number_format($row->final_value, 0, ',', '.') }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="10" style="text-align: center; color: #94a3b8; padding: 3rem;">
                                Tidak ada data pergerakan stok untuk periode ini.
                            </td>
                        </tr>
                    @endforelse

                    @if($summary->count() > 0)
                        <tr class="total-row">
                            <td colspan="4">Total</td>
                            <td class="number-col">{{ number_format($data['totalInitialQty'], 0, ',', '.') }}</td>
                            <td class="number-col">{{ number_format($data['totalMovementQty'], 0, ',', '.') }}</td>
                            <td class="number-col">{{ number_format($data['totalFinalQty'], 0, ',', '.') }}</td>
                            <td class="number-col">{{ number_format($data['totalInitialValue'], 0, ',', '.') }}</td>
                            <td class="number-col">{{ number_format($data['totalMovementValue'], 0, ',', '.') }}</td>
                            <td class="number-col">{{ number_format($data['totalFinalValue'], 0, ',', '.') }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        @if($summary->count() > 0)
            <div style="text-align: right; color: #94a3b8; font-size: 0.75rem; margin-top: 0.5rem;">
                Total {{ $summary->count() }} data
            </div>
        @endif
    </div>
</x-filament-panels::page>
