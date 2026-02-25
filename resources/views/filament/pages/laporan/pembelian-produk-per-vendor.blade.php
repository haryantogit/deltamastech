@php
    $viewData = $this->getViewData();
    $vendors = $viewData['vendors'];
    $paginator = $viewData['paginator'];
    $chartData = $viewData['chartData'];
    $nestedData = $viewData['nestedData'] ?? [];
    $fmt = function ($num) {
        if ($num == 0)
            return '0';
        return number_format($num, 0, ',', '.');
    };
@endphp

<x-filament-panels::page>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
            min-width: 900px;
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

        .vendor-header-row {
            background: rgba(59, 130, 246, 0.02);
        }

        .nested-header th {
            background: #f1f5f9 !important;
            color: #475569 !important;
            border-bottom: 1px solid #e2e8f0 !important;
            padding: 0.5rem 1rem !important;
        }

        .dark .nested-header th {
            background: #1e293b !important;
            color: #94a3b8 !important;
            border-bottom-color: #334155 !important;
        }

        .charts-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .chart-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        .dark .chart-card {
            background: #111827;
            border-color: #374151;
        }

        .row-group {
            cursor: pointer;
            transition: all 0.2s ease-in-out;
        }

        .row-group:hover {
            background: rgba(59, 130, 246, 0.05) !important;
        }

        .dark .row-group:hover {
            background: rgba(255, 255, 255, 0.03) !important;
        }

        .expand-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.5rem;
            height: 1.5rem;
            color: #3b82f6;
            font-weight: 800;
            font-size: 1.125rem;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            user-select: none;
        }

        .is-expanded .expand-icon {
            transform: rotate(180deg);
        }

        .nested-row {
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .total-row {
            background: #f8fafc;
            font-weight: 700;
        }

        .chart-card h3 {
            font-size: 0.75rem;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 1.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .dark .chart-card h3 {
            color: #e2e8f0;
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
            <div class="search-container">
                <span style="position:absolute; left:0.75rem; top:50%; transform:translateY(-50%); color:#94a3b8;">
                    <x-filament::icon icon="heroicon-m-magnifying-glass" class="w-4 h-4" />
                </span>
                <input type="text" wire:model.live.debounce.500ms="search" placeholder="Cari..." class="search-input">
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
                        <th>Supplier</th>
                        <th style="text-align: right;">Total kuantitas Produk</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vendors as $vendor)
                        @php $isExpanded = in_array($vendor->vendor_id, $this->expandedVendors); @endphp
                        <tr class="vendor-header-row row-group {{ $isExpanded ? 'is-expanded' : '' }}" wire:click="toggleVendor({{ $vendor->vendor_id }})">
                            <td style="font-weight: 600; color: #3b82f6; display: flex; align-items: center; gap: 0.75rem;">
                                <span class="expand-icon">
                                    {{ $isExpanded ? '−' : '+' }}
                                </span>
                                {{ $vendor->vendor_name }}
                            </td>
                            <td style="text-align: right; font-weight: 600;">{{ number_format($vendor->total_qty, 0, ',', '.') }}</td>
                        </tr>
                        @php $lines = $nestedData[$vendor->vendor_id] ?? []; @endphp
                        @if($isExpanded && count($lines) > 0)
                            <tr class="nested-header nested-row">
                                <td colspan="2" style="padding: 0;">
                                    <table style="width: 100%; border-collapse: collapse;">
                                        <thead>
                                            <tr class="nested-header">
                                                <th style="width: 40%; padding-left: 2rem !important;">Produk</th>
                                                <th style="width: 20%;">Kode</th>
                                                <th style="width: 20%; text-align: right;">Kuantitas</th>
                                                <th style="width: 20%; text-align: right; padding-right: 1.5rem !important;">
                                                    Total Harga</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($lines as $line)
                                                <tr>
                                                    <td style="padding-left: 2rem !important; color: #3b82f6; font-weight: 500;">
                                                        {{ $line->product_name }}</td>
                                                    <td>{{ $line->product_sku }}</td>
                                                    <td style="text-align: right;">{{ number_format($line->quantity, 0, ',', '.') }}
                                                    </td>
                                                    <td style="text-align: right; padding-right: 1.5rem !important;">
                                                        {{ $fmt($line->total_price) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="2" style="text-align: center; padding: 3rem; color: #94a3b8;">Tidak ada data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="charts-container">
            <div class="chart-card">
                <h3>PEMBELIAN PRODUK PER VENDOR</h3>
                <div style="height: 300px; position: relative;">
                    <canvas id="vendorChart"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <h3>PEMBELIAN PRODUK PER GRUP VENDOR</h3>
                <div
                    style="height: 300px; display: flex; align-items: center; justify-content: center; color: #94a3b8; font-size: 0.8125rem; font-style: italic;">
                    Data tidak tersedia
                </div>
            </div>
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

    <script>
        document.addEventListener('livewire:initialized', () => {
            const ctx = document.getElementById('vendorChart');
            const data = @json($chartData);

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: data.map(i => i.name),
                    datasets: [{
                        data: data.map(i => i.total_qty),
                        backgroundColor: [
                            '#f43f5e', '#fbbf24', '#2dd4bf', '#3b82f6', '#8b5cf6',
                            '#ec4899', '#f97316', '#10b981', '#06b6d4', '#6366f1'
                        ],
                        borderWidth: 0,
                        hoverOffset: 15
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                usePointStyle: true,
                                padding: 20,
                                font: { size: 11 }
                            }
                        }
                    },
                    cutout: '70%'
                }
            });
        });
    </script>
</x-filament-panels::page>