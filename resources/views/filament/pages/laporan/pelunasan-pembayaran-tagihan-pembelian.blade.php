@php
    $viewData = $this->getViewData();
    $items = $viewData['items'];
    $paginator = $viewData['paginator'];
    $grandTotal = $viewData['grandTotal'];
    $chartLabels = $viewData['chartLabels'];
    $chartAmountData = $viewData['chartAmountData'];

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
            margin-bottom: 1rem;
        }

        .dark .delivery-report-container {
            background: #111827;
            border-color: #374151;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }

        .report-table th {
            padding: 0.875rem 1.25rem;
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            text-transform: capitalize;
            background: #f8fafc;
            border-bottom: 2px solid #f1f5f9;
        }

        .dark .report-table th {
            background: #1f2937;
            border-bottom-color: #374151;
            color: #94a3b8;
        }

        .report-table td {
            padding: 0.75rem 1.25rem;
            font-size: 0.8125rem;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
        }

        .dark .report-table td {
            border-bottom-color: #374151;
            color: #e2e8f0;
        }

        .doc-link {
            color: #3b82f6;
            font-weight: 700;
            text-decoration: none;
        }

        .doc-link:hover {
            text-decoration: underline;
        }

        .charts-grid {
            margin-bottom: 1.5rem;
        }

        .chart-card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            padding: 1rem 1.25rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
            box-sizing: border-box;
            width: 100%;
            overflow: hidden;
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

        /* Filter Row */
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

        .custom-search-input:focus {
            border-color: #3b82f6;
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

        @media print {

            .filter-search-row,
            .pagination-row,
            .charts-grid {
                display: none !important;
            }
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <div class="report-content">
        {{-- Chart Section --}}
        <div class="charts-grid" x-data="{
            labels: @js($chartLabels),
            amounts: @js($chartAmountData),
            chart: null,
            init() {
                const ctx = document.getElementById('mainChart').getContext('2d');
                const gradient = ctx.createLinearGradient(0, 0, 0, 300);
                gradient.addColorStop(0, 'rgba(59, 130, 246, 0.4)');
                gradient.addColorStop(1, 'rgba(59, 130, 246, 0)');

                if (window.myChart) {
                    window.myChart.destroy();
                }

                window.myChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: this.labels,
                        datasets: [{
                            label: 'Nilai Transaksi',
                            data: this.amounts,
                            borderColor: '#3b82f6',
                            backgroundColor: gradient,
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
                                        return 'Total: Rp ' + context.parsed.y.toLocaleString('id-ID');
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0,
                                    maxTicksLimit: 6,
                                    callback: function (value) {
                                        if (value >= 1000000) return (value / 1000000) + 'jt';
                                        if (value >= 1000) return (value / 1000) + 'rb';
                                        return value;
                                    },
                                    font: { size: 10, weight: '500' },
                                    color: '#94a3b8'
                                },
                                grid: { color: 'rgba(148, 163, 184, 0.1)', drawBorder: false }
                            },
                            x: {
                                grid: { display: false },
                                ticks: { 
                                    font: { size: 10, weight: '500' }, 
                                    color: '#94a3b8'
                                }
                            }
                        },
                        layout: {
                            padding: {
                                left: 10,
                                right: 30,
                                top: 10,
                                bottom: 10
                            }
                        }
                    }
                });
            }
        }">
            <div class="chart-card">
                <h3 class="chart-title">
                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                    {{ $viewData['chartTitle'] ?? 'TREN PELUNASAN PEMBAYARAN' }}
                </h3>
                <div style="height: 260px; position: relative; width: 100%;">
                    <canvas id="mainChart"></canvas>
                </div>
            </div>
        </div>

        <div class="delivery-report-container">
            {{-- Search Row --}}
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

            {{-- Table --}}
            <div style="overflow-x: auto;">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th style="text-align: left;">Nomor</th>
                            <th style="text-align: left;">Nama</th>
                            <th style="text-align: left;">Tanggal Tagihan</th>
                            <th style="text-align: left;">Tanggal Pembayaran Pertama</th>
                            <th style="text-align: left;">Tanggal Pelunasan</th>
                            <th style="text-align: right;">Total Tagihan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $i)
                            <tr>
                                <td><span class="doc-link">{{ $i['number'] }}</span></td>
                                <td>{{ $i['supplier_name'] }}</td>
                                <td style="color: #64748b;">{{ $i['invoice_date'] }}</td>
                                <td style="color: #64748b;">{{ $i['display_first_payment'] }}</td>
                                <td style="color: #64748b;">{{ $i['display_full_settlement'] }}</td>
                                <td style="text-align: right; font-weight: 600;">{{ $fmt($i['total_amount']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 4rem; color: #94a3b8;">
                                    <div
                                        style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                        <svg style="width: 48px; height: 48px; margin-bottom: 1rem; opacity: 0.2;"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <span style="font-size: 0.875rem; font-weight: 500;">Tidak ada data untuk periode
                                            ini.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse

                        {{-- Total Row --}}
                        <tr style="border-top: 2px solid rgba(128,128,128,0.2); background: #f8fafc;"
                            class="dark:bg-white/5 font-bold">
                            <td colspan="5" style="padding: 1rem 1.25rem;">Grand Total</td>
                            <td style="text-align: right; padding: 1rem 1.25rem; color: #3b82f6;">
                                <div x-data="{ show: false }">
                                    <span x-show="show">{{ $fmt($grandTotal) }}</span>
                                    <a x-show="!show" @click="show = true" class="btn-lihat-total"
                                        style="justify-content: flex-end;">
                                        Lihat Total <x-filament::icon icon="heroicon-m-eye" class="w-3 h-3" />
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        @if ($paginator->hasPages() || count([5, 10, 20, 50, 100, 'all']) > 1)
            <div style="margin-top: 2rem; margin-bottom: 1rem;" class="pagination-row">
                <x-filament::pagination :paginator="$paginator" :page-options="[5, 10, 20, 50, 100, 'all']"
                    current-page-option-property="perPage" />
            </div>
        @endif
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>