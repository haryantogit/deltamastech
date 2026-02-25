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
            left: 0.75rem; top: 50%; transform: translateY(-50%);
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

        .asset-section {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
            margin-bottom: 2rem;
        }

        .dark .asset-section {
            background: #111827;
            border-color: #374151;
        }

        .asset-header {
            background: #f8fafc;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .dark .asset-header {
            background: #1f2937;
            border-bottom-color: #374151;
        }

        .asset-title {
            font-size: 0.85rem;
            font-weight: 700;
            color: #2563eb;
            text-decoration: none;
        }

        .asset-title:hover {
            text-decoration: underline;
        }

        .dark .asset-title {
            color: #60a5fa;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
        }

        .report-table th {
            padding: 0.75rem 1.25rem;
            font-size: 0.7rem;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            text-align: left;
            border-bottom: 1px solid #f1f5f9;
        }

        .dark .report-table th {
            border-bottom-color: #374151;
        }

        .report-table td {
            padding: 0.75rem 1.25rem;
            font-size: 0.75rem;
            color: #1e293b;
            border-bottom: 1px solid #f8fafc;
        }

        .dark .report-table td {
            color: #e2e8f0;
            border-bottom-color: rgba(255, 255, 255, 0.05);
        }

        .number-col {
            text-align: right !important;
        }

        .asset-footer {
            background: #fcfcfc;
            padding: 0.75rem 1.25rem;
            font-weight: 700;
            font-size: 0.8rem;
            color: #1e293b;
            display: flex;
            justify-content: space-between;
            border-top: 1px solid #f1f5f9;
        }

        .dark .asset-footer {
            background: #111827;
            color: #f1f5f9;
            border-top-color: #374151;
        }

        .footer-totals {
            display: flex;
            gap: 3rem;
        }

        @media print {
            .report-toolbar, .fi-header-actions {
                display: none !important;
            }
            .asset-section {
                border: 1px solid #000 !important;
                break-inside: avoid;
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
                    Periode {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} —
                    {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
                </div>
            </div>
        </div>

        @forelse($data['reportData'] as $asset)
            <div class="asset-section">
                <div class="asset-header">
                    <a href="{{ \App\Filament\Resources\FixedAssetResource::getUrl('view', ['record' => $asset->id]) }}" class="asset-title">
                        {{ $asset->name }} - {{ $asset->sku }}
                    </a>
                </div>
                <table class="report-table">
                    <thead>
                        <tr>
                            <th style="width: 150px;">Tanggal</th>
                            <th>Referensi</th>
                            <th class="number-col" style="width: 180px;">Debit</th>
                            <th class="number-col" style="width: 180px;">Kredit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($asset->ledger as $row)
                            <tr>
                                <td style="color: #64748b;">{{ \Carbon\Carbon::parse($row->date)->format('d/m/Y') }}</td>
                                <td>{{ $row->reference }}</td>
                                <td class="number-col">{{ $row->debit > 0 ? number_format($row->debit, 0, ',', '.') : '' }}</td>
                                <td class="number-col" style="color: #ef4444;">{{ $row->credit > 0 ? number_format($row->credit, 0, ',', '.') : '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="asset-footer">
                    <div>Total</div>
                    <div class="footer-totals">
                        <div class="number-col" style="width: 120px;">{{ number_format($asset->total_debit, 0, ',', '.') }}</div>
                        <div class="number-col" style="width: 120px; color: #ef4444;">{{ number_format($asset->total_credit, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>
        @empty
            <div style="text-align: center; color: #94a3b8; padding: 4rem; background: white; border-radius: 12px; border: 1px dashed #e2e8f0;">
                <x-filament::icon icon="heroicon-o-document-magnifying-glass" class="w-12 h-12 mx-auto mb-3 opacity-20" />
                <p>Tidak ada transaksi aset tetap ditemukan pada periode ini.</p>
            </div>
        @endforelse
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>