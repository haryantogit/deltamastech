<x-filament-panels::page>
    <style>
        .report-content {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .filter-ribbon {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            margin-bottom: -1rem;
        }

        .date-badge {
            display: flex;
            align-items: center;
            padding: 0.5rem 1rem;
            background: white;
            border-radius: 9999px;
            border: 1px solid #e2e8f0;
            font-size: 0.8125rem;
            font-weight: 500;
            color: #64748b;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            cursor: pointer;
        }

        .dark .date-badge {
            background: #1e293b;
            border-color: #334155;
            color: #94a3b8;
        }

        .report-section {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        .dark .report-section {
            background: #111827;
            border-color: #374151;
        }

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
        }

        .report-table th {
            padding: 0.875rem 1.25rem;
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
            padding: 0.75rem 1.25rem;
            font-size: 0.8125rem;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
        }

        .dark .report-table td {
            border-bottom-color: #374151;
            color: #e2e8f0;
        }

        .total-row {
            border-top: 2px solid rgba(128, 128, 128, 0.2);
            background: #f8fafc;
            font-weight: 700;
        }

        .dark .total-row {
            background: rgba(255, 255, 255, 0.05);
            border-top-color: #374151;
        }

        .total-row td {
            padding: 1rem 1.25rem !important;
            border-top: none !important;
            background: transparent !important;
            font-weight: 700;
        }

        .dark .total-row td {
            background: transparent !important;
        }

        .toggle-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.5rem;
            height: 1.5rem;
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 6px;
            margin-right: 0.5rem;
            font-size: 1rem;
            color: #3b82f6;
            background: rgba(59, 130, 246, 0.1);
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 800;
            line-height: 0;
            user-select: none;
        }

        .toggle-btn:hover {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
            transform: scale(1.05);
        }

        .dark .toggle-btn {
            background: rgba(59, 130, 246, 0.2);
            border-color: rgba(59, 130, 246, 0.3);
            color: #60a5fa;
        }

        .dark .toggle-btn:hover {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        .detail-header th {
            padding: 0.625rem 1.25rem 0.625rem 4rem !important;
            font-size: 0.70rem !important;
            color: #94a3b8 !important;
            font-weight: 700 !important;
            text-transform: capitalize;
            background: rgba(0, 0, 0, 0.02) !important;
            border-bottom: 1px solid #f1f5f9 !important;
        }

        .dark .detail-header th {
            background: rgba(255, 255, 255, 0.02) !important;
            border-bottom-color: #374151 !important;
        }

        .detail-row {
            border-bottom: 1px solid #f8fafc;
        }

        .dark .detail-row {
            background: rgba(255, 255, 255, 0.01);
            border-bottom-color: rgba(255, 255, 255, 0.05);
        }

        .charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            width: 100%;
            box-sizing: border-box;
            margin-bottom: 0;
        }

        .chart-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
            overflow: hidden;
            width: 100%;
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

        .dark .chart-title {
            color: #f1f5f9;
        }

        .chart-wrapper {
            width: 100%;
            max-width: 500px;
        }

        .report-layout {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
    </style>

    @php
        $data = $this->getViewData();
        $paginator = $data['paginator'];
        $summary = $data['summary'];
        $details = $data['details'];
        $totalAll = $data['totalAll'];
        $chartLabels = $data['chartLabels'];
        $chartValues = $data['chartValues'];
    @endphp

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <div class="report-content">

        <div class="charts-container" wire:key="biaya-chart-{{ md5(json_encode($chartValues)) }}" x-data="{
                labels: {{ json_encode($chartLabels) }},
                values: {{ json_encode($chartValues) }},
                init() {
                    let checkChartInterval = setInterval(() => {
                        if (typeof Chart !== 'undefined') {
                            clearInterval(checkChartInterval);
                            this.renderChart();
                        }
                    }, 100);
                },
                renderChart() {
                    const ctx = document.getElementById('expenseChart').getContext('2d');
                    if (window.expenseChartInstance) {
                        window.expenseChartInstance.destroy();
                    }
                    
                    window.expenseChartInstance = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: this.labels,
                            datasets: [{
                                data: this.values,
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
                                        font: { size: 11, weight: '600' }
                                    }
                                },
                                tooltip: {
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
                <h3>BIAYA PER KONTAK (TOP 10)</h3>
                <div style="height: 300px; position: relative; width: 100%;">
                    <canvas id="expenseChart"></canvas>
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

            <div style="overflow-x: auto; margin-bottom: 1rem;">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th style="width: 40px; border-bottom: 2px solid #f1f5f9;"></th>
                            <th style="padding-left: 0; min-width: 200px; border-bottom: 2px solid #f1f5f9;">Kontak</th>
                            <th style="text-align: right; min-width: 150px; border-bottom: 2px solid #f1f5f9;">
                                Pengeluaran</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($summary as $row)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors" style="cursor: pointer;"
                                wire:key="row-{{ $row->id }}" wire:click="toggleRow({{ $row->id }})">
                                <td>
                                    @php $isExpanded = in_array($row->id, $expandedRows); @endphp
                                    <span class="toggle-btn">{{ $isExpanded ? '−' : '+' }}</span>
                                </td>
                                <td style="padding-left: 0; font-weight: 800; color: #3b82f6;">
                                    {{ $row->name }}
                                </td>
                                <td style="text-align: right; color: #1e293b; font-weight: 700;" class="dark:text-gray-100">
                                    {{ number_format($row->total_expenses, 0, ',', '.') }}
                                </td>
                            </tr>

                            @if(in_array($row->id, $expandedRows))
                                <tr class="detail-header" wire:key="header-{{ $row->id }}">
                                    <th colspan="2" style="padding-left: 4.5rem !important;">Nomor</th>
                                    <th style="text-align: right;">Total</th>
                                </tr>
                                @foreach($details[$row->id] ?? [] as $detail)
                                    <tr class="detail-row" wire:key="detail-{{ $detail->id }}">
                                        <td colspan="2" style="padding-left: 4.5rem !important;">
                                            <div class="flex flex-col">
                                                <span
                                                    style="font-size: 0.70rem; color: #94a3b8; font-weight: 500;">{{ \Carbon\Carbon::parse($detail->date)->format('d/m/Y') }}</span>
                                                <a href="{{ \App\Filament\Resources\ExpenseResource::getUrl('view', ['record' => $detail->id]) }}"
                                                    target="_blank"
                                                    style="color: #3b82f6; text-decoration: none; font-weight: 700;">
                                                    {{ $detail->number }}
                                                </a>
                                            </div>
                                        </td>
                                        <td style="text-align: right; color: #1e293b; font-weight: 600;">
                                            {{ number_format($detail->total, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        @empty
                            <tr>
                                <td colspan="3" style="text-align: center; padding: 4rem; color: #94a3b8;">
                                    <div class="flex flex-col items-center">
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

                        @if(count($summary) > 0)
                            <tr class="total-row">
                                <td colspan="2" style="padding-left: 1.25rem !important;">Total
                                </td>
                                <td style="text-align: right; color: #3b82f6;">{{ number_format($totalAll, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if ($paginator->hasPages() || count([5, 10, 20, 50, 100, 'all']) > 1)
        <div style="margin-top: 2rem; margin-bottom: 1rem;">
            <x-filament::pagination :paginator="$paginator" :page-options="[5, 10, 20, 50, 100, 'all']"
                current-page-option-property="perPage" />
        </div>
    @endif
</x-filament-panels::page>