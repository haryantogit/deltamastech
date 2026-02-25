@php
    $viewData = $this->getViewData();
    $results = $viewData['results'];
    $paginator = $viewData['paginator'];
    $grandTotalCount = $viewData['grandTotalCount'];
    $grandTotalAmount = $viewData['grandTotalAmount'];
    $fmt = function ($num) {
        if ($num == 0)
            return '0';
        return number_format($num, 0, ',', '.');
    };
@endphp

<x-filament-panels::page>
    <style>
        .report-section {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            overflow: auto;
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

        .total-row {
            background: #f8fafc;
            font-weight: 700;
        }

        .dark .total-row {
            background: #1f2937;
        }

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
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }

        .dark .date-badge {
            background: #1e293b;
            border-color: #334155;
            color: #94a3b8;
        }
    </style>

    <div class="report-content">
        <div class="filter-ribbon">
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
                        <th>Provinsi</th>
                        <th style="text-align: center;">Jumlah Transaksi</th>
                        <th style="text-align: center;">Total Pembelian</th>
                        <th style="text-align: right;">Rata-rata per Transaksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($results as $row)
                        <tr>
                            <td style="font-weight: 600; color: #3b82f6;">{{ $row->region }}</td>
                            <td style="text-align: center;">{{ number_format($row->transaction_count, 0, ',', '.') }}</td>
                            <td style="text-align: center;">{{ $fmt($row->total_amount) }}</td>
                            <td style="text-align: right;">{{ $fmt($row->average_per_transaction) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 3rem; color: #94a3b8;">Tidak ada data.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($results) > 0)
                    <tfoot>
                        <tr class="total-row">
                            <td style="padding: 1rem;">Total</td>
                            <td style="text-align: center; padding: 1rem;">
                                {{ number_format($grandTotalCount, 0, ',', '.') }}</td>
                            <td style="text-align: center; padding: 1rem;">{{ $fmt($grandTotalAmount) }}</td>
                            <td style="text-align: right; padding: 1rem;">
                                {{ $grandTotalCount > 0 ? $fmt($grandTotalAmount / $grandTotalCount) : '0' }}</td>
                        </tr>
                    </tfoot>
                @endif
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