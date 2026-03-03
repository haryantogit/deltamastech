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
            width: 1.5rem;
            height: 1.5rem;
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 6px;
            margin-right: 0.5rem;
            font-size: 1rem;
            color: #3b82f6;
            background: rgba(59, 130, 246, 0.1);
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 800;
            line-height: 0;
            user-select: none;
        }

        .toggle-btn:hover {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
            transform: scale(1.05);
        }

        .dark .toggle-btn {
            background: rgba(59, 130, 246, 0.2);
            border-color: rgba(59, 130, 246, 0.3);
            color: #60a5fa;
        }

        .dark .toggle-btn:hover {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
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

        @media print {
            .fi-header-actions {
                display: none !important;
            }
        }
    </style>

    <div class="delivery-report-container">

        {{-- Search Row --}}
        <div style="display: flex; justify-content: flex-end; padding: 1rem 1.25rem; border-bottom: 1px solid #f1f5f9;">
            <div style="position: relative; width: 280px;">
                <svg style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); width: 1rem; height: 1rem; color: #94a3b8;"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <input type="text" wire:model.live.debounce.500ms="search" placeholder="Cari"
                    style="width: 100%; padding: 0.5rem 0.75rem 0.5rem 2.25rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.8125rem; background: white; color: #1e293b; outline: none; transition: border-color 0.2s;"
                    onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
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
                                                                {{ $invoice['invoice_number'] }}
                                                            </td>
                                                            <td style="padding: 0.5rem 1.25rem; font-size: 0.75rem;">
                                                                {{ $invoice['customer_name'] }}
                                                            </td>
                                                            <td style="padding: 0.5rem 1.25rem; text-align: right; font-size: 0.75rem;">
                                                                {{ $fmt($invoice['total_tagihan']) }}
                                                            </td>
                                                            <td style="padding: 0.5rem 1.25rem; text-align: right; font-size: 0.75rem;">
                                                                {{ $fmt($invoice['total_ongkir']) }}
                                                            </td>
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
                    {{-- Grand Total row --}}
                    <tr style="border-top: 2px solid rgba(128,128,128,0.2);">
                        <td style="padding:16px 14px;"></td>
                        <td style="padding:16px 14px; text-align: center; font-size: 13px; font-weight: 700;"
                            class="text-gray-900 dark:text-gray-100">
                            {{ $globalTotals['jumlah_pengiriman'] }}
                        </td>
                        <td style="padding:16px 14px; text-align: right; font-size: 13px; font-weight: 700;"
                            class="text-gray-900 dark:text-gray-100">
                            {{ $fmt($globalTotals['total_tagihan']) }}
                        </td>
                        <td style="padding:16px 14px; text-align: right; font-size: 13px; font-weight: 700;"
                            class="text-gray-900 dark:text-gray-100">
                            {{ $fmt($globalTotals['total_ongkir']) }}
                        </td>
                    </tr>
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