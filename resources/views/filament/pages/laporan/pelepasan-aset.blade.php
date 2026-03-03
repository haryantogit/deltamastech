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

        .grand-total-row {
            background: #f8fafc;
            font-weight: 800;
            font-size: 0.875rem !important;
            color: #0f172a;
        }

        .dark .grand-total-row {
            background: #1f2937;
            color: #f1f5f9;
        }

        .number-col {
            text-align: right !important;
            font-variant-numeric: tabular-nums;
        }

        .asset-link {
            color: #2563eb;
            text-decoration: none;
            font-weight: 700;
        }

        .asset-link:hover {
            text-decoration: underline;
        }

        .dark .asset-link {
            color: #60a5fa;
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
            .fi-header-actions,
            .pagination-row {
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
    @endphp

    <div class="report-content">
        <div class="delivery-report-container">
            {{-- Search row --}}
            <div class="dark:border-gray-800 search-row">
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
                <div style="position: relative; width: 300px;">
                    <svg style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); width: 1.125rem; height: 1.125rem; color: #94a3b8;"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <input type="text" wire:model.live.debounce.500ms="search" placeholder="Cari Nama atau SKU Aset..."
                        style="width: 100%; padding: 0.5rem 0.75rem 0.5rem 2.25rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.8125rem; background: white; color: #1e293b; outline: none; transition: all 0.2s;"
                        class="dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 dark:focus:border-blue-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500/20">
                </div>
            </div>

            <div style="overflow-x: auto;">
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
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                                <td>
                                    <a href="{{ $asset->url }}" class="asset-link">
                                        {{ $asset->name }}
                                    </a>
                                </td>
                                <td style="color: #64748b; font-weight: 500;">{{ $asset->sku }}</td>
                                <td style="color: #64748b;">
                                    {{ \Carbon\Carbon::parse($asset->disposal_date)->format('d/m/Y') }}</td>
                                <td class="number-col">{{ number_format($asset->cost, 0, ',', '.') }}</td>
                                <td class="number-col">{{ number_format($asset->accum_dep, 0, ',', '.') }}</td>
                                <td class="number-col" style="font-weight: 600;">
                                    {{ number_format($asset->book_value, 0, ',', '.') }}</td>
                                <td class="number-col" style="font-weight: 600;">
                                    {{ number_format($asset->sale_price, 0, ',', '.') }}</td>
                                <td class="number-col"
                                    style="font-weight: 800; color: {{ $asset->gain_loss >= 0 ? '#16a34a' : '#ef4444' }}">
                                    @if($asset->gain_loss > 0) + @endif{{ number_format($asset->gain_loss, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align: center; color: #94a3b8; padding: 6rem 0;">
                                    <div
                                        style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                        <svg style="width: 64px; height: 64px; margin-bottom: 1.5rem; opacity: 0.1;"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                        <span style="font-size: 0.875rem; font-weight: 600;">Tidak ada data pelepasan aset
                                            untuk periode ini.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($data['assets']->isNotEmpty())
                        <tfoot>
                            <tr class="grand-total-row">
                                <td colspan="3" style="text-align: left; padding: 1.25rem; text-transform: uppercase;">Total
                                </td>
                                <td class="number-col">{{ number_format($data['total_cost'], 0, ',', '.') }}</td>
                                <td class="number-col">{{ number_format($data['total_accum_dep'], 0, ',', '.') }}</td>
                                <td class="number-col">{{ number_format($data['total_book_value'], 0, ',', '.') }}</td>
                                <td class="number-col">{{ number_format($data['total_sale_price'], 0, ',', '.') }}</td>
                                <td class="number-col"
                                    style="color: {{ $data['total_gain_loss'] >= 0 ? '#16a34a' : '#ef4444' }}">
                                    @if($data['total_gain_loss'] > 0) +
                                    @endif{{ number_format($data['total_gain_loss'], 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            {{-- Pagination Row --}}
            @if($data['totalCount'] > 0 && $perPage !== 'all')
                <div class="p-4 border-t border-gray-100 dark:border-gray-800 pagination-row">
                    <x-filament::pagination
                        :page-options="[5, 10, 25, 50, 'all']"
                        :current-page-option="$perPage"
                        :records-per-page-selector-label="__('filament::components/pagination.fields.records_per_page.label')"
                        :paginator="new \Illuminate\Pagination\LengthAwarePaginator($data['assets'], $data['totalCount'], $perPage, $page)"
                        class="px-3 py-3"
                    />
                </div>
            @endif
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>