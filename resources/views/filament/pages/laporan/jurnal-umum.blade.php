@php
    $data = $this->getViewData();
    $entries = $data['entries'];
    $paginator = $data['paginator'];
    $stats = $data['stats'];
    $totalCount = $data['totalCount'];
    $totalDebit = $data['totalDebit'];
    $totalCredit = $data['totalCredit'];
    $difference = $totalDebit - $totalCredit;

    $fmt = function ($num) {
        if ($num == 0)
            return '0';
        return number_format($num, 0, ',', '.');
    };
@endphp

<x-filament-panels::page>
    <style>
        /* Neraca-style Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.25rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid #e2e8f0;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 130px;
        }

        .dark .stat-card {
            background: #111827;
            border-color: #374151;
        }

        .stat-label {
            font-size: 0.8125rem;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 0.5rem;
        }

        .dark .stat-label {
            color: #94a3b8;
        }

        .stat-value {
            font-size: 1.875rem;
            font-weight: 800;
            color: #1e293b;
            line-height: 1;
        }

        .dark .stat-value {
            color: white;
        }

        .stat-footer {
            margin-top: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8125rem;
            font-weight: 500;
        }

        .trend-badge {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-weight: 700;
        }

        .trend-success {
            color: #10b981;
        }

        .trend-danger {
            color: #ef4444;
        }

        .stat-comparison {
            color: #94a3b8;
        }

        /* Journal Sync to Neraca Section Styling */
        .journal-container {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .journal-entry-block {
            background: white;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        .dark .journal-entry-block {
            background: #111827;
            border-color: #374151;
        }

        /* Sync Header style with Neraca "Aset" Segment Header */
        .entry-header {
            padding: 1.25rem 1.5rem;
            background: rgba(59, 130, 246, 0.08);
            /* EXACT Neraca Aset BG */
            border-top: 3px solid rgba(59, 130, 246, 0.3);
            /* EXACT Neraca Aset Border */
            border-bottom: 1px solid rgba(59, 130, 246, 0.1);
        }

        /* For entries that feel more like liabilities (optional fallback), but usually blue is standard for main reports */

        .entry-title {
            font-size: 0.9375rem;
            font-weight: 700;
            color: #3b82f6;
            /* EXACT Neraca Blue */
            margin-bottom: 0.5rem;
        }

        .entry-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.75rem;
            color: #64748b;
        }

        .dark .entry-meta {
            color: #94a3b8;
        }

        .entry-meta-left {
            display: flex;
            gap: 1.5rem;
        }

        .journal-table {
            width: 100%;
            border-collapse: collapse;
        }

        .journal-table th {
            text-align: left;
            padding: 0.75rem 1.5rem;
            font-size: 0.75rem;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            border-bottom: 1px solid #f1f5f9;
        }

        .dark .journal-table th {
            border-bottom-color: #374151;
        }

        .item-row {
            border-bottom: 1px solid rgba(0, 0, 0, 0.02);
        }

        .dark .item-row {
            border-bottom-color: rgba(255, 255, 255, 0.03);
        }

        .cell-account {
            padding: 1rem 1.5rem;
            width: 60%;
        }

        .account-header {
            font-weight: 700;
            color: #1e293b;
            font-size: 0.8125rem;
        }

        .dark .account-header {
            color: #cbd5e1;
        }

        .account-code {
            color: #94a3b8;
            font-weight: 500;
            margin-left: 0.5rem;
        }

        /* Account numbers also blue like Neraca values */
        .cell-amount {
            padding: 1rem 1.5rem;
            text-align: right;
            font-weight: 600;
            font-size: 0.875rem;
            color: #3b82f6;
            /* Sync with Neraca main value color */
        }

        .amount-zero {
            color: #cbd5e1;
            font-weight: 400;
        }

        .dark .amount-zero {
            color: #4b5563;
        }

        /* Entry Footer sync to segment total in Neraca */
        .entry-footer {
            padding: 1rem 1.5rem;
            background: rgba(59, 130, 246, 0.05);
            /* Subtle sync with segment total bg */
            display: flex;
            justify-content: flex-end;
            gap: 3rem;
            font-weight: 800;
            font-size: 0.9375rem;
            border-top: 2px solid rgba(59, 130, 246, 0.2);
        }

        .footer-total {
            color: #3b82f6;
        }

        .pagination-footer {
            margin-top: 2rem;
            padding: 1rem 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            border-top: 1px solid rgba(128, 128, 128, 0.1);
        }

        .pagination-status {
            font-size: 0.8125rem;
            color: #64748b;
            /* Light theme */
            font-weight: 500;
        }

        .dark .pagination-status {
            color: #94a3b8;
        }

        /* Center Per-Page Selector Capsule */
        .per-page-capsule {
            display: flex;
            align-items: center;
            background: rgba(0, 0, 0, 0.02);
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 0 0.75rem;
            height: 2.25rem;
            gap: 0;
        }

        .dark .per-page-capsule {
            background: rgba(255, 255, 255, 0.03);
            border-color: rgba(255, 255, 255, 0.1);
        }

        .per-page-label {
            font-size: 0.75rem;
            color: #64748b;
            font-weight: 500;
            border-right: 1px solid #e2e8f0;
            padding-right: 0.75rem;
            height: 100%;
            display: flex;
            align-items: center;
        }

        .dark .per-page-label {
            color: #94a3b8;
            border-right-color: rgba(255, 255, 255, 0.1);
        }

        .per-page-capsule select {
            background: transparent;
            border: none;
            font-size: 0.8125rem;
            font-weight: 600;
            color: #1e293b;
            outline: none;
            cursor: pointer;
            padding: 0 0.5rem 0 0.75rem;
            margin: 0;
            appearance: none;
            -webkit-appearance: none;
        }

        .dark .per-page-capsule select {
            color: #f1f5f9;
        }

        /* Right Numeric Pagination Capsule */
        .numeric-capsule nav {
            display: flex;
            align-items: center;
        }

        .numeric-capsule nav>div:first-child {
            display: none !important;
        }

        /* Hide standard p (Showing X to Y) */
        .numeric-capsule nav p {
            display: none !important;
        }

        .numeric-capsule nav div:last-child {
            display: flex !important;
            background: rgba(0, 0, 0, 0.02) !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 10px !important;
            overflow: hidden !important;
        }

        .dark .numeric-capsule nav div:last-child {
            background: rgba(255, 255, 255, 0.03) !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
        }

        .numeric-capsule a,
        .numeric-capsule span {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            min-width: 2.5rem !important;
            height: 2.25rem !important;
            padding: 0 0.75rem !important;
            font-size: 0.8125rem !important;
            font-weight: 600 !important;
            color: #1e293b !important;
            border: none !important;
            border-right: 1px solid #e2e8f0 !important;
            background: transparent !important;
            transition: all 0.2s !important;
            text-decoration: none !important;
        }

        .dark .numeric-capsule a,
        .dark .numeric-capsule span {
            color: #f1f5f9 !important;
            border-right-color: rgba(255, 255, 255, 0.1) !important;
        }

        .numeric-capsule div:last-child> :last-child {
            border-right: none !important;
        }

        .numeric-capsule a:hover {
            background: rgba(59, 130, 246, 0.05) !important;
            color: #3b82f6 !important;
        }

        .numeric-capsule .active span {
            background: rgba(59, 130, 246, 0.1) !important;
            color: #3b82f6 !important;
            font-weight: 700 !important;
        }

        .numeric-capsule [aria-disabled="true"] span {
            color: #94a3b8 !important;
            cursor: default !important;
        }
    </style>

    <!-- Statistics Grid -->
    <div class="stats-grid">
        @foreach($stats as $key => $stat)
            @if($key !== 'totalKredit')
                <div class="stat-card">
                    <div>
                        <div class="stat-label">{{ $stat['label'] }}</div>
                        <div class="stat-value">
                            {{ $key == 'nilai' ? $fmt($stat['value']) : number_format($stat['value'], 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="stat-footer">
                        <div class="trend-badge {{ $stat['trend']['color'] == 'success' ? 'trend-success' : 'trend-danger' }}">
                            <x-dynamic-component :component="$stat['trend']['icon']"
                                style="width: 0.875rem; height: 0.875rem;" />
                            {{ abs($stat['trend']['pct']) }}%
                        </div>
                        <span class="stat-comparison">vs bulan lalu</span>
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    <!-- Overall Balance Bar (Synced to Total Aset row style) -->
    <div
        style="margin-bottom: 2rem; display: flex; align-items: center; gap: 1.5rem; background: rgba(59, 130, 246, 0.1); padding: 1.25rem 1.5rem; border-radius: 8px; border-top: 3px solid #3b82f6; justify-content: flex-end;">
        <span style="font-size: 0.8125rem; font-weight: 800; color: #1e293b; text-transform: uppercase;"
            class="dark:text-white">Total Debit: <b style="color: #3b82f6; margin-left: 0.5rem;">Rp
                {{ $fmt($totalDebit) }}</b></span>
        <span style="font-size: 0.8125rem; font-weight: 800; color: #1e293b; text-transform: uppercase;"
            class="dark:text-white">Total Kredit: <b style="color: #3b82f6; margin-left: 0.5rem;">Rp
                {{ $fmt($totalCredit) }}</b></span>
        <span style="font-size: 0.8125rem; font-weight: 800; color: #1e293b; text-transform: uppercase;"
            class="dark:text-white">Selisih: <b
                style="color: {{ $difference != 0 ? '#ef4444' : '#10b981' }}; margin-left: 0.5rem;">Rp
                {{ $fmt($difference) }}</b></span>
    </div>

    <!-- Journal Entries Container -->
    <div class="journal-container">
        @forelse ($entries as $entry)
            @php
                $entryDebit = $entry->items->sum('debit');
                $entryCredit = $entry->items->sum('credit');
            @endphp
            <div class="journal-entry-block">
                <div class="entry-header">
                    <div class="entry-title">{{ $entry->description ?: 'Transaksi Jurnal Umum' }}</div>
                    <div class="entry-meta">
                        <div class="entry-meta-left">
                            <span>Dibuat pada {{ Carbon\Carbon::parse($entry->created_at)->format('d/m/Y') }}</span>
                            <span style="font-family: monospace; font-weight: 600;">#{{ $entry->reference_number }}</span>
                        </div>
                        <span
                            style="font-weight: 700; color: #3b82f6;">{{ Carbon\Carbon::parse($entry->transaction_date)->format('d/m/Y') }}</span>
                    </div>
                </div>

                <table class="journal-table">
                    <thead>
                        <tr>
                            <th>Akun</th>
                            <th style="text-align: right; width: 180px;">Debit</th>
                            <th style="text-align: right; width: 180px;">Kredit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($entry->items as $item)
                            <tr class="item-row">
                                <td class="cell-account">
                                    <div class="account-header">
                                        {{ $item->account->name }}
                                        <span class="account-code">({{ $item->account->code }})</span>
                                    </div>
                                    @if($item->description && $item->description !== $entry->description)
                                        <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.25rem;">
                                            {{ $item->description }}</div>
                                    @endif
                                </td>
                                <td class="cell-amount {{ $item->debit == 0 ? 'amount-zero' : '' }}">
                                    {{ $item->debit > 0 ? $fmt($item->debit) : '0' }}
                                </td>
                                <td class="cell-amount {{ $item->credit == 0 ? 'amount-zero' : '' }}">
                                    {{ $item->credit > 0 ? $fmt($item->credit) : '0' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="entry-footer">
                    <span class="footer-total">{{ $fmt($entryDebit) }}</span>
                    <span class="footer-total">{{ $fmt($entryCredit) }}</span>
                </div>
            </div>
        @empty
            <div
                style="padding: 3rem; text-align: center; color: #94a3b8; font-style: italic; background: white; border-radius: 12px; border: 1px dashed #e2e8f0;">
                Tidak ada transaksi jurnal dalam rentang waktu ini.
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="pagination-footer">
        <div class="pagination-status">
            Menampilkan {{ $paginator->firstItem() ?? 0 }} sampai {{ $paginator->lastItem() ?? 0 }} dari
            {{ number_format($totalCount, 0, ',', '.') }} hasil
        </div>

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

        <div style="display: flex; gap: 1rem; align-items: center;">
            <div class="numeric-capsule">
                {{ $paginator->links() }}
            </div>
        </div>
    </div>
</x-filament-panels::page>