@php
    $viewData = $this->getViewData();
    $results = $viewData['results'];
    $grandTotalQty = $viewData['grandTotalQty'];
    $grandTotalValue = $viewData['grandTotalValue'];
    $fmt = function ($num) {
        if ($num == 0)
            return '0';
        return number_format($num, 0, ',', '.');
    };

    $chartLabels = $results->pluck('period_label')->toArray();
    $chartValueData = $results->pluck('total_value')->toArray();
    $chartQtyData = $results->pluck('total_qty')->toArray();
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
                <x-filament::icon icon="heroicon-m-funnel" class="w-4 h-4 mr-2" />
                {{ ucfirst($periodType) }}: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} —
                {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
            </div>
        </div>

        <div class="report-section">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th style="text-align: right;">Qty</th>
                        <th style="text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($results as $row)
                        <tr>
                            <td style="font-weight: 600; color: #3b82f6;">{{ $row->period_label }}</td>
                            <td style="text-align: right;">{{ number_format($row->total_qty, 0, ',', '.') }}</td>
                            <td style="text-align: right; color: #3b82f6; font-weight: 600;">{{ $fmt($row->total_value) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="text-align: center; padding: 3rem; color: #94a3b8;">Tidak ada data.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($results) > 0)
                    <tfoot>
                        <tr class="total-row">
                            <td style="padding: 1rem;">Total</td>
                            <td style="text-align: right; padding: 1rem;">{{ number_format($grandTotalQty, 0, ',', '.') }}
                            </td>
                            <td style="text-align: right; padding: 1rem; color: #3b82f6; font-weight: 700;">
                                {{ $fmt($grandTotalValue) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        <div class="charts-container">
            <div class="chart-card">
                <h3>NILAI PEMBELIAN</h3>
                <div style="height: 300px; position: relative;">
                    <canvas id="valueChart"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <h3>JUMLAH PEMBELIAN</h3>
                <div style="height: 300px; position: relative;">
                    <canvas id="qtyChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:initialized', () => {
            const labels = @json($chartLabels);
            const valueData = @json($chartValueData);
            const qtyData = @json($chartQtyData);

            // Value Chart
            new Chart(document.getElementById('valueChart'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Nilai',
                        data: valueData,
                        backgroundColor: '#2dd4bf',
                        borderRadius: 4,
                        maxBarThickness: 40
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
                            grid: { color: '#f1f5f9' },
                            ticks: {
                                color: '#94a3b8',
                                callback: (value) => 'Rp ' + (value / 1000000) + 'jt'
                            }
                        },
                        x: { grid: { display: false }, ticks: { color: '#94a3b8' } }
                    }
                }
            });

            // Quantity Chart
            new Chart(document.getElementById('qtyChart'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Qty',
                        data: qtyData,
                        backgroundColor: '#fbbf24',
                        borderRadius: 4,
                        maxBarThickness: 40
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
                            grid: { color: '#f1f5f9' },
                            ticks: { color: '#94a3b8' }
                        },
                        x: { grid: { display: false }, ticks: { color: '#94a3b8' } }
                    }
                }
            });
        });
    </script>
</x-filament-panels::page>