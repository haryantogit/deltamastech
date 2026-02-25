@php
    $viewData = $this->getViewData();
    $results = $viewData['results'];
    $paginator = $viewData['paginator'];
    $totalCount = $viewData['totalCount'];
    $globalTotals = $viewData['globalTotals'];

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

        /* Filter & Search row */
        .filter-search-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #f1f5f9;
            gap: 1rem;
        }

        .dark .filter-search-row {
            border-color: #374151;
        }

        .right-controls {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .mode-select-capsule {
            display: flex;
            align-items: center;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            height: 2.25rem;
            padding: 0 0.5rem;
        }

        .dark .mode-select-capsule {
            background: #1f2937;
            border-color: #374151;
        }

        .mode-select-capsule select {
            background: transparent;
            border: none;
            font-size: 0.8125rem;
            font-weight: 500;
            color: #3b82f6;
            outline: none;
            cursor: pointer;
            padding-right: 0.5rem;
            appearance: none;
            -webkit-appearance: none;
        }

        .dark .mode-select-capsule select {
            color: #60a5fa;
        }

        .date-display {
            font-size: 0.8125rem;
            color: #64748b;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: white;
            border: 1px solid #e2e8f0;
            padding: 0.4rem 0.75rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .date-display:hover {
            border-color: #3b82f6;
            color: #3b82f6;
        }

        .dark .date-display {
            background: #1f2937;
            border-color: #374151;
        }

        /* Tables & Groups */
        .report-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        .report-table th {
            padding: 0.875rem 1.25rem;
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            text-transform: capitalize;
            background: #f8fafc;
            border-bottom: 2px solid #f1f5f9;
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

        .nested-row-container {
            background: #f8fafc;
        }

        .dark .nested-row-container {
            background: rgba(255, 255, 255, 0.02) !important;
        }

        .nested-table {
            width: 100%;
            border-collapse: collapse;
            background: #fcfdfe;
        }

        .dark .nested-table {
            background: rgba(255, 255, 255, 0.01) !important;
        }

        .nested-table-header {
            border-bottom: 1px solid #f1f5f9;
        }

        .dark .nested-table-header {
            border-bottom-color: #1f2937;
        }

        .nested-table-row {
            border-bottom: 1px solid #f8fafc;
        }

        .dark .nested-table-row {
            border-bottom-color: rgba(255, 255, 255, 0.05);
        }

        .toggle-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.25rem;
            height: 1.25rem;
            border: 1px solid #3b82f6;
            border-radius: 4px;
            margin-right: 0.5rem;
            font-size: 0.75rem;
            color: #3b82f6;
            background: rgba(59, 130, 246, 0.05);
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 700;
        }

        .toggle-btn:hover {
            background: #3b82f6;
            color: white;
        }

        .dark .toggle-btn {
            border-color: #60a5fa;
            color: #60a5fa;
            background: rgba(96, 165, 250, 0.1);
        }

        .dark .toggle-btn:hover {
            background: #60a5fa;
            color: #111827;
        }

        .grand-total-container {
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            border-top: 2px solid #e2e8f0;
            background: #f8fafc;
        }

        .dark .grand-total-container {
            border-top-color: #374151;
            background: #111827;
        }

        /* Pagination */
        .pagination-container {
            padding: 1rem 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            margin-top: 2rem;
        }

        .pagination-status {
            font-size: 0.8125rem;
            color: #64748b;
            font-weight: 500;
        }

        .per-page-capsule {
            display: flex;
            align-items: center;
            background: rgba(128, 128, 128, 0.05);
            border: 1px solid rgba(128, 128, 128, 0.1);
            border-radius: 10px;
            padding: 0 0.75rem;
            height: 2.25rem;
            gap: 0;
        }

        .per-page-label {
            font-size: 0.75rem;
            color: #64748b;
            font-weight: 500;
            border-right: 1px solid rgba(128, 128, 128, 0.1);
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

        .numeric-capsule nav {
            display: flex;
            align-items: center;
        }

        .numeric-capsule nav>div:first-child,
        .numeric-capsule nav p,
        .numeric-capsule [class*="hidden sm:flex-1"] {
            display: none !important;
        }

        .numeric-capsule nav div:last-child {
            display: flex !important;
            background: rgba(128, 128, 128, 0.05) !important;
            border: 1px solid rgba(128, 128, 128, 0.1) !important;
            border-radius: 10px !important;
            overflow: hidden !important;
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
            border-right: 1px solid rgba(128, 128, 128, 0.1) !important;
            background: transparent !important;
            transition: all 0.2s !important;
            text-decoration: none !important;
        }

        .dark .numeric-capsule a,
        .dark .numeric-capsule span {
            color: #f1f5f9 !important;
        }

        .numeric-capsule div:last-child> :last-child {
            border-right: none !important;
        }

        .numeric-capsule a:hover {
            background: rgba(59, 130, 246, 0.05) !important;
            color: #3b82f6 !important;
        }

        .numeric-capsule .active span,
        .numeric-capsule [aria-current="page"] span {
            background: rgba(59, 130, 246, 0.1) !important;
            color: #3b82f6 !important;
            font-weight: 700 !important;
        }

        .numeric-capsule svg {
            width: 1rem !important;
            height: 1rem !important;
        }

        @media print {

            .fi-header-actions,
            .filter-search-row,
            .pagination-container {
                display: none !important;
            }
        }
    </style>

    <div class="delivery-report-container">
        {{-- Controls Row --}}
        <div class="filter-search-row" style="justify-content: flex-end;">
            <div class="right-controls">
                <div class="mode-select-capsule">
                    <select wire:model.live="dateMode">
                        <option value="transaksi">Tanggal Transaksi</option>
                        <option value="pengiriman">Tanggal Pengiriman</option>
                    </select>
                    <svg style="width: 0.875rem; height: 0.875rem; color: #3b82f6;" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>

                <div class="date-display" wire:click="mountAction('filter')">
                    {{ \Carbon\Carbon::parse($this->startDate)->format('d/m/Y') }} —
                    {{ \Carbon\Carbon::parse($this->endDate)->format('d/m/Y') }}
                    <svg style="width: 1rem; height: 1rem; color: #94a3b8;" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                        </path>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div style="overflow-x: auto;">
            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width: 30%; text-align: left;">Ekspedisi</th>
                        <th style="text-align: center;">Jumlah Pengiriman</th>
                        <th style="text-align: right;">Total Tagihan</th>
                        <th style="text-align: right;">Total Ongkos Kirim</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($results as $courier)
                        {{-- Level 1: Courier --}}
                        <tr wire:key="courier-{{ $courier['courier_name'] }}">
                            <td style="font-weight: 500;">
                                <span class="toggle-btn" wire:click="toggleCourier('{{ $courier['courier_name'] }}')">
                                    {{ in_array($courier['courier_name'], $this->expandedCouriers) ? '−' : '+' }}
                                </span>
                                {{ $courier['courier_name'] }}
                            </td>
                            <td style="text-align: center;">{{ $courier['jumlah_pengiriman'] }}</td>
                            <td style="text-align: right;">{{ $fmt($courier['total_tagihan']) }}</td>
                            <td style="text-align: right;">{{ $fmt($courier['total_ongkir']) }}</td>
                        </tr>

                        @if (in_array($courier['courier_name'], $this->expandedCouriers))
                            @foreach ($courier['dates'] as $dateRow)
                                {{-- Level 2: Date --}}
                                <tr wire:key="date-{{ $courier['courier_name'] }}-{{ $dateRow['date'] }}"
                                    class="nested-row-container">
                                    <td style="padding-left: 2.5rem; font-weight: 500; font-size: 0.8125rem;">
                                        <span class="toggle-btn"
                                            wire:click="toggleDate('{{ $courier['courier_name'] }}', '{{ $dateRow['date'] }}')">
                                            {{ isset($this->expandedDates["{$courier['courier_name']}|{$dateRow['date']}"]) ? '−' : '+' }}
                                        </span>
                                        {{ \Carbon\Carbon::parse($dateRow['date'])->format('d/m/Y') }}
                                    </td>
                                    <td style="text-align: center; font-size: 0.8125rem;">{{ $dateRow['jumlah_pengiriman'] }}
                                    </td>
                                    <td style="text-align: right; font-size: 0.8125rem;">{{ $fmt($dateRow['total_tagihan']) }}
                                    </td>
                                    <td style="text-align: right; font-size: 0.8125rem;">{{ $fmt($dateRow['total_ongkir']) }}
                                    </td>
                                </tr>

                                @if (isset($this->expandedDates["{$courier['courier_name']}|{$dateRow['date']}"]))
                                    <tr wire:key="invoices-{{ $courier['courier_name'] }}-{{ $dateRow['date'] }}">
                                        <td colspan="4" style="padding: 0;">
                                            <table class="nested-table">
                                                <thead>
                                                    <tr class="nested-table-header">
                                                        <th
                                                            style="padding: 0.5rem 1.25rem 0.5rem 4rem; text-align: left; font-size: 0.70rem; color: #94a3b8; font-weight: 600;">
                                                            Nomor</th>
                                                        <th
                                                            style="padding: 0.5rem 1.25rem; text-align: left; font-size: 0.70rem; color: #94a3b8; font-weight: 600;">
                                                            Nama</th>
                                                        <th
                                                            style="padding: 0.5rem 1.25rem; text-align: right; font-size: 0.70rem; color: #94a3b8; font-weight: 600;">
                                                            Total Tagihan</th>
                                                        <th
                                                            style="padding: 0.5rem 1.25rem; text-align: right; font-size: 0.70rem; color: #94a3b8; font-weight: 600;">
                                                            Ongkos Kirim</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($dateRow['invoices'] as $invoice)
                                                        <tr class="nested-table-row">
                                                            <td
                                                                style="padding: 0.5rem 1.25rem 0.5rem 4rem; font-size: 0.75rem; color: #3b82f6;">
                                                                {{ $invoice['invoice_number'] }}</td>
                                                            <td style="padding: 0.5rem 1.25rem; font-size: 0.75rem;">
                                                                {{ $invoice['customer_name'] }}</td>
                                                            <td
                                                                style="padding: 0.5rem 1.25rem; text-align: right; font-size: 0.75rem;">
                                                                {{ $fmt($invoice['total_tagihan']) }}</td>
                                                            <td
                                                                style="padding: 0.5rem 1.25rem; text-align: right; font-size: 0.75rem;">
                                                                {{ $fmt($invoice['total_ongkir']) }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        @endif
                    @empty
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 3rem; color: #94a3b8;">Tidak ada data ongkos
                                kirim.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{-- Grand Total row --}}
            <div class="grand-total-container">
                <div style="flex: 1; visibility: hidden;">Spacer</div>
                <div style="flex: 1; text-align: center; font-weight: 700;" class="text-slate-900 dark:text-white">
                    {{ $globalTotals['jumlah_pengiriman'] }}
                </div>
                <div style="flex: 1; text-align: right; font-weight: 700;" class="text-slate-900 dark:text-white">
                    {{ $fmt($globalTotals['total_tagihan']) }}
                </div>
                <div style="flex: 1; text-align: right; font-weight: 700;" class="text-slate-900 dark:text-white">
                    {{ $fmt($globalTotals['total_ongkir']) }}
                </div>
            </div>
        </div>
    </div>

    {{-- Pagination Footer --}}
    <div class="pagination-container">
        <div class="pagination-status">
            Total {{ number_format($totalCount, 0, ',', '.') }} data
        </div>

        <div class="per-page-capsule">
            <span class="per-page-label">per halaman</span>
            <select wire:model.live="perPage">
                <option value="15">15</option>
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="500">500</option>
            </select>
            <svg style="width: 1rem; height: 1rem; color: #64748b; margin-left: -0.25rem;" fill="none"
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </div>

        <div class="numeric-capsule">
            {{ $paginator->links() }}
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
