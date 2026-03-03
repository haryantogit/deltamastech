<x-filament-panels::page>
    <style>
        .delivery-report-container {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
            margin-bottom: 2rem;
        }

        .dark .delivery-report-container {
            background: #111827;
            border-color: #374151;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
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

        .asset-header {
            background: #f8fafc;
            padding: 1.25rem;
            border-bottom: 2px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .dark .asset-header {
            background: rgba(255, 255, 255, 0.05);
            border-bottom-color: #374151;
        }

        .asset-name {
            font-weight: 800;
            color: #2563eb;
            font-size: 0.9375rem;
            text-decoration: none;
        }

        .asset-name:hover {
            text-decoration: underline;
        }

        .dark .asset-name {
            color: #60a5fa;
        }

        .asset-sku {
            color: #64748b;
            font-weight: 600;
            font-size: 0.8125rem;
        }

        .asset-footer {
            background: #f8fafc;
            padding: 1rem 1.25rem;
            font-weight: 800;
            font-size: 0.875rem;
            color: #0f172a;
            display: flex;
            justify-content: space-between;
            border-top: 1px solid #e2e8f0;
        }

        .dark .asset-footer {
            background: #1f2937;
            color: #f1f5f9;
            border-top-color: #374151;
        }

        .number-col {
            text-align: right !important;
            font-variant-numeric: tabular-nums;
        }

        .credit-amt {
            color: #ef4444;
        }

        .dark .credit-amt {
            color: #f87171;
        }

        .search-row {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .dark .search-row {
            border-color: #374151;
        }

        @media print {

            .search-row,
            .fi-header-actions {
                display: none !important;
            }

            .delivery-report-container {
                border: 1px solid #000 !important;
                box-shadow: none !important;
                break-inside: avoid;
            }
        }
    </style>

    @php
        $data = $this->getViewData();
    @endphp

    <div class="report-content">
        {{-- Unified search row --}}
        <div style="background: white; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 1.5rem; padding: 1rem 1.25rem;"
            class="dark:bg-gray-900 dark:border-gray-800 search-row">
            <div>
                @if ($categoryId)
                    @php $cat = \App\Models\Category::find($categoryId); @endphp
                    @if ($cat)
                        <div
                            style="font-size: 0.75rem; font-weight: 700; color: #3b82f6; text-transform: uppercase; background: #eff6ff; padding: 0.25rem 0.75rem; border-radius: 9999px; border: 1px solid #dbeafe; display: inline-block;">
                            Kategori: {{ $cat->name }}
                        </div>
                    @endif
                @endif
            </div>
            <div style="position: relative; width: 400px;">
                <svg style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); width: 1.25rem; height: 1.25rem; color: #94a3b8;"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <input type="text" wire:model.live.debounce.500ms="search"
                    placeholder="Cari Nama Aset atau Nomor SKU..."
                    style="width: 100%; padding: 0.625rem 0.75rem 0.625rem 2.5rem; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 0.875rem; background: white; color: #1e293b; outline: none; transition: all 0.2s;"
                    class="dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 dark:focus:border-blue-500 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10">
            </div>
        </div>

        @forelse($data['reportData'] as $asset)
            <div class="delivery-report-container">
                <div class="asset-header">
                    <a href="{{ \App\Filament\Resources\FixedAssetResource::getUrl('view', ['record' => $asset->id]) }}"
                        class="asset-name">
                        {{ $asset->name }}
                    </a>
                    <span class="asset-sku">{{ $asset->sku }}</span>
                </div>
                <div style="overflow-x: auto;">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th style="width: 180px;">Tanggal</th>
                                <th>Referensi</th>
                                <th class="number-col" style="width: 200px;">Debit</th>
                                <th class="number-col" style="width: 200px;">Kredit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($asset->ledger as $row)
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                                    <td style="color: #64748b;">{{ \Carbon\Carbon::parse($row->date)->format('d/m/Y') }}</td>
                                    <td style="font-weight: 500;">{{ $row->reference }}</td>
                                    <td class="number-col" style="font-weight: 600;">
                                        {{ $row->debit > 0 ? number_format($row->debit, 0, ',', '.') : '' }}</td>
                                    <td class="number-col credit-amt" style="font-weight: 600;">
                                        {{ $row->credit > 0 ? number_format($row->credit, 0, ',', '.') : '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="asset-footer">
                    <span>Total</span>
                    <div style="display: flex; gap: 0;">
                        <div class="number-col" style="width: 200px; padding-right: 1.25rem;">
                            {{ number_format($asset->total_debit, 0, ',', '.') }}
                        </div>
                        <div class="number-col credit-amt" style="width: 200px; padding-right: 1.25rem;">
                            {{ number_format($asset->total_credit, 0, ',', '.') }}
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div style="padding: 6rem 0; background: white; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; flex-direction: column; align-items: center; justify-content: center;"
                class="dark:bg-gray-900 dark:border-gray-800">
                <svg style="width: 64px; height: 64px; margin-bottom: 1.5rem; opacity: 0.1;" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <div style="text-align: center;">
                    <h3 style="font-size: 1.125rem; font-weight: 700; color: #1e293b; margin-bottom: 0.5rem;"
                        class="dark:text-gray-100">
                        Tidak Ada Data Transaksi
                    </h3>
                    <p style="color: #94a3b8; font-size: 0.875rem;">
                        Tidak ditemukan transaksi aset tetap pada kriteria pencarian atau periode ini.
                    </p>
                </div>
            </div>
        @endforelse
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>