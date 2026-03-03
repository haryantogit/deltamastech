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

        .category-row {
            background: #f8fafc;
        }

        .dark .category-row {
            background: rgba(255, 255, 255, 0.05);
        }

        .category-name {
            font-weight: 800;
            color: #0f172a;
            font-size: 0.9375rem;
            padding: 1rem 1.25rem !important;
            border-bottom: 2px solid #e2e8f0 !important;
        }

        .dark .category-name {
            color: #f1f5f9;
            border-bottom-color: #374151 !important;
        }

        .subtotal-row {
            background: #fdfdfd;
            font-weight: 700;
        }

        .dark .subtotal-row {
            background: rgba(255, 255, 255, 0.02);
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

        .date-badge {
            display: flex;
            align-items: center;
            padding: 0.5rem 1rem;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            font-size: 0.875rem;
            font-weight: 600;
            color: #475569;
            gap: 0.5rem;
        }

        .dark .date-badge {
            background: #1e293b;
            border-color: #334155;
            color: #94a3b8;
        }

        @media print {
            .search-row, .fi-header-actions, .pagination-row {
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
            <div style="padding: 1rem 1.25rem; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;"
                class="dark:border-gray-800 search-row">
                <div>
                    @if ($categoryId)
                        @php $cat = \App\Models\Category::find($categoryId); @endphp
                        @if ($cat)
                            <div style="font-size: 0.75rem; font-weight: 700; color: #3b82f6; text-transform: uppercase; background: #eff6ff; padding: 0.25rem 0.75rem; border-radius: 9999px; border: 1px solid #dbeafe; display: inline-block;">
                                Kategori: {{ $cat->name }}
                            </div>
                        @endif
                    @endif
                </div>
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
                            <th>Nama Aset</th>
                            <th>Nomor</th>
                            <th>Referensi</th>
                            <th>Tanggal Pembelian</th>
                            <th class="number-col">Harga Pembelian</th>
                            <th>Masa Manfaat</th>
                            <th class="number-col">Penyusutan</th>
                            <th class="number-col">Nilai Buku</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data['groupedData'] as $group)
                            <tr class="category-row">
                                <td colspan="8" class="category-name">
                                    {{ $group->name }}
                                </td>
                            </tr>

                            @foreach($group->items as $item)
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                                    <td>
                                        <a href="{{ $item->url }}" class="asset-link">
                                            {{ $item->name }}
                                        </a>
                                    </td>
                                    <td style="color: #64748b; font-weight: 500;">{{ $item->number }}</td>
                                    <td style="color: #64748b;">{{ $item->reference }}</td>
                                    <td style="color: #64748b;">{{ \Carbon\Carbon::parse($item->purchase_date)->format('d/m/Y') }}</td>
                                    <td class="number-col">{{ number_format($item->purchase_price, 0, ',', '.') }}</td>
                                    <td style="color: #64748b;">{{ $item->useful_life }}</td>
                                    <td class="number-col" style="color: #64748b;">{{ number_format($item->depreciation, 2, ',', '.') }}</td>
                                    <td class="number-col" style="font-weight: 700; color: #1e293b;">{{ number_format($item->book_value, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach

                            <tr class="subtotal-row">
                                <td colspan="4" style="text-align: left; padding: 1rem 1.25rem; color: #64748b;">Total {{ $group->name }}</td>
                                <td class="number-col">{{ number_format($group->total_purchase, 0, ',', '.') }}</td>
                                <td></td>
                                <td class="number-col">{{ number_format($group->total_depreciation, 2, ',', '.') }}</td>
                                <td class="number-col">{{ number_format($group->total_book_value, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align: center; color: #94a3b8; padding: 5rem 0;">
                                    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%;">
                                        <svg style="width: 48px; height: 48px; margin-bottom: 1rem; opacity: 0.2;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                        <span style="font-size: 0.875rem; font-weight: 500;">Tidak ada data aset tetap untuk periode ini.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($data['groupedData']->isNotEmpty())
                        <tfoot>
                            <tr class="grand-total-row">
                                <td colspan="4" style="text-align: left; padding: 1.25rem; text-transform: uppercase;">Grand Total</td>
                                <td class="number-col">{{ number_format($data['grand_total_purchase'], 0, ',', '.') }}</td>
                                <td></td>
                                <td class="number-col">{{ number_format($data['grand_total_depreciation'], 2, ',', '.') }}</td>
                                <td class="number-col">{{ number_format($data['grand_total_book_value'], 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
