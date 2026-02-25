@php
    $data = $this->getViewData();
    $rows = $data['rows'];
    $paginator = $data['paginator'];
    $totalCount = $data['totalCount'];
    $transactionPaginator = $data['transactionPaginator'] ?? null;

    $fmt = function ($num) {
        if ($num == 0) return '0';
        return number_format($num, 0, ',', '.');
    };
@endphp

<x-filament-panels::page>
    <style>
        .custom-tab-button {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 0.5rem 1.5rem;
            border-radius: 6px;
            font-size: 0.8125rem;
            font-weight: 700;
            cursor: pointer;
            border: none;
        }
        .custom-tab-button.active {
            background-color: #3b82f6;
            color: white;
            box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.2), 0 2px 4px -1px rgba(59, 130, 246, 0.1);
        }
        .custom-tab-button.inactive {
            background-color: transparent;
            color: #64748b;
        }
        .custom-tab-button.inactive:hover {
            color: #3b82f6;
            background-color: rgba(59, 130, 246, 0.05);
        }

        .expand-btn {
            width: 1.1rem; height: 1.1rem; 
            background: #1e293b; border: 1px solid rgba(255,255,255,0.1); 
            border-radius: 4px; display: flex; align-items: center; justify-content: center; 
            cursor: pointer; color: #3b82f6; font-weight: bold; font-family: monospace;
        }
        .expand-btn:hover { border-color: #3b82f6; }
        
        /* FILAMENT TABLE FOOTER STYLE - Exact Sync */
        .pagination-footer {
            margin-top: 1rem;
            padding: 1rem 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            border-top: 1px solid rgba(255,255,255,0.05);
        }

        .pagination-status {
            font-size: 0.8125rem;
            color: #94a3b8;
            font-weight: 500;
        }

        /* Center Per-Page Selector Capsule */
        .per-page-capsule {
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 0 0.75rem;
            height: 2.25rem;
            gap: 0;
        }
        .per-page-label {
            font-size: 0.75rem;
            color: #94a3b8;
            font-weight: 500;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            padding-right: 0.75rem;
            height: 100%;
            display: flex;
            align-items: center;
        }
        .per-page-capsule select {
            background: transparent;
            border: none;
            font-size: 0.8125rem;
            font-weight: 600;
            color: #f1f5f9;
            outline: none;
            cursor: pointer;
            padding: 0 0.5rem 0 0.75rem;
            margin: 0;
            appearance: none;
            -webkit-appearance: none;
        }

        /* Right Numeric Pagination Capsule */
        .numeric-capsule nav { display: flex; align-items: center; }
        .numeric-capsule nav > div:first-child { display: none !important; }
        .numeric-capsule nav p { display: none !important; }
        
        .numeric-capsule nav div:last-child {
            display: flex !important;
            background: rgba(255, 255, 255, 0.03) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 10px !important;
            overflow: hidden !important;
        }
        .numeric-capsule a, .numeric-capsule span {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            min-width: 2.5rem !important;
            height: 2.25rem !important;
            padding: 0 0.75rem !important;
            font-size: 0.8125rem !important;
            font-weight: 600 !important;
            color: #f1f5f9 !important;
            border: none !important;
            border-right: 1px solid rgba(255, 255, 255, 0.1) !important;
            background: transparent !important;
            transition: all 0.2s !important;
            text-decoration: none !important;
        }
        .numeric-capsule div:last-child > :last-child { border-right: none !important; }
        
        .numeric-capsule a:hover { background: rgba(255, 255, 255, 0.05) !important; color: #3b82f6 !important; }
        
        .numeric-capsule .active span { 
            background: rgba(59, 130, 246, 0.1) !important; 
            color: #3b82f6 !important; 
            font-weight: 700 !important;
        }

        .numeric-capsule [aria-disabled="true"] span {
            color: #475569 !important;
            cursor: default !important;
        }
        
        /* Dark mode specific tweak if needed */
        .dark .per-page-capsule { background: rgba(0,0,0,0.2); }
        .dark .numeric-capsule nav div:last-child { background: rgba(0,0,0,0.2); }
    </style>

    <!-- Secondary Toolbar: Tabs & Range -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div style="display: flex; align-items: center; background: rgba(128,128,128,0.04); border: 1px solid rgba(128,128,128,0.08); padding: 4px; border-radius: 8px;">
            <button wire:click="setViewMode('compact')" class="custom-tab-button {{ $viewMode === 'compact' ? 'active' : 'inactive' }}">Compact</button>
            <button wire:click="setViewMode('expanded')" class="custom-tab-button {{ $viewMode === 'expanded' ? 'active' : 'inactive' }}">Expanded</button>
        </div>

        <div style="display: flex; align-items: center; gap: 0.5rem; background: rgba(128,128,128,0.04); padding: 0.5rem 1rem; border-radius: 8px; border: 1px solid rgba(128,128,128,0.08);">
            <x-heroicon-o-calendar style="width: 1rem; height: 1rem; color: #94a3b8;"/>
            <div style="font-size: 0.8125rem; font-weight: 600; color: #64748b;" class="dark:text-gray-400">
                {{ Carbon\Carbon::parse($startDate)->format('d/m/Y') }} — {{ Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
            </div>
        </div>
    </div>

    <!-- Main Table Section -->
    <x-filament::section>
        <div style="width: 100%; overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    @if($viewMode === 'compact')
                        <tr style="border-bottom: 1.5px solid rgba(128,128,128,0.2);">
                            @php $thBase = 'padding: 1rem; font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-align: left; text-transform: uppercase; letter-spacing: 0.025em;'; @endphp
                            <th style="{{ $thBase }}">Nama</th>
                            <th style="{{ $thBase }} text-align: right;">Total Debit</th>
                            <th style="{{ $thBase }} text-align: right;">Total Kredit</th>
                            <th style="{{ $thBase }} text-align: right;">Saldo</th>
                        </tr>
                    @else
                        <tr style="border-bottom: 1.5px solid rgba(128,128,128,0.2);">
                            @php $thBase = 'padding: 0.875rem 1rem; font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-align: left; text-transform: uppercase; letter-spacing: 0.025em;'; @endphp
                            <th style="{{ $thBase }}">Tanggal</th>
                            <th style="{{ $thBase }}">Sumber</th>
                            <th style="{{ $thBase }}">Deskripsi</th>
                            <th style="{{ $thBase }}">Referensi</th>
                            <th style="{{ $thBase }}">Nomor</th>
                            <th style="{{ $thBase }} text-align: right;">Debit</th>
                            <th style="{{ $thBase }} text-align: right;">Kredit</th>
                            <th style="{{ $thBase }} text-align: right;">Saldo Berjalan</th>
                        </tr>
                    @endif
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @if($viewMode === 'compact')
                        @foreach ($rows as $row)
                            @php $isExpanded = in_array($row['id'], $this->expandedRows); @endphp
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02] transition-colors">
                                <td style="padding: 1rem; font-size: 0.875rem; font-weight: 600;">
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <div wire:click="toggleRow({{ $row['id'] }})" class="expand-btn">
                                            {{ $isExpanded ? '-' : '+' }}
                                        </div>
                                        <span class="text-gray-900 dark:text-blue-400">{{ $row['name'] }} ({{ $row['code'] }})</span>
                                    </div>
                                </td>
                                <td style="padding: 1rem; font-size: 0.875rem; text-align: right; font-weight: 600;" class="text-gray-900 dark:text-gray-100">{{ $fmt($row['debit']) }}</td>
                                <td style="padding: 1rem; font-size: 0.875rem; text-align: right; font-weight: 600;" class="text-gray-900 dark:text-gray-100">{{ $fmt($row['credit']) }}</td>
                                <td style="padding: 1rem; font-size: 0.875rem; text-align: right; font-weight: 600;" class="text-emerald-600 dark:text-emerald-400">{{ $fmt($row['saldo']) }}</td>
                            </tr>
                            @if ($isExpanded)
                                <tr>
                                    <td colspan="4" style="padding: 0 1rem 1.5rem 2.8rem;">
                                        @if (!empty($row['transactions']))
                                            <table style="width: 100%; border-collapse: collapse;">
                                                <thead>
                                                    <tr style="border-bottom: 1px solid rgba(128,128,128,0.1);">
                                                        @php $subTh = 'padding: 0.6rem 0.75rem; font-size: 0.7rem; font-weight: 700; color: #94a3b8; text-align: left; text-transform: uppercase;'; @endphp
                                                        <th style="{{ $subTh }}">Tanggal</th>
                                                        <th style="{{ $subTh }}">Sumber</th>
                                                        <th style="{{ $subTh }}">Nomor</th>
                                                        <th style="{{ $subTh }}">Deskripsi</th>
                                                        <th style="{{ $subTh }} text-align: right;">Debit</th>
                                                        <th style="{{ $subTh }} text-align: right;">Kredit</th>
                                                        <th style="{{ $subTh }} text-align: right;">Saldo</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @if ($transactionPaginator->onFirstPage())
                                                        <tr style="border-bottom: 1px solid rgba(128,128,128,0.05);">
                                                            <td colspan="4" style="padding: 0.6rem 0.75rem; font-size: 0.75rem; font-style: italic;" class="text-gray-400">Saldo Awal</td>
                                                            <td style="padding: 0.6rem 0.75rem; font-size: 0.75rem; text-align: right;" class="text-gray-500">{{ $row['openingBalance'] > 0 ? $fmt(abs($row['openingBalance'])) : '0' }}</td>
                                                            <td style="padding: 0.6rem 0.75rem; font-size: 0.75rem; text-align: right;" class="text-gray-500">{{ $row['openingBalance'] < 0 ? $fmt(abs($row['openingBalance'])) : '0' }}</td>
                                                            <td style="padding: 0.6rem 0.75rem; font-size: 0.75rem; text-align: right; font-weight: 600;" class="text-gray-700 dark:text-gray-300">{{ $fmt($row['openingBalance']) }}</td>
                                                        </tr>
                                                    @endif
                                                    @foreach ($row['transactions'] as $trx)
                                                        <tr style="border-bottom: 1px solid rgba(128,128,128,0.05);">
                                                            <td style="padding: 0.6rem 0.75rem; font-size: 0.75rem;" class="text-gray-600 dark:text-gray-400">{{ $trx['tanggal'] }}</td>
                                                            <td style="padding: 0.6rem 0.75rem; font-size: 0.75rem;"><span class="text-blue-600 dark:text-blue-400">{{ $trx['sumber'] }}</span></td>
                                                            <td style="padding: 0.6rem 0.75rem; font-size: 0.75rem; font-family: monospace;" class="text-gray-600 dark:text-gray-400">{{ $trx['nomor'] }}</td>
                                                            <td style="padding: 0.6rem 0.75rem; font-size: 0.75rem;" class="text-gray-600 dark:text-gray-400">{{ $trx['deskripsi'] }}</td>
                                                            <td style="padding: 0.6rem 0.75rem; font-size: 0.75rem; text-align: right;" class="text-gray-900 dark:text-gray-300">{{ $fmt($trx['debit']) }}</td>
                                                            <td style="padding: 0.6rem 0.75rem; font-size: 0.75rem; text-align: right;" class="text-gray-900 dark:text-gray-300">{{ $fmt($trx['kredit']) }}</td>
                                                            <td style="padding: 0.6rem 0.75rem; font-size: 0.75rem; text-align: right; font-weight: 600;" class="text-gray-900 dark:text-gray-100">{{ $fmt($trx['saldo']) }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                            @if ($transactionPaginator->hasPages())
                                                <div style="margin-top: 1rem;" class="numeric-capsule">
                                                    {{ $transactionPaginator->links() }}
                                                </div>
                                            @endif
                                        @else
                                            <div style="padding: 1rem; font-size: 0.75rem; color: #94a3b8; font-style: italic;">Tidak ada rincian transaksi.</div>
                                        @endif
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    @else
                        @php $currentAccountCode = null; @endphp
                        @foreach ($rows as $row)
                            @if ($currentAccountCode !== $row['account_code'])
                                @php $currentAccountCode = $row['account_code']; @endphp
                                <tr style="background: rgba(128,128,128,0.03);">
                                    <td colspan="8" style="padding: 1rem; font-size: 0.9375rem; font-weight: 700; background-color: rgba(59, 130, 246, 0.05);" class="text-gray-900 dark:text-white">
                                        {{ $row['account_name'] }} ({{ $row['account_code'] }})
                                    </td>
                                </tr>
                            @endif
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02] transition-colors">
                                <td style="padding: 0.75rem 1rem; font-size: 0.8125rem;" class="text-gray-600 dark:text-gray-400">{{ $row['tanggal'] }}</td>
                                <td style="padding: 0.75rem 1rem; font-size: 0.8125rem;" class="text-blue-600 dark:text-blue-400">{{ $row['sumber'] }}</td>
                                <td style="padding: 0.75rem 1rem; font-size: 0.8125rem; max-width: 300px;" class="text-gray-600 dark:text-gray-400">{{ $row['deskripsi'] }}</td>
                                <td style="padding: 0.75rem 1rem; font-size: 0.8125rem;" class="text-gray-600 dark:text-gray-400">{{ $row['referensi'] }}</td>
                                <td style="padding: 0.75rem 1rem; font-size: 0.8125rem; font-family: monospace;" class="text-gray-600 dark:text-gray-400">{{ $row['nomor'] }}</td>
                                <td style="padding: 0.75rem 1rem; font-size: 0.8125rem; text-align: right;" class="text-gray-900 dark:text-gray-300">{{ $fmt($row['debit']) }}</td>
                                <td style="padding: 0.75rem 1rem; font-size: 0.8125rem; text-align: right;" class="text-gray-900 dark:text-gray-300">{{ $fmt($row['kredit']) }}</td>
                                <td style="padding: 0.75rem 1rem; font-size: 0.8125rem; text-align: right; font-weight: 600;" class="text-gray-900 dark:text-gray-100">{{ $fmt($row['saldo_berjalan']) }}</td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
                @if($viewMode === 'compact')
                    <tfoot style="background: rgba(128,128,128,0.05);" class="dark:bg-white/[0.02]">
                        <tr style="border-top: 1.5px solid rgba(128,128,128,0.2);">
                            <td style="padding: 1rem; font-size: 0.875rem; font-weight: 700; text-transform: uppercase;" class="text-gray-500">Total Keseluruhan</td>
                            <td style="padding: 1rem; font-size: 0.875rem; text-align: right; font-weight: 700;" class="text-gray-900 dark:text-gray-100">{{ $fmt($data['grandTotalDebit']) }}</td>
                            <td style="padding: 1rem; font-size: 0.875rem; text-align: right; font-weight: 700;" class="text-gray-900 dark:text-gray-100">{{ $fmt($data['grandTotalCredit']) }}</td>
                            <td style="padding: 1rem; font-size: 1rem; text-align: right; font-weight: 800;" class="text-emerald-600 dark:text-emerald-400">{{ $fmt($data['grandTotalDebit'] - $data['grandTotalCredit']) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        <div class="pagination-footer">
            <!-- Left: Status Text -->
             <div class="pagination-status">
                Menampilkan {{ $paginator->firstItem() }} sampai {{ $paginator->lastItem() }} dari {{ number_format($totalCount, 0, ',', '.') }} hasil
            </div>

            <!-- Center: Per Page Capsule - Refined Sync -->
            <div class="per-page-capsule">
                <span class="per-page-label">per halaman</span>
                <select wire:model.live="perPage">
                    <option value="15">15</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="500">500</option>
                </select>
                <x-heroicon-m-chevron-down style="width: 1rem; height: 1rem; color: #64748b; margin-left: -0.25rem;" />
            </div>

            <!-- Right: Numeric Links - Refined Sync -->
             <div class="numeric-capsule">
                 {{ $paginator->links() }}
             </div>
        </div>
    </x-filament::section>

    <style>
        @media print { .x-filament-panels-page-header-actions, .custom-tab-button, .expand-btn, .pagination-footer { display: none !important; } }
    </style>
</x-filament-panels::page>