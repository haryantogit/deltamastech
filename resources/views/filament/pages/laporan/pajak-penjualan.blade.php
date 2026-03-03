@php
    $viewData = $this->getViewData();
    $sales = $viewData['sales'];
    $purchase = $viewData['purchase'];
    $salesDetails = $viewData['salesDetails'];
    $purchaseDetails = $viewData['purchaseDetails'];
    $totalNet = $viewData['totalNet'];
    $totalTax = $viewData['totalTax'];
    $chartData = $viewData['chartData'] ?? [];

    $fmt = function ($num) {
        return number_format($num ?? 0, 0, ',', '.');
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
        }

        .report-table th {
            padding: 0.75rem 1rem;
            font-size: 0.7rem;
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
            color: #94a3b8;
        }

        .report-table td {
            padding: 0.75rem 1rem;
            font-size: 0.75rem;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
        }

        .dark .report-table td {
            border-bottom-color: #374151;
            color: #e2e8f0;
        }

        .number-col {
            text-align: right !important;
        }

        .expand-btn {
            cursor: pointer;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            transition: all 0.2s;
            background: #eff6ff;
            border: 1px solid #dbeafe;
            color: #3b82f6;
        }

        .expand-btn:hover {
            background: #dbeafe;
        }

        .dark .expand-btn {
            background: rgba(59, 130, 246, 0.1);
            border-color: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
        }

        .detail-header th {
            padding: 0.625rem 1.25rem !important;
            font-size: 0.70rem !important;
            color: #94a3b8 !important;
            font-weight: 700 !important;
            text-transform: uppercase;
            letter-spacing: 0.025em;
            background: rgba(0, 0, 0, 0.02) !important;
            border-bottom: 1px solid #f1f5f9 !important;
        }

        .detail-row td {
            padding: 0.625rem 1.25rem !important;
            font-size: 0.75rem !important;
            border-bottom: 1px solid #f8fafc !important;
        }

        .dark .detail-row td {
            background: rgba(0, 0, 0, 0.1);
            border-bottom-color: rgba(255, 255, 255, 0.03) !important;
        }

        .total-row td {
            padding: 1.25rem !important;
            border-top: 2px solid #e2e8f0 !important;
            background: #f8fafc !important;
            font-weight: 700;
        }

        .dark .total-row td {
            background: #111827 !important;
            border-top-color: #374151 !important;
        }

        .trx-link {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 600;
        }

        .trx-link:hover {
            color: #2563eb;
            text-decoration: underline;
        }

        @media print {

            .charts-container,
            .expand-btn,
            .fi-header-actions {
                display: none !important;
            }
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <div class="report-content">
        {{-- Charts at Top --}}
        <div class="charts-container" 
            wire:key="tax-chart-{{ md5(json_encode($chartData)) }}"
            x-data="{
                chartData: @js($chartData),
                init() {
                    let checkChartInterval = setInterval(() => {
                        if (typeof Chart !== 'undefined') {
                            clearInterval(checkChartInterval);
                            this.renderChart();
                        }
                    }, 100);
                },
                renderChart() {
                    const ctx = document.getElementById('taxChart').getContext('2d');
                    if (window.taxChartInstance) {
                        window.taxChartInstance.destroy();
                    }
                    
                    const hasData = this.chartData.some(i => i.value > 0);
                    
                    window.taxChartInstance = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: this.chartData.map(i => i.label),
                            datasets: [{
                                data: hasData ? this.chartData.map(i => i.value) : [1, 1],
                                backgroundColor: hasData ? this.chartData.map(i => i.color) : ['#e2e8f0', '#e2e8f0'],
                                borderWidth: 0,
                                hoverOffset: hasData ? 15 : 0
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
                                        font: { size: 11, weight: '600' }
                                    }
                                },
                                tooltip: {
                                    enabled: hasData,
                                    callbacks: {
                                        label: function(context) {
                                            let label = context.label || '';
                                            if (label) label += ': ';
                                            if (context.parsed !== null) {
                                                label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(context.parsed);
                                            }
                                            return label;
                                        }
                                    }
                                }
                            },
                            cutout: '70%'
                        }
                    });
                }
            }">
            <div class="chart-card">
                <h3>RINGKASAN PPN (KELUARAN vs MASUKAN)</h3>
                <div style="height: 300px; position: relative; width: 100%;">
                    <canvas id="taxChart"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <h3>STATUS PPN</h3>
                <div
                    style="height: 300px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                    <span
                        style="font-size: 2rem; font-weight: 800; color: {{ $totalTax >= 0 ? '#3b82f6' : '#ef4444' }};">
                        {{ $totalTax >= 0 ? 'Kurang Bayar' : 'Lebih Bayar' }}
                    </span>
                    <span style="font-size: 1.25rem; font-weight: 600; margin-top: 0.5rem; color: #64748b;">
                        Rp {{ $fmt(abs($totalTax)) }}
                    </span>
                </div>
            </div>
        </div>

        <div class="delivery-report-container">
            {{-- Search row --}}
            <div style="padding: 1rem 1.25rem; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: flex-end;"
                class="dark:border-gray-800">
                <div style="position: relative; width: 280px;">
                    <svg style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); width: 1rem; height: 1rem; color: #94a3b8;"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <input type="text" wire:model.live.debounce.500ms="search" placeholder="Cari"
                        style="width: 100%; padding: 0.5rem 0.75rem 0.5rem 2.25rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.8125rem; background: white; color: #1e293b; outline: none;"
                        class="dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100">
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th style="padding-left: 1.5rem;">DESKRIPSI PPN (11%)</th>
                            <th style="text-align: right; width: 25%;">NET</th>
                            <th style="text-align: right; width: 25%;">PAJAK</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Penjualan --}}
                        <tr wire:key="row-sales">
                            <td style="font-weight: 500; color: #10b981; display: flex; align-items: center; gap: 0.75rem;">
                                <div class="expand-btn" wire:click="toggleRow('sales')">
                                    <x-filament::icon 
                                        :icon="in_array('sales', $expandedRows) ? 'heroicon-m-minus' : 'heroicon-m-plus'" 
                                        class="w-3 h-3" 
                                    />
                                </div>
                                Penjualan (PPN Keluaran)
                            </td>
                            <td style="text-align: right; font-weight: 700;">{{ $fmt($sales->net) }}</td>
                            <td style="text-align: right; font-weight: 700;">{{ $fmt($sales->tax) }}</td>
                        </tr>

                        @if(in_array('sales', $expandedRows))
                            <tr class="detail-header">
                                <th style="padding-left: 60px !important;">Tanggal & Transaksi</th>
                                <th colspan="2" style="text-align: right;">Total Transaksi</th>
                            </tr>
                            @forelse($salesDetails as $detail)
                                <tr class="detail-row">
                                    <td style="padding-left: 60px !important;">
                                        <div class="flex flex-col">
                                            <span
                                                style="font-size: 0.75rem; color: #94a3b8;">{{ \Carbon\Carbon::parse($detail->date)->format('d/m/Y') }}</span>
                                            <a href="{{ \App\Filament\Resources\SalesInvoiceResource::getUrl('view', ['record' => $detail->id]) }}"
                                                target="_blank" class="trx-link">
                                                {{ $detail->number }}
                                            </a>
                                        </div>
                                    </td>
                                    <td colspan="2" style="text-align: right; font-weight: 600;">{{ $fmt($detail->total) }}</td>
                                </tr>
                            @empty
                                <tr class="detail-row">
                                    <td colspan="3" style="text-align: center; color: #94a3b8; padding: 2rem !important;">
                                        Tidak ada data detail.
                                    </td>
                                </tr>
                            @endforelse
                        @endif

                        {{-- Pembelian --}}
                        <tr wire:key="row-purchase">
                            <td style="font-weight: 500; color: #f59e0b; display: flex; align-items: center; gap: 0.75rem;">
                                <div class="expand-btn" wire:click="toggleRow('purchase')">
                                    <x-filament::icon 
                                        :icon="in_array('purchase', $expandedRows) ? 'heroicon-m-minus' : 'heroicon-m-plus'" 
                                        class="w-3 h-3" 
                                    />
                                </div>
                                Pembelian (PPN Masukan)
                            </td>
                            <td style="text-align: right; font-weight: 700;">{{ $fmt($purchase->net) }}</td>
                            <td style="text-align: right; font-weight: 700;">{{ $fmt($purchase->tax) }}</td>
                        </tr>

                        @if(in_array('purchase', $expandedRows))
                            <tr class="detail-header">
                                <th style="padding-left: 60px !important;">Tanggal & Transaksi</th>
                                <th colspan="2" style="text-align: right;">Total Transaksi</th>
                            </tr>
                            @forelse($purchaseDetails as $detail)
                                <tr class="detail-row">
                                    <td style="padding-left: 60px !important;">
                                        <div class="flex flex-col">
                                            <span
                                                style="font-size: 0.75rem; color: #94a3b8;">{{ \Carbon\Carbon::parse($detail->date)->format('d/m/Y') }}</span>
                                            <a href="{{ \App\Filament\Resources\PurchaseInvoiceResource::getUrl('view', ['record' => $detail->id]) }}"
                                                target="_blank" class="trx-link" style="color: #f59e0b;">
                                                {{ $detail->number }}
                                            </a>
                                        </div>
                                    </td>
                                    <td colspan="2" style="text-align: right; font-weight: 600;">{{ $fmt($detail->total) }}</td>
                                </tr>
                            @empty
                                <tr class="detail-row">
                                    <td colspan="3" style="text-align: center; color: #94a3b8; padding: 2rem !important;">
                                        Tidak ada data detail.
                                    </td>
                                </tr>
                            @endforelse
                        @endif
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td style="padding-left: 1.5rem !important; font-weight: 800; color: #475569;">TOTAL PPN
                                YANG HARUS DIBAYAR / (LEBIH BAYAR)</td>
                            <td style="text-align: right; font-weight: 700;">{{ $fmt($totalNet) }}</td>
                            <td style="text-align: right; color: #3b82f6; font-weight: 800; font-size: 1.1rem;">
                                {{ $fmt($totalTax) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:initialized', () => {
            let taxChartInstance = null;
            
            function initChart() {
                if(taxChartInstance) {
                    taxChartInstance.destroy();
                }

                // Get data from PHP
                const chartData = @json($chartData);
                const hasData = chartData.some(i => i.value > 0);

                const ctx = document.getElementById('taxChart').getContext('2d');

                taxChartInstance = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: chartData.map(i => i.label),
                        datasets: [{
                            data: hasData ? chartData.map(i => i.value) : [1, 1], // Show grey chart if 0
                            backgroundColor: hasData ? chartData.map(i => i.color) : ['#e2e8f0', '#e2e8f0'],
                            borderWidth: 0,
                            hoverOffset: hasData ? 15 : 0
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
                                    font: { size: 11, weight: '600' }
                                }
                            },
                            tooltip: {
                                enabled: hasData,
                                callbacks: {
                                    label: function(context) {
                                        let label = context.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        if (context.parsed !== null) {
                                            label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(context.parsed);
                                        }
                                        return label;
                                    }
                                }
                            }
                        },
                        cutout: '70%'
                    }
                });
            }

            // Fallback initialization check for Chart.js
            let checkChartInterval = setInterval(() => {
                if (typeof Chart !== 'undefined') {
                    clearInterval(checkChartInterval);
                    initChart();
                }
            }, 100);

            Livewire.hook('commit', ({ component, commit, respond, succeed, fail }) => {
                succeed(({ snapshot, effect }) => {
                    if (component.name === 'laporan.pajak-penjualan') {
                        setTimeout(() => {
                            if (typeof Chart !== 'undefined') {
                                initChart();
                            }
                        }, 50);
                    }
                });
            });
        });
    </script>
</x-filament-panels::page>