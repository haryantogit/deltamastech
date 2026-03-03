@php
    $data = $this->getViewData();
    $rows = $data['rows'];
    $paginator = $data['paginator'];
    $totalCount = $data['totalCount'];
    $transactionPaginator = $data['transactionPaginator'] ?? null;

    $fmt = function ($num) {
        if ($num == 0)
            return '0';
        return number_format($num, 0, ',', '.');
    };
@endphp

<x-filament-panels::page>
    <style>
        .custom-tab-button {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 0.4rem 1.25rem;
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
            width: 1.25rem; height: 1.25rem; 
            background: rgba(59, 130, 246, 0.1); 
            border: 1px solid rgba(59, 130, 246, 0.2); 
            border-radius: 6px; 
            display: grid; place-items: center;
            line-height: 0;
            cursor: pointer; color: #3b82f6; 
            font-weight: 800; font-size: 0.95rem;
            transition: all 0.2s ease;
            user-select: none;
        }
        .expand-btn:hover { 
            background: #3b82f6; 
            color: white;
            border-color: #3b82f6;
            transform: scale(1.05);
        }
        
        /* Table Border Sync for Light Mode */
        table { border-spacing: 0; border: 1px solid #f1f5f9; border-radius: 8px; overflow: hidden; }
        .dark table { border-color: rgba(255, 255, 255, 0.05); }
        tr { border-bottom: 1px solid #f1f5f9; }
        .dark tr { border-bottom-color: rgba(255, 255, 255, 0.05); }
        thead tr { background: #f8fafc; border-bottom: 2px solid #e2e8f0; }
        .dark thead tr { background: rgba(255, 255, 255, 0.02); border-bottom-color: rgba(255, 255, 255, 0.1); }
    </style>

    <!-- Secondary Toolbar: Tabs -->
    <div style="display: flex; justify-content: flex-start; align-items: center; margin-bottom: 0.5rem; flex-wrap: wrap; gap: 1rem;">
        <div style="display: flex; align-items: center; background: rgba(128,128,128,0.04); border: 1px solid rgba(128,128,128,0.08); padding: 4px; border-radius: 8px;">
            <button wire:click="setViewMode('compact')" class="custom-tab-button {{ $viewMode === 'compact' ? 'active' : 'inactive' }}">Compact</button>
            <button wire:click="setViewMode('expanded')" class="custom-tab-button {{ $viewMode === 'expanded' ? 'active' : 'inactive' }}">Expanded</button>
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

    </x-filament::section>

    @if ($paginator->hasPages() || count([5, 10, 20, 50, 100, 'all']) > 1)
        <div style="margin-top: 2rem; margin-bottom: 1rem;">
            <x-filament::pagination
                :paginator="$paginator"
                :page-options="[5, 10, 20, 50, 100, 'all']"
                current-page-option-property="perPage"
            />
        </div>
    @endif

    <style>
        @media print { .x-filament-panels-page-header-actions, .custom-tab-button, .expand-btn, .pagination-footer { display: none !important; } }
    </style>
</x-filament-panels::page>
