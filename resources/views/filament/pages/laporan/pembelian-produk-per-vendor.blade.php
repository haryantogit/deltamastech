@php
    $viewData = $this->getViewData();
    $vendors = $viewData['vendors'];
    $paginator = $viewData['paginator'];
    $chartData = $viewData['chartData'];
    $nestedData = $viewData['nestedData'] ?? [];
    $fmt = function ($num) {
        if ($num == 0) return '0';
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

        /* Search Row */
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

        .charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            width: 100%;
            box-sizing: border-box;
            margin-bottom: 2rem;
        }

        .chart-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
            overflow: hidden;
        }
        .dark .chart-card {
            background: #111827;
            border-color: #374151;
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

        .report-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }
        .report-table th {
            padding: 1rem 1.25rem;
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            text-transform: capitalize;
            background: #f8fafc;
            border-bottom: 2px solid #f1f5f9;
            text-align: left;
        }
        .dark .report-table th {
            background: #1f2937;
            border-bottom-color: #374151;
            color: #94a3b8;
        }
        .report-table td {
            padding: 0.875rem 1.25rem;
            font-size: 0.8125rem;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
        }
        .dark .report-table td {
            border-bottom-color: #374151;
            color: #e2e8f0;
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
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 4px;
            color: #2563eb;
            font-weight: bold;
            font-size: 1rem;
            line-height: 1;
            transition: all 0.2s ease-in-out;
        }
        .dark .expand-icon {
            background: #1e3a8a;
            border-color: #1e40af;
            color: #60a5fa;
        }
        .is-expanded .expand-icon {
            background: #f1f5f9;
            border-color: #cbd5e1;
            color: #64748b;
        }
        .dark .is-expanded .expand-icon {
            background: #334155;
            border-color: #475569;
            color: #94a3b8;
        }

        @media print {
            .filter-search-row,
            .pagination-row,
            .charts-container {
                display: none !important;
            }
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <div class="report-content">

        {{-- Hidden bridge --}}
        <div x-data="{}" x-init="$dispatch('chart-data-updated', @js($chartData))" wire:key="chart-bridge-{{ now()->timestamp }}" style="display:none"></div>

        {{-- Charts at Top --}}
        <div class="charts-container" x-data="{
            chartData: @js($chartData),
            chart: null,
            init() {
                if(window.vendorDoughnut) {
                    window.vendorDoughnut.destroy();
                }
                const ctx = document.getElementById('vendorChart').getContext('2d');
                window.vendorDoughnut = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: this.chartData.map(i => i.name),
                        datasets: [{
                            data: this.chartData.map(i => i.total_qty),
                            backgroundColor: [
                                '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
                                '#06b6d4', '#ec4899', '#f97316', '#6366f1', '#14b8a6'
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
                                    font: { size: 11, weight: '500' }
                                }
                            }
                        },
                        cutout: '70%'
                    }
                });

                this.$watch('chartData', (val) => {
                    window.vendorDoughnut.data.labels = val.map(i => i.name);
                    window.vendorDoughnut.data.datasets[0].data = val.map(i => i.total_qty);
                    window.vendorDoughnut.update();
                });
            }
        }">
            <div class="chart-card">
                <h3>PEMBELIAN PRODUK PER VENDOR</h3>
                <div style="height: 300px; position: relative; width: 100%;">
                    <canvas id="vendorChart"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <h3>PEMBELIAN PRODUK PER GRUP VENDOR</h3>
                <div
                    style="height: 300px; display: flex; align-items: center; justify-content: center; color: #94a3b8; font-size: 0.8125rem; font-style: italic;">
                    Data grup vendor belum tersedia
                </div>
            </div>
        </div>

        <div class="delivery-report-container">
            {{-- Search bar (restored outside or at the top of section) --}}
            <div style="padding: 1rem 1.25rem; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: flex-end;">
                <div class="custom-search-container">
                    <svg class="search-icon-abs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <input type="text" wire:model.live.debounce.500ms="search" placeholder="Cari"
                        class="custom-search-input">
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th style="text-align: left;">Supplier</th>
                            <th style="text-align: right;">Total Kuantitas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vendors as $vendor)
                            @php $isExpanded = in_array($vendor->vendor_id, $this->expandedVendors); @endphp
                            <tr class="row-group {{ $isExpanded ? 'is-expanded' : '' }}"
                                wire:click="toggleVendor({{ $vendor->vendor_id }})">
                                <td
                                    style="font-weight: 600; color: #3b82f6; display: flex; align-items: center; gap: 0.5rem;">
                                    <span class="expand-icon">
                                        {{ $isExpanded ? '-' : '+' }}
                                    </span>
                                    {{ $vendor->vendor_name }}
                                </td>
                                <td style="text-align: right; font-weight: 700;">
                                    {{ number_format($vendor->total_qty, 0, ',', '.') }}</td>
                            </tr>
                            @php $lines = $nestedData[$vendor->vendor_id] ?? []; @endphp
                            @if($isExpanded)
                                @if(count($lines) > 0)
                                    <tr>
                                        <td colspan="2" style="padding: 0; background: #f8fafc;" class="dark:bg-white/5">
                                            <table style="width: 100%; border-collapse: collapse;">
                                                <thead>
                                                    <tr style="background: rgba(0,0,0,0.02);">
                                                        <th
                                                            style="padding-left: 3rem !important; text-align: left; font-size: 0.7rem; color: #94a3b8;">
                                                            PRODUK</th>
                                                        <th style="text-align: left; font-size: 0.7rem; color: #94a3b8;">KODE</th>
                                                        <th style="text-align: right; font-size: 0.7rem; color: #94a3b8;">QTY</th>
                                                        <th
                                                            style="text-align: right; padding-right: 1.5rem !important; font-size: 0.7rem; color: #94a3b8;">
                                                            TOTAL</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($lines as $line)
                                                        <tr>
                                                            <td
                                                                style="padding-left: 3rem !important; color: #1e293b; font-weight: 500;">
                                                                {{ $line->product_name }}</td>
                                                            <td style="color: #64748b;">{{ $line->product_sku }}</td>
                                                            <td style="text-align: right; font-weight: 600;">
                                                                {{ number_format($line->quantity, 0, ',', '.') }}</td>
                                                            <td
                                                                style="text-align: right; padding-right: 1.5rem !important; font-weight: 600;">
                                                                {{ $fmt($line->total_price) }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                @else
                                    <tr>
                                        <td colspan="2"
                                            style="text-align: center; padding: 1rem; color: #94a3b8; font-style: italic; font-size: 0.75rem;">
                                            Tidak ada detail produk untuk periode ini.
                                        </td>
                                    </tr>
                                @endif
                            @endif
                        @empty
                            <tr>
                                <td colspan="2" style="text-align: center; padding: 4rem; color: #94a3b8;">
                                    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                        <svg style="width: 48px; height: 48px; margin-bottom: 1rem; opacity: 0.2;" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <span style="font-size: 0.875rem; font-weight: 500;">Tidak ada data untuk periode ini.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($paginator->hasPages() || count([5, 10, 20, 50, 100, 'all']) > 1)
            <div style="margin-top: 1.5rem; margin-bottom: 1.0rem;" class="pagination-row">
                <x-filament::pagination :paginator="$paginator" :page-options="[5, 10, 20, 50, 100, 'all']"
                    current-page-option-property="perPage" />
            </div>
        @endif
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>