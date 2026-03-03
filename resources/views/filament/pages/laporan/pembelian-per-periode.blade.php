@php
    $viewData = $this->getViewData();
    $results = $viewData['results'];
    $chartResults = $viewData['chartResults'];
    $grandTotalQty = $viewData['grandTotalQty'];
    $grandTotalValue = $viewData['grandTotalValue'];
    $fmt = function ($num) {
        if ($num == 0)
            return '0';
        return number_format($num, 0, ',', '.');
    };

    $chartLabels = $chartResults->pluck('period_label')->toArray();
    $chartValueData = $chartResults->pluck('total_value')->toArray();
    $chartQtyData = $chartResults->pluck('total_qty')->toArray();
@endphp

<x-filament-panels::page>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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

        /* Search row */
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

        .report-table {
            width: 100%;
            border-collapse: collapse;
        }

        .report-table th {
            padding: 1.25rem;
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
            background: #1f2937;
        }

        .charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 1.5rem;
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

        @media print {
            .filter-search-row, .pagination-container {
                display: none !important;
            }
        }
    </style>

    <div class="report-content">

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

        <div class="delivery-report-container">
            {{-- Search row --}}
            <div class="filter-search-row">
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
                                <td colspan="3" style="text-align: center; padding: 3rem; color: #94a3b8;">Tidak ada data.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if (count($results) > 0)
                        <tfoot>
                            <tr class="total-row">
                                <td style="padding: 1rem 1.25rem;">Total</td>
                                <td style="text-align: right; padding: 1rem 1.25rem;">
                                    {{ number_format($grandTotalQty, 0, ',', '.') }}
                                </td>
                                <td style="text-align: right; padding: 1rem 1.25rem; color: #3b82f6; font-weight: 700;">
                                    {{ $fmt($grandTotalValue) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        @if ($results->hasPages() || count([5, 10, 20, 50, 100, 'all']) > 1)
            <div style="margin-top: 2rem; margin-bottom: 1rem;">
                <x-filament::pagination :paginator="$results" :page-options="[5, 10, 20, 50, 100, 'all']"
                    current-page-option-property="perPage" />
            </div>
        @endif
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
                        backgroundColor: '#3b82f6',
                        borderRadius: 4,
                        maxBarThickness: 40
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#f1f5f9'
                            },
                            ticks: {
                                color: '#94a3b8',
                                callback: (value) => 'Rp ' + (value / 1000000) + 'jt'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#94a3b8'
                            }
                        }
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
                        backgroundColor: '#10b981',
                        borderRadius: 4,
                        maxBarThickness: 40
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#f1f5f9'
                            },
                            ticks: {
                                color: '#94a3b8'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#94a3b8'
                            }
                        }
                    }
                }
            });
        });
    </script>
</x-filament-panels::page>