@php
    $viewData = $this->getViewData();
    $results = $viewData['results'];
    $chartResults = $viewData['chartResults'];
    $grandTotalQty = $viewData['grandTotalQty'];
    $grandTotalAmount = $viewData['grandTotalAmount'];

    $chartLabels = $chartResults->pluck('period_label')->toArray();
    $chartQtyData = $chartResults->pluck('total_qty')->toArray();
    $chartAmountData = $chartResults->pluck('total_amount')->toArray();

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
            min-width: 800px;
        }

        .report-table th {
            padding: 1.25rem;
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
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
            padding: 1rem 1.25rem;
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

        /* Charts Container */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .chart-card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        .dark .chart-card {
            background: #111827;
            border-color: #374151;
        }

        .chart-title {
            font-size: 0.875rem;
            font-weight: 700;
            color: #1e293b;
            text-transform: uppercase;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .dark .chart-title {
            color: #f1f5f9;
        }

        @media print {

            .charts-grid,
            .fi-header-actions,
            .pagination-container {
                display: none !important;
            }
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <div class="report-content">
        <div class="charts-grid">
            <div class="chart-card">
                <h3 class="chart-title">
                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                    NILAI PENJUALAN
                </h3>
                <div style="height: 350px;">
                    <canvas id="amountChart"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <h3 class="chart-title">
                    <span class="w-2 h-2 rounded-full bg-yellow-500"></span>
                    JUMLAH PENJUALAN (QTY)
                </h3>
                <div style="height: 350px;">
                    <canvas id="qtyChart"></canvas>
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
                            <th>TANGGAL</th>
                            <th style="text-align: right; width: 20%;">QTY</th>
                            <th style="text-align: right; width: 25%;">TOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($results as $row)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                                <td style="font-weight: 600; color: #3b82f6;">{{ $row->period_label }}</td>
                                <td style="text-align: right;">{{ $fmt($row->total_qty) }}</td>
                                <td style="text-align: right; font-weight: 700;">
                                    {{ $fmt($row->total_amount) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" style="text-align: center; padding: 4rem; color: #94a3b8;">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 mb-3 opacity-20" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 002-2h2a2 2 0 002-2">
                                            </path>
                                        </svg>
                                        <span>Tidak ada data untuk periode ini.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="total-row border-t-2 border-gray-100 dark:border-gray-700">
                            <td style="padding: 1rem 1.25rem;">TOTAL</td>
                            <td style="text-align: right; padding: 1rem 1.25rem;">{{ $fmt($grandTotalQty) }}</td>
                            <td style="text-align: right; color: #3b82f6; padding: 1rem 1.25rem;">
                                {{ $fmt($grandTotalAmount) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        @if ($results->hasPages() || count([5, 10, 20, 50, 100, 'all']) > 1)
            <div style="margin-top: 2rem; margin-bottom: 1rem;" class="pagination-row">
                <x-filament::pagination :paginator="$results" :page-options="[5, 10, 20, 50, 100, 'all']"
                    current-page-option-property="perPage" />
            </div>
        @endif
    </div>

    <script>
        document.addEventListener('livewire:initialized', function () {
            const ctxAmount = document.getElementById('amountChart').getContext('2d');
            const ctxQty = document.getElementById('qtyChart').getContext('2d');

            // Gradient for Amount Chart
            const amountGradient = ctxAmount.createLinearGradient(0, 0, 0, 350);
            amountGradient.addColorStop(0, 'rgba(59, 130, 246, 0.4)');
            amountGradient.addColorStop(1, 'rgba(59, 130, 246, 0)');

            new Chart(ctxAmount, {
                type: 'line',
                data: {
                    labels: @json($chartLabels),
                    datasets: [{
                        label: 'Nilai Penjualan',
                        data: @json($chartAmountData),
                        borderColor: '#3b82f6',
                        backgroundColor: amountGradient,
                        fill: true,
                        tension: 0.4,
                        borderWidth: 3,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#3b82f6',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index',
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'white',
                            titleColor: '#1e293b',
                            bodyColor: '#1e293b',
                            borderColor: '#e2e8f0',
                            borderWidth: 1,
                            padding: 12,
                            displayColors: false,
                            callbacks: {
                                label: function (context) {
                                    return 'Nilai: Rp ' + context.parsed.y.toLocaleString('id-ID');
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(148, 163, 184, 0.1)', drawBorder: false },
                            ticks: {
                                callback: function (value) {
                                    if (value >= 1000000) return (value / 1000000) + 'jt';
                                    if (value >= 1000) return (value / 1000) + 'rb';
                                    return value;
                                },
                                font: { size: 10, weight: '500' },
                                color: '#94a3b8'
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 10, weight: '500' }, color: '#94a3b8' }
                        }
                    }
                }
            });

            new Chart(ctxQty, {
                type: 'bar',
                data: {
                    labels: {!! json_encode($chartLabels) !!},
                    datasets: [{
                        label: 'Jumlah Penjualan',
                        data: {!! json_encode($chartQtyData) !!},
                        backgroundColor: '#facc15',
                        hoverBackgroundColor: '#eab308',
                        borderRadius: 6,
                        barThickness: 30
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'white',
                            titleColor: '#1e293b',
                            bodyColor: '#1e293b',
                            borderColor: '#e2e8f0',
                            borderWidth: 1,
                            padding: 12,
                            displayColors: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(148, 163, 184, 0.1)', drawBorder: false },
                            ticks: {
                                font: { size: 10, weight: '500' },
                                color: '#94a3b8'
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 10, weight: '500' }, color: '#94a3b8' }
                        }
                    }
                }
            });
        });
    </script>
</x-filament-panels::page>