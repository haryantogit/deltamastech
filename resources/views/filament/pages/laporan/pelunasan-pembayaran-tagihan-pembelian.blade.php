@php
    $viewData = $this->getViewData();
    $items = $viewData['items'];
    $paginator = $viewData['paginator'];
    $grandTotal = $viewData['grandTotal'];
    $fmt = function ($num) {
        if ($num == 0)
            return '0';
        return number_format($num, 0, ',', '.');
    };
    $dt = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d/m/Y') : '-';
@endphp

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

        .total-row {
            background: #f8fafc;
            font-weight: 700;
        }

        .dark .total-row {
            background: rgba(255, 255, 255, 0.02);
        }

        .filter-ribbon {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .search-container {
            position: relative;
            width: 300px;
        }

        .search-input {
            width: 100%;
            padding: 0.5rem 1rem 0.5rem 2.5rem;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.8125rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }

        .dark .search-input {
            background: #1e293b;
            border-color: #334155;
            color: #e2e8f0;
        }

        .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        .date-badge,
        .type-switcher {
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

        .dark .date-badge,
        .dark .type-switcher {
            background: #1e293b;
            border-color: #334155;
            color: #94a3b8;
        }

        select.type-input {
            border: none;
            background: transparent;
            font-size: 0.8125rem;
            font-weight: 600;
            color: #3b82f6;
            cursor: pointer;
            outline: none;
            padding: 0 0.5rem;
        }

        .btn-lihat-total {
            color: #3b82f6;
            font-size: 0.75rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.25rem;
            cursor: pointer;
        }

        .btn-lihat-total:hover {
            text-decoration: underline;
        }
    </style>

    <div class="report-content">
        <div class="filter-ribbon">
            <div class="search-container">
                <span class="search-icon">
                    <x-filament::icon icon="heroicon-m-magnifying-glass" class="w-4 h-4" />
                </span>
                <input type="text" wire:model.live.debounce.500ms="search" placeholder="Cari..." class="search-input">
            </div>

            <div class="type-switcher">
                <select wire:model.live="dateType" class="type-input">
                    <option value="date">Tanggal Tagihan</option>
                    <option value="settlement">Tanggal Pelunasan</option>
                </select>
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
                        <th>Nama</th>
                        <th>Tanggal Tagihan</th>
                        <th>Tanggal Pembayaran Pertama</th>
                        <th>Tanggal Pelunasan</th>
                        <th style="text-align: right;">Total Tagihan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td style="font-weight: 600; color: #3b82f6;">{{ $item->number }}</td>
                            <td>{{ $item->supplier_name }}</td>
                            <td>{{ $dt($item->invoice_date) }}</td>
                            <td>{{ $dt($item->display_first_payment) }}</td>
                            <td>{{ $dt($item->display_full_settlement) }}</td>
                            <td style="text-align: right; font-weight: 600;">{{ $fmt($item->total_amount) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 3rem; color: #94a3b8;">Tidak ada data.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="5" style="padding: 1rem;">Total</td>
                        <td style="text-align: right; color: #3b82f6;">
                            <div x-data="{ show: false }">
                                <span x-show="show">{{ $fmt($grandTotal) }}</span>
                                <a x-show="!show" @click="show = true" class="btn-lihat-total">
                                    Lihat Total <x-filament::icon icon="heroicon-m-eye" class="w-3 h-3" />
                                </a>
                            </div>
                        </td>
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