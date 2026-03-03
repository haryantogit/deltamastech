@php
    $viewData = $this->getViewData();
    $results = $viewData['results'];
    $paginator = $viewData['paginator'];

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
            min-width: 900px;
        }

        .report-table th {
            padding: 0.875rem 1.25rem;
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
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

        .row-hover:hover {
            background: rgba(59, 130, 246, 0.02);
        }

        .dark .row-hover:hover {
            background: rgba(255, 255, 255, 0.02);
        }

        /* Nested Table */
        .nested-row {
            background: #f8fafc;
        }

        .dark .nested-row {
            background: rgba(255, 255, 255, 0.02);
        }

        .nested-table {
            width: 100%;
            border-collapse: collapse;
        }

        .nested-table th {
            background: rgba(59, 130, 246, 0.03);
            font-size: 0.7rem;
            padding: 0.5rem 1.25rem;
            color: #64748b;
            text-transform: uppercase;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
        }

        .dark .nested-table th {
            background: rgba(255, 255, 255, 0.05);
            border-color: #374151;
        }

        .nested-table td {
            padding: 0.625rem 1.25rem;
            font-size: 0.75rem;
            border-bottom: 1px solid rgba(226, 232, 240, 0.5);
            color: #475569;
        }

        .dark .nested-table td {
            border-color: #374151;
            color: #94a3b8;
        }

        /* Toggle Button */
        .toggle-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.5rem;
            height: 1.5rem;
            border: 1px solid #3b82f6;
            border-radius: 4px;
            color: #3b82f6;
            background: #eff6ff;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.2s;
        }

        .toggle-btn:hover {
            background: #3b82f6;
            color: white;
        }

        .dark .toggle-btn {
            border-color: #3b82f6;
            color: #60a5fa;
            background: rgba(59, 130, 246, 0.1);
        }

        .dark .toggle-btn:hover {
            background: #3b82f6;
            color: white;
        }

        /* Search Box */
        .filter-search-row {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .dark .filter-search-row {
            border-color: #374151;
        }

        .custom-search-container {
            position: relative;
            width: 280px;
        }

        .search-icon-abs {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            width: 1rem;
            height: 1rem;
            color: #94a3b8;
        }

        .custom-search-input {
            width: 100%;
            padding: 0.5rem 0.75rem 0.5rem 2.25rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.8125rem;
            background: white;
            color: #1e293b;
            outline: none;
            transition: border-color 0.2s;
        }

        .dark .custom-search-input {
            background: #1f2937;
            border-color: #374151;
            color: #f1f5f9;
        }

        .custom-search-input:focus {
            border-color: #3b82f6;
        }

        @media print {

            .filter-search-row,
            .fi-header-actions,
            .mt-8 {
                display: none !important;
            }
        }
    </style>

    <div class="delivery-report-container">
        {{-- Search Row --}}
        <div class="filter-search-row">
            <div class="custom-search-container">
                <svg class="search-icon-abs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <input type="text" wire:model.live.debounce.500ms="search" placeholder="Cari"
                    class="custom-search-input">
            </div>
        </div>

        <div style="overflow-x: auto;">
            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width: 50px;"></th>
                        <th>PELANGGAN</th>
                        <th>PERUSAHAAN</th>
                        <th style="text-align: right; width: 220px;">TOTAL KUANTITAS PRODUK</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($results as $row)
                        @php $isExpanded = in_array($row->id, $expandedContacts); @endphp
                        <tr class="row-hover cursor-pointer" wire:click="toggleContact({{ $row->id }})">
                            <td style="text-align: center;">
                                <button class="toggle-btn">
                                    {{ $isExpanded ? '−' : '+' }}
                                </button>
                            </td>
                            <td style="font-weight: 600; color: #3b82f6;">{{ $row->contact_name }}</td>
                            <td>{{ $row->company ?? '-' }}</td>
                            <td style="text-align: right; font-weight: 700;">
                                {{ number_format($row->total_qty, 0, ',', '.') }}
                            </td>
                        </tr>

                        @if($isExpanded)
                            <tr class="nested-row">
                                <td colspan="4" style="padding: 1.25rem 2rem;">
                                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                                        <table class="nested-table">
                                            <thead>
                                                <tr>
                                                    <th>PRODUK</th>
                                                    <th>KODE</th>
                                                    <th style="text-align: right;">KUANTITAS</th>
                                                    <th style="text-align: right;">HARGA JUAL (RATA-RATA)</th>
                                                    <th style="text-align: right;">TOTAL HARGA</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($row->products as $item)
                                                    <tr>
                                                        <td style="font-weight: 600; color: #3b82f6;">{{ $item->product_name }}</td>
                                                        <td style="font-family: monospace; color: #64748b;">{{ $item->sku }}</td>
                                                        <td style="text-align: right;">{{ number_format($item->qty, 0, ',', '.') }}
                                                        </td>
                                                        <td style="text-align: right;">
                                                            {{ number_format($item->avg_price, 0, ',', '.') }}
                                                        </td>
                                                        <td style="text-align: right; font-weight: 700; color: #1e293b;"
                                                            class="dark:text-white">
                                                            {{ number_format($item->total_price, 0, ',', '.') }}
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="5" style="text-align: center; color: #94a3b8; padding: 2rem;">
                                                            Tidak ada detail produk.
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
                            <td colspan="4" style="text-align: center; padding: 4rem; color: #94a3b8;">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 mb-3 opacity-20" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 002-2h2a2 2 0 002-2">
                                        </path>
                                    </svg>
                                    <span>Tidak ada data untuk periode ini.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($paginator->hasPages() || count([5, 10, 20, 50, 100, 'all']) > 1)
        <div style="margin-top: 2rem; margin-bottom: 1rem;">
            <x-filament::pagination :paginator="$paginator" :page-options="[5, 10, 20, 50, 100, 'all']"
                current-page-option-property="perPage" />
        </div>
    @endif

    <x-filament-actions::modals />
</x-filament-panels::page>