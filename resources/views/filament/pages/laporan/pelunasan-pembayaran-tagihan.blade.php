<x-filament-panels::page>
    <style>
        .report-section {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            overflow-x: auto;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
            margin-bottom: 2rem;
        }

        .dark .report-section {
            background: #111827;
            border-color: #374151;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }

        .report-table th {
            padding: 1rem;
            font-size: 0.75rem;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            background: #f8fafc;
            border-bottom: 2px solid #f1f5f9;
            text-align: left;
        }

        .dark .report-table th {
            background: #1f2937;
            border-bottom-color: #374151;
        }

        .report-table td {
            padding: 0.875rem 1rem;
            font-size: 0.8125rem;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
        }

        .dark .report-table td {
            border-bottom-color: #374151;
            color: #e2e8f0;
        }

        .report-table tr:hover {
            background: rgba(59, 130, 246, 0.02);
        }

        .dark .report-table tr:hover {
            background: rgba(255, 255, 255, 0.02);
        }

        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
            color: white;
        }

        /* Filter Ribbon */
        .filter-ribbon {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .date-badge {
            display: flex;
            align-items: center;
            padding: 0.5rem 1rem;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.8125rem;
            font-weight: 500;
            color: #475569;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }

        .dark .date-badge {
            background: #1e293b;
            border-color: #334155;
            color: #94a3b8;
        }

        .total-row {
            background: #f8fafc;
            font-weight: 700;
        }

        .dark .total-row {
            background: rgba(255, 255, 255, 0.02);
        }

        @media print {

            .filter-ribbon,
            .fi-header-actions,
            .pagination-container {
                display: none !important;
            }
        }
    </style>

    <div class="report-content">
        <div class="filter-ribbon">
            <div style="flex: 1;">
                <x-filament::input.wrapper style="max-width: 320px;">
                    <x-filament::input active-indicator type="search" wire:model.live.debounce.500ms="search"
                        placeholder="Cari nomor atau pelanggan..." />
                </x-filament::input.wrapper>
            </div>

            <div class="date-badge" x-on:click="$dispatch('open-modal', { id: 'fi-modal-action-filter' })">
                <x-filament::icon icon="heroicon-m-calendar" class="w-4 h-4 mr-2" />
                {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} —
                {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
            </div>
        </div>

        <div class="report-section">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Nomor</th>
                        <th>Nama Pelanggan</th>
                        <th style="width: 140px;">Tgl Tagihan</th>
                        <th style="width: 160px;">Tgl Pembayaran I</th>
                        <th style="width: 140px;">Tgl Pelunasan</th>
                        <th style="text-align: right; width: 160px;">Total Tagihan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($results as $row)
                        <tr>
                            <td style="font-weight: 700; color: #3b82f6;">{{ $row->invoice_number }}</td>
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
                                    <span class="status-badge" style="background: #10b981;">
                                        {{ \Carbon\Carbon::parse($row->display_settlement_date)->format('d/m/Y') }}
                                    </span>
                                @elseif($row->status === 'paid')
                                    <span class="status-badge" style="background: #10b981;">Lunas</span>
                                @else
                                    <span style="color: #94a3b8;">Belum Lunas</span>
                                @endif
                            </td>
                            <td style="text-align: right; font-weight: 700;">
                                {{ number_format($row->total_amount, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 3rem; color: #94a3b8;">
                                Tidak ada data untuk periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="5" style="text-align: right; padding: 1rem;">Subtotal (Halaman Ini)</td>
                        <td style="text-align: right;">{{ number_format($pageSubtotal, 0, ',', '.') }}</td>
                    </tr>
                    <tr class="total-row" style="background: rgba(59, 130, 246, 0.05);">
                        <td colspan="5" style="text-align: right; padding: 1rem; color: #3b82f6;">Total Keseluruhan</td>
                        <td style="text-align: right; font-size: 1rem; color: #3b82f6;">
                            {{ number_format($globalTotal, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="pagination-container"
            style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem;">
            <div style="font-size: 0.8125rem; color: #64748b; font-weight: 500;">
                Total {{ $paginator->total() }} data
            </div>
            <div>
                {{ $paginator->links() }}
            </div>
        </div>
    </div>
</x-filament-panels::page>