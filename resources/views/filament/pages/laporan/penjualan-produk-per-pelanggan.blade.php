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
            min-width: 800px;
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

        .row-hover:hover {
            background: rgba(59, 130, 246, 0.02);
        }

        .dark .row-hover:hover {
            background: rgba(255, 255, 255, 0.02);
        }

        /* Nested Table */
        .nested-row {
            background: #f8fafc;
        }

        .dark .nested-row {
            background: rgba(255, 255, 255, 0.02);
        }

        .nested-table {
            width: 100%;
            border-collapse: collapse;
        }

        .nested-table th {
            background: rgba(59, 130, 246, 0.03);
            font-size: 0.7rem;
            padding: 0.5rem 1rem;
            color: #64748b;
        }

        .dark .nested-table th {
            background: rgba(255, 255, 255, 0.05);
        }

        .nested-table td {
            padding: 0.5rem 1rem;
            font-size: 0.75rem;
            border-bottom: 1px solid rgba(226, 232, 240, 0.5);
        }

        /* Toggle Button */
        .toggle-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.5rem;
            height: 1.5rem;
            border: 1px solid #3b82f6;
            border-radius: 4px;
            color: #3b82f6;
            background: #eff6ff;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.2s;
        }

        .toggle-btn:hover {
            background: #3b82f6;
            color: white;
        }

        .dark .toggle-btn {
            border-color: #3b82f6;
            color: #60a5fa;
            background: rgba(59, 130, 246, 0.1);
        }

        .dark .toggle-btn:hover {
            background: #3b82f6;
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

        @media print {

            .filter-ribbon,
            .fi-header-actions,
            .pagination-container {
                display: none !important;
            }
        }
    </style>

    @php
        $viewData = $this->getViewData();
        $results = $viewData['results'];
        $paginator = $viewData['paginator'];
    @endphp

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
                        <th style="width: 40px;"></th>
                        <th>Pelanggan</th>
                        <th>Perusahaan</th>
                        <th style="text-align: right; width: 200px;">Total kuantitas Produk</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($results as $row)
                        @php $isExpanded = in_array($row->id, $expandedContacts); @endphp
                        <tr class="row-hover cursor-pointer" wire:click="toggleContact({{ $row->id }})">
                            <td>
                                <span class="toggle-btn text-xs">
                                    {{ $isExpanded ? '−' : '+' }}
                                </span>
                            </td>
                            <td style="font-weight: 600; color: #3b82f6;">{{ $row->contact_name }}</td>
                            <td>{{ $row->company ?? '-' }}</td>
                            <td style="text-align: right; font-weight: 700;">
                                {{ number_format($row->total_qty, 0, ',', '.') }}
                            </td>
                        </tr>

                        @if($isExpanded)
                            <tr class="nested-row">
                                <td colspan="4" style="padding: 1rem 2rem;">
                                    <table class="nested-table">
                                        <thead>
                                            <tr>
                                                <th>Produk</th>
                                                <th>Kode</th>
                                                <th style="text-align: right;">Kuantitas</th>
                                                <th style="text-align: right;">Harga Jual</th>
                                                <th style="text-align: right;">Total Harga</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($row->products as $item)
                                                <tr>
                                                    <td style="font-weight: 500; color: #3b82f6;">{{ $item->product_name }}</td>
                                                    <td style="font-family: monospace;">{{ $item->sku }}</td>
                                                    <td style="text-align: right;">{{ number_format($item->qty, 0, ',', '.') }}</td>
                                                    <td style="text-align: right;">
                                                        {{ number_format($item->avg_price, 0, ',', '.') }}
                                                    </td>
                                                    <td style="text-align: right; font-weight: 600;">
                                                        {{ number_format($item->total_price, 0, ',', '.') }}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" style="text-align: center; color: #94a3b8;">Tidak ada detail
                                                        produk.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 3rem; color: #94a3b8;">
                                Tidak ada data untuk periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
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