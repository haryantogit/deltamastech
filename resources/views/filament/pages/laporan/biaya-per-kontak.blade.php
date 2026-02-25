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
            background: rgba(255, 255, 255, 0.02);
        }

        .expand-btn {
            cursor: pointer;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s;
            background: #3b82f6;
            color: white;
            border: none;
        }

        .expand-btn:hover {
            background: #2563eb;
            transform: scale(1.1);
        }

        .detail-header {
            background: #fcfcfc !important;
            border-top: 1px solid #f1f5f9;
        }

        .dark .detail-header {
            background: rgba(255, 255, 255, 0.02) !important;
            border-top-color: #374151;
        }

        .detail-row {
            background: white;
        }

        .dark .detail-row {
            background: #111827;
        }

        .detail-row td {
            padding: 0.75rem 1rem !important;
            font-size: 0.75rem !important;
        }

        .trx-link {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }

        .trx-link:hover {
            text-decoration: underline;
        }

        .chart-container {
            padding: 2rem;
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.5rem;
            width: 100%;
        }

        .dark .chart-container {
            background: #111827;
            border-color: #374151;
        }

        .chart-title {
            align-self: flex-start;
            font-size: 0.875rem;
            font-weight: 700;
            color: #1e293b;
            text-transform: uppercase;
            letter-spacing: 0.025em;
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
        $summary = $data['summary'];
        $details = $data['details'];
        $totalAll = $data['totalAll'];
        $chartLabels = $data['chartLabels'];
        $chartValues = $data['chartValues'];
    @endphp

    <div class="report-content">
        <div class="filter-ribbon">
            <div class="date-badge" x-on:click="$dispatch('open-modal', { id: 'fi-modal-action-filter' })">
                <x-filament::icon icon="heroicon-m-calendar" class="w-4 h-4 mr-2" />
                {{ \Carbon\Carbon::parse($startDate)->format('d/M/Y') }} —
                {{ \Carbon\Carbon::parse($endDate)->format('d/M/Y') }}
            </div>
        </div>

        <div class="report-layout">
            <div class="report-section">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;"></th>
                            <th>Kontak</th>
                            <th style="text-align: right;">Pengeluaran</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($summary as $row)
                            <tr wire:key="row-{{ $row->id }}">
                                <td style="vertical-align: middle; text-align: center;">
                                    <div class="flex justify-center">
                                        <div class="expand-btn" wire:click="toggleRow({{ $row->id }})">
                                            <x-filament::icon 
                                                :icon="in_array($row->id, $expandedRows) ? 'heroicon-m-minus' : 'heroicon-m-plus'" 
                                                class="w-3 h-3" 
                                            />
                                        </div>
                                    </div>
                                </td>
                                <td style="font-weight: 500;">{{ $row->name }}</td>
                                <td style="text-align: right; color: #3b82f6; font-weight: 600;">
                                    {{ number_format($row->total_expenses, 0, ',', '.') }}
                                </td>
                            </tr>

                            @if(in_array($row->id, $expandedRows))
                                <tr class="detail-header" wire:key="header-{{ $row->id }}">
                                    <td></td>
                                    <td style="font-weight: 600; font-size: 0.75rem; color: #475569;">Nomor</td>
                                    <td style="font-weight: 600; font-size: 0.75rem; color: #475569; text-align: right;">Total</td>
                                </tr>
                                @foreach($details[$row->id] ?? [] as $detail)
                                    <tr class="detail-row" wire:key="detail-{{ $detail->id }}">
                                        <td></td>
                                        <td>
                                            <a href="{{ \App\Filament\Resources\ExpenseResource::getUrl('view', ['record' => $detail->id]) }}" 
                                               target="_blank" class="trx-link">
                                                {{ $detail->number }}
                                            </a>
                                        </td>
                                        <td style="text-align: right; color: #64748b;">
                                            {{ number_format($detail->total, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        @empty
                            <tr>
                                <td colspan="3" style="text-align: center; color: #94a3b8; padding: 2rem;">
                                    Tidak ada data untuk periode ini.
                                </td>
                            </tr>
                        @endforelse

                        @if($summary->count() > 0)
                            <tr class="total-row">
                                <td></td>
                                <td style="text-align: right; color: #64748b; font-size: 0.75rem;">Total {{ $summary->count() }} data</td>
                                <td style="text-align: right;">{{ number_format($totalAll, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <div class="chart-container">
                <div class="chart-title">BIAYA PER KONTAK</div>
                <div class="chart-wrapper" 
                     x-data="{
                        chart: null,
                        initChart() {
                            if (this.chart) this.chart.destroy();
                            const ctx = $refs.canvas.getContext('2d');
                            this.chart = new Chart(ctx, {
                                type: 'doughnut',
                                data: {
                                    labels: {{ json_encode($chartLabels) }},
                                    datasets: [{
                                        data: {{ json_encode($chartValues) }},
                                        backgroundColor: [
                                            '#ff5b77', '#ffcc5c', '#48c3d3', '#3d91cf', '#b983ff', 
                                            '#9c89ff', '#7b61ff', '#5b47ff', '#3d2eff', '#1f14ff'
                                        ],
                                        borderWidth: 0
                                    }]
                                },
                                options: {
                                    cutout: '70%',
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            position: 'bottom',
                                            labels: {
                                                usePointStyle: true,
                                                boxWidth: 8,
                                                padding: 20,
                                                font: { size: 11, family: 'Inter, sans-serif' }
                                            }
                                        }
                                    }
                                }
                            });
                        }
                     }"
                     x-init="initChart()"
                     wire:ignore
                >
                    <div style="height: 300px; width: 100%;">
                        <canvas x-ref="canvas"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @endpush
</x-filament-panels::page>
