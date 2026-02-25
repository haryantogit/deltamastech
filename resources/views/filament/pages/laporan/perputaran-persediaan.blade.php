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
            min-width: 900px;
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

        .detail-card {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            margin: 0.5rem 0;
        }

        .dark .detail-card {
            background: #1e293b;
            border-color: #334155;
        }

        .detail-table {
            width: 100%;
            border-collapse: collapse;
        }

        .detail-table th {
            background: transparent;
            font-size: 0.65rem;
            padding: 0.5rem;
            color: #94a3b8;
            border-bottom: 1px solid #f1f5f9;
        }

        .dark .detail-table th {
            border-bottom-color: #334155;
        }

        .detail-table td {
            padding: 0.5rem;
            font-size: 0.7rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .dark .detail-table td {
            border-bottom-color: #334155;
        }

        .ratio-badge {
            display: inline-block;
            padding: 0.125rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 600;
            background: #eff6ff;
            color: #2563eb;
        }

        .dark .ratio-badge {
            background: rgba(37, 99, 235, 0.1);
            color: #60a5fa;
        }

        .duration-text {
            color: #64748b;
            font-weight: 500;
        }

        .dark .duration-text {
            color: #94a3b8;
        }

        .notes-section {
            background: #f8fafc;
            border-radius: 8px;
            padding: 1rem;
            border: 1px solid #e2e8f0;
            font-size: 0.75rem;
            color: #64748b;
        }

        .dark .notes-section {
            background: #1e293b;
            border-color: #334155;
            color: #94a3b8;
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
                       placeholder="Cari gudang..." 
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
                        <th>Nama</th>
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
                        <tr wire:key="row-{{ $row->id }}">
                            <td style="text-align: center;">
                                <div class="expand-btn" wire:click="toggleRow({{ $row->id }})">
                                    <x-filament::icon 
                                        :icon="in_array($row->id, $expandedRows) ? 'heroicon-m-minus' : 'heroicon-m-plus'" 
                                        class="w-3 h-3" 
                                    />
                                </div>
                            </td>
                            <td style="font-weight: 600; color: #3b82f6;">{{ $row->name }}</td>
                            <td class="number-col">{{ number_format($row->initial_qty, 0, ',', '.') }}</td>
                            <td class="number-col">{{ number_format($row->final_qty, 0, ',', '.') }}</td>
                            <td class="number-col">{{ number_format($row->avg_qty, 2, ',', '.') }}</td>
                            <td class="number-col" style="font-weight: 500;">{{ number_format($row->qty_sold, 0, ',', '.') }}</td>
                            <td class="number-col">
                                <span class="ratio-badge">
                                    {{ number_format($row->ratio, 2, ',', '.') }} kali
                                </span>
                            </td>
                            <td class="number-col">
                                <span class="duration-text">
                                    {{ number_format($row->duration, 2, ',', '.') }} Hari
                                </span>
                            </td>
                        </tr>

                        @if(in_array($row->id, $expandedRows))
                            <tr class="detail-row" wire:key="detail-container-{{ $row->id }}">
                                <td></td>
                                <td colspan="7" style="padding: 0 1rem 1rem 1rem;">
                                    <div class="detail-card">
                                        <div style="font-size: 0.7rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 0.5rem;">
                                            Top 5 Produk (Berdasarkan Penjualan)
                                        </div>
                                        <table class="detail-table">
                                            <thead>
                                                <tr>
                                                    <th>Produk</th>
                                                    <th>Kode</th>
                                                    <th class="number-col">Qty Terjual</th>
                                                    <th class="number-col">Stok Rata-rata</th>
                                                    <th class="number-col">Rasio</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($details[$row->id] ?? [] as $d)
                                                    <tr>
                                                        <td style="font-weight: 500;">{{ $d->name }}</td>
                                                        <td style="color: #64748b;">{{ $d->sku }}</td>
                                                        <td class="number-col">{{ number_format($d->sold, 0, ',', '.') }}</td>
                                                        <td class="number-col">{{ number_format($d->avg_stock, 2, ',', '.') }}</td>
                                                        <td class="number-col" style="font-weight: 600; color: #3b82f6;">
                                                            {{ number_format($d->ratio, 2, ',', '.') }}x
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="5" style="text-align: center; color: #94a3b8; padding: 1rem;">
                                                            Tidak ada data produk.
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; color: #94a3b8; padding: 3rem;">
                                Tidak ada data perputaran persediaan untuk periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="notes-section">
            <p><strong>Keterangan:</strong></p>
            <ul style="margin-top: 0.5rem; list-style-type: disc; margin-left: 1.5rem;">
                <li><strong>Stok Rata-rata</strong> = (Stok Awal + Stok Akhir) / 2</li>
                <li><strong>Rasio Perputaran</strong> = Qty Terjual / Stok Rata-rata. Semakin tinggi rasio, semakin efisien pengelolaan stok.</li>
                <li><strong>Durasi Simpan</strong> = Periode Hari / Rasio Perputaran. Menunjukkan rata-rata hari barang tinggal di gudang sebelum terjual.</li>
            </ul>
        </div>
    </div>
</x-filament-panels::page>
