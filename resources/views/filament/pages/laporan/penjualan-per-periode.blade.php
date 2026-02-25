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
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
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
        }

        .dark .chart-title {
            color: #f1f5f9;
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
    </style>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    @php
        $viewData = $this->getViewData();
        $results = $viewData['results'];
        $grandTotalQty = $viewData['grandTotalQty'];
        $grandTotalAmount = $viewData['grandTotalAmount'];
        $chartLabels = $viewData['chartLabels'];
        $chartQtyData = $viewData['chartQtyData'];
        $chartAmountData = $viewData['chartAmountData'];
    @endphp

    <div class="report-content">
        <div class="filter-ribbon">
            <div class="date-badge" x-on:click="$dispatch('open-modal', { id: 'fi-modal-action-filter' })">
                <x-filament::icon icon="heroicon-m-calendar" class="w-4 h-4 mr-2" />
                @if($periodType === 'monthly')
                    Bulanan ({{ \Carbon\Carbon::parse($startDate)->format('M Y') }} —
                    {{ \Carbon\Carbon::parse($endDate)->format('M Y') }})
                @elseif($periodType === 'daily')
                    Harian ({{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} —
                    {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }})
                @else
                    Tahunan ({{ \Carbon\Carbon::parse($startDate)->format('Y') }} —
                    {{ \Carbon\Carbon::parse($endDate)->format('Y') }})
                @endif
            </div>
        </div>

        <div class="report-section">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th style="text-align: right; width: 20%;">Qty</th>
                        <th style="text-align: right; width: 25%;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($results as $row)
                        <tr>
                            <td style="font-weight: 500; color: #3b82f6;">{{ $row->period_label }}</td>
                            <td style="text-align: right;">{{ number_format($row->total_qty, 0, ',', '.') }}</td>
                            <td style="text-align: right; font-weight: 700;">
                                {{ number_format($row->total_amount, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="text-align: center; padding: 3rem; color: #94a3b8;">
                                Tidak ada data untuk periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td style="padding: 1rem;">Total</td>
                        <td style="text-align: right;">{{ number_format($grandTotalQty, 0, ',', '.') }}</td>
                        <td style="text-align: right; color: #3b82f6;">
                            {{ number_format($grandTotalAmount, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="charts-grid">
            <div class="chart-card">
                <h3 class="chart-title">Nilai Penjualan</h3>
                <div style="height: 300px;">
                    <canvas id="amountChart"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <h3 class="chart-title">Jumlah Penjualan</h3>
                <div style="height: 300px;">
                    <canvas id="qtyChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctxAmount = document.getElementById('amountChart').getContext('2d');
            const ctxQty = document.getElementById('qtyChart').getContext('2d');

            new Chart(ctxAmount, {
                type: 'bar',
                data: {
                    labels: {!! json_encode($chartLabels) !!},
                    datasets: [{
                        label: 'Nilai Penjualan',
                        data: {!! json_encode($chartAmountData) !!},
                        backgroundColor: '#2dd4bf',
                        borderRadius: 4,
                        hoverBackgroundColor: '#0ea5e9'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(128, 128, 128, 0.1)' },
                            ticks: {
                                callback: function (value) {
                                    return value.toLocaleString('id-ID');
                                },
                                font: { size: 10 }
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 10 } }
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
                        borderRadius: 4,
                        hoverBackgroundColor: '#eab308'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(128, 128, 128, 0.1)' },
                            ticks: {
                                font: { size: 10 }
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 10 } }
                        }
                    }
                }
            });
        });
    </script>
</x-filament-panels::page>