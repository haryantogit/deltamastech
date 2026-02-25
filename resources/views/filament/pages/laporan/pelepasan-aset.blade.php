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

        .asset-link {
            color: #2563eb;
            text-decoration: none;
            font-weight: 500;
        }

        .asset-link:hover {
            text-decoration: underline;
        }

        .dark .asset-link {
            color: #60a5fa;
        }

        .grand-total-row {
            background: #f8fafc;
            font-weight: 800;
            font-size: 0.85rem !important;
            border-top: 2px solid #e2e8f0;
        }

        .dark .grand-total-row {
            background: #1f2937;
            border-top-color: #374151;
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
    @endphp

    <div class="report-content">
        <div class="report-toolbar">
            <div style="display: flex; gap: 0.75rem; align-items: center; flex: 1;">
                <button wire:click="mountAction('filter')" class="date-display"
                    style="cursor: pointer; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.8'"
                    onmouseout="this.style.opacity='1'">
                    <x-filament::icon icon="heroicon-m-funnel" class="w-4 h-4" />
                    Filter
                </button>
                <div class="search-container">
                    <x-filament::icon icon="heroicon-m-magnifying-glass" class="w-5 h-5 search-icon" />
                    <input type="text" wire:model.live.debounce.500ms="search" placeholder="Cari aset..."
                        class="search-input">
                </div>
            </div>

            <div style="display: flex; gap: 0.75rem; align-items: center;">
                @if ($categoryId)
                    @php $cat = \App\Models\Category::find($categoryId); @endphp
                    @if ($cat)
                        <div class="date-display" style="background: #eff6ff; color: #2563eb; border-color: #bfdbfe;">
                            Kategori: {{ $cat->name }}
                        </div>
                    @endif
                @endif
                <div class="date-display">
                    <x-filament::icon icon="heroicon-m-calendar" class="w-4 h-4" />
                    Pelepasan Periode {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} —
                    {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
                </div>
            </div>
        </div>

        <div class="report-section">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Nama Aset</th>
                        <th>Nomor</th>
                        <th>Tanggal Pelepasan</th>
                        <th class="number-col">Biaya Awal</th>
                        <th class="number-col">Akumulasi Penyusutan</th>
                        <th class="number-col">Nilai Buku</th>
                        <th class="number-col">Harga Jual</th>
                        <th class="number-col">Untung/Rugi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data['assets'] as $asset)
                        <tr>
                            <td>
                                <a href="{{ $asset->url }}" class="asset-link">
                                    {{ $asset->name }}
                                </a>
                            </td>
                            <td>{{ $asset->sku }}</td>
                            <td>{{ \Carbon\Carbon::parse($asset->disposal_date)->format('d/m/Y') }}</td>
                            <td class="number-col">{{ number_format($asset->cost, 0, ',', '.') }}</td>
                            <td class="number-col">{{ number_format($asset->accum_dep, 0, ',', '.') }}</td>
                            <td class="number-col">{{ number_format($asset->book_value, 0, ',', '.') }}</td>
                            <td class="number-col">{{ number_format($asset->sale_price, 0, ',', '.') }}</td>
                            <td class="number-col"
                                style="font-weight: 700; color: {{ $asset->gain_loss >= 0 ? '#16a34a' : '#ef4444' }}">
                                {{ number_format($asset->gain_loss, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; color: #94a3b8; padding: 3rem;">
                                Tidak ada data pelepasan aset untuk periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($data['assets']->isNotEmpty())
                    <tfoot>
                        <tr class="grand-total-row">
                            <td colspan="3" style="text-align: left; padding: 1rem;">Total</td>
                            <td class="number-col"></td>
                            <td class="number-col"></td>
                            <td class="number-col">{{ number_format($data['total_book_value'], 0, ',', '.') }}</td>
                            <td class="number-col"></td>
                            <td class="number-col"
                                style="color: {{ $data['total_gain_loss'] >= 0 ? '#16a34a' : '#ef4444' }}">
                                {{ number_format($data['total_gain_loss'], 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</x-filament-panels::page>