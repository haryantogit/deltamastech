@php
    $viewData = $this->getViewData();
    $vendors = $viewData['vendors'];
    $paginator = $viewData['paginator'];
    $nestedData = $viewData['nestedData'];
    $grandTotalCount = $viewData['grandTotalCount'];
    $grandTotalAmount = $viewData['grandTotalAmount'];
    $expandedVendors = $this->expandedVendors;

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
            min-width: 1100px;
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

        .row-vendor {
            cursor: pointer;
            transition: background 0.2s;
        }

        .row-vendor:hover {
            background: rgba(59, 130, 246, 0.02);
        }

        .dark .row-vendor:hover {
            background: rgba(255, 255, 255, 0.02);
        }

        .expand-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.25rem;
            height: 1.25rem;
            color: #3b82f6;
            font-weight: 800;
            font-size: 1rem;
        }

        .total-row {
            background: #f8fafc;
            font-weight: 700;
        }

        .dark .total-row {
            background: rgba(255, 255, 255, 0.02);
        }

        /* Filter Ribbon */
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
            color: #1e293b;
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

        /* Expanded Details Style */
        .details-wrapper {
            background: rgba(59, 130, 246, 0.01);
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .dark .details-wrapper {
            background: rgba(255, 255, 255, 0.01);
            border-color: #1e293b;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        .dark .details-table {
            background: #111827;
            border-color: #374151;
        }

        .details-table th {
            background: rgba(59, 130, 246, 0.04);
            color: #475569;
            padding: 0.75rem;
            font-size: 0.7rem;
            text-transform: uppercase;
        }

        .dark .details-table th {
            background: rgba(255, 255, 255, 0.05);
            color: #94a3b8;
        }

        .details-table td {
            padding: 0.75rem;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.75rem;
        }

        .dark .details-table td {
            border-bottom-color: #1e293b;
        }
    </style>

    <div class="report-content">
        <div class="filter-ribbon">
            <div class="search-container">
                <span class="search-icon">
                    <x-filament::icon icon="heroicon-m-magnifying-glass" class="w-4 h-4" />
                </span>
                <input type="text" wire:model.live.debounce.500ms="search" placeholder="Cari vendor..."
                    class="search-input">
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
                        <th style="width: 40px;"></th>
                        <th>Vendor</th>
                        <th>Perusahaan</th>
                        <th style="text-align: right;">Total Transaksi</th>
                        <th style="text-align: right;">Pendapatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vendors as $vendor)
                        @php $isExpanded = in_array($vendor->vendor_id, $expandedVendors); @endphp
                        <tr class="row-vendor" wire:click="toggleVendor({{ $vendor->vendor_id }})">
                            <td>
                                <span class="expand-icon">{{ $isExpanded ? '−' : '+' }}</span>
                            </td>
                            <td style="font-weight: 600; color: #3b82f6;">{{ $vendor->vendor_name }}</td>
                            <td>{{ $vendor->company_name ?? '-' }}</td>
                            <td style="text-align: right;">{{ number_format($vendor->transaction_count, 0, ',', '.') }}</td>
                            <td style="text-align: right; font-weight: 700; color: #3b82f6;">
                                {{ $fmt($vendor->total_amount) }}</td>
                        </tr>

                        @if($isExpanded)
                            <tr>
                                <td colspan="5" style="padding: 0;">
                                    <div class="details-wrapper">
                                        <table class="details-table">
                                            <thead>
                                                <tr>
                                                    <th>Nomor</th>
                                                    <th>Tanggal</th>
                                                    <th>Produk</th>
                                                    <th>Kode/SKU</th>
                                                    <th>Kategori</th>
                                                    <th>Deskripsi</th>
                                                    <th style="text-align: right;">Kuantitas</th>
                                                    <th style="text-align: right;">Harga</th>
                                                    <th style="text-align: right;">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($nestedData[$vendor->vendor_id] ?? [] as $item)
                                                    <tr>
                                                        <td style="font-weight: 600; color: #3b82f6;">{{ $item->invoice_number }}
                                                        </td>
                                                        <td>{{ \Carbon\Carbon::parse($item->invoice_date)->format('d/m/Y') }}</td>
                                                        <td style="color: #3b82f6; font-weight: 600;">{{ $item->product_name }}</td>
                                                        <td style="font-family: monospace;">{{ $item->product_sku ?? '-' }}</td>
                                                        <td>{{ $item->category_name ?? '-' }}</td>
                                                        <td style="color: #64748b; font-size: 0.7rem;">{{ $item->description }}</td>
                                                        <td style="text-align: right;">
                                                            {{ number_format($item->quantity, 0, ',', '.') }}</td>
                                                        <td style="text-align: right;">{{ $fmt($item->unit_price) }}</td>
                                                        <td style="text-align: right; font-weight: 600; color: #3b82f6;">
                                                            {{ $fmt($item->total_price) }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="9" style="text-align: center; color: #94a3b8;">Tidak ada data
                                                            detail.</td>
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
                            <td colspan="5" style="text-align: center; padding: 3rem; color: #94a3b8;">
                                Tidak ada data untuk periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="3" style="padding: 1rem;">Total</td>
                        <td style="text-align: right;">{{ number_format($grandTotalCount, 0, ',', '.') }}</td>
                        <td style="text-align: right; color: #3b82f6;">{{ $fmt($grandTotalAmount) }}</td>
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