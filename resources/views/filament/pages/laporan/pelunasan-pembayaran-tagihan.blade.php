@php
    $viewData = $this->getViewData();
    $results = $viewData['results'];
    $paginator = $viewData['paginator'];
    $pageSubtotal = $viewData['pageSubtotal'];
    $globalTotal = $viewData['globalTotal'];

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

        .text-link {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 600;
        }

        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
            background: #dcfce7;
            color: #10b981;
        }

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

            .fi-header-actions,
            .filter-search-row {
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

        {{-- Table --}}
        <div style="overflow-x: auto;">
            <table class="report-table">
                <thead>
                    <tr>
                        <th style="text-align: left;">Nomor</th>
                        <th style="text-align: left;">Nama Pelanggan</th>
                        <th style="text-align: left; width: 140px;">Tgl Tagihan</th>
                        <th style="text-align: left; width: 160px;">Tgl Pembayaran I</th>
                        <th style="text-align: left; width: 140px;">Tgl Pelunasan</th>
                        <th style="text-align: right; width: 160px;">Total Tagihan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($results as $row)
                        <tr>
                            <td><span class="text-link">{{ $row->invoice_number }}</span></td>
                            <td style="font-weight: 600;">{{ $row->contact_name }}</td>
                            <td>{{ \Carbon\Carbon::parse($row->transaction_date)->format('d/m/Y') }}</td>
                            <td>
                                @if($row->display_first_payment)
                                    {{ \Carbon\Carbon::parse($row->display_first_payment)->format('d/m/Y') }}
                                @else
                                    <span style="color: #94a3b8;">-</span>
                                @endif
                            </td>
                            <td>
                                @if($row->display_settlement_date)
                                    <span class="status-badge">
                                        {{ \Carbon\Carbon::parse($row->display_settlement_date)->format('d/m/Y') }}
                                    </span>
                                @elseif($row->status === 'paid')
                                    <span class="status-badge">Lunas</span>
                                @else
                                    <span style="color: #94a3b8;">Belum Lunas</span>
                                @endif
                            </td>
                            <td style="text-align: right; font-weight: 700;">
                                {{ $fmt($row->total_amount) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 3rem; color: #94a3b8;">
                                Tidak ada data untuk periode ini.
                            </td>
                        </tr>
                    @endforelse

                    {{-- Subtotal row --}}
                    <tr style="border-top: 2px solid rgba(128,128,128,0.2);">
                        <td colspan="5" style="padding: 12px 14px; text-align: right; font-weight: 700;">
                            Subtotal (Halaman Ini)
                        </td>
                        <td style="padding: 12px 14px; text-align: right; font-weight: 700;">
                            {{ $fmt($pageSubtotal) }}
                        </td>
                    </tr>
                    {{-- Grand Total row --}}
                    <tr style="background: rgba(59, 130, 246, 0.05);">
                        <td colspan="5"
                            style="padding: 12px 14px; text-align: right; font-weight: 700; color: #3b82f6;">
                            Total Keseluruhan
                        </td>
                        <td
                            style="padding: 12px 14px; text-align: right; font-weight: 700; font-size: 1rem; color: #3b82f6;">
                            {{ $fmt($globalTotal) }}
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