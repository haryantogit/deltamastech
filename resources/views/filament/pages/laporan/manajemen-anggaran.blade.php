<x-filament-panels::page>
    <style>
        .report-content {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .report-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .search-container {
            position: relative;
            flex: 1;
            min-width: 250px;
            max-width: 400px;
        }

        .search-input {
            width: 100%;
            padding: 0.5rem 1rem 0.5rem 2.5rem;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: white;
            font-size: 0.875rem;
            outline: none;
            transition: border-color 0.2s;
        }

        .dark .search-input {
            background: #1e293b;
            border-color: #334155;
            color: #f1f5f9;
        }

        .search-input:focus {
            border-color: #3b82f6;
        }

        .search-icon {
            position: absolute;
            left: 0.75rem; top: 50%; transform: translateY(-50%);
            color: #94a3b8;
        }

        .budget-header-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid #e2e8f0;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .dark .budget-header-card {
            background: #111827;
            border-color: #374151;
        }

        .metric-label {
            font-size: 0.75rem;
            color: #64748b;
            margin-bottom: 0.25rem;
            font-weight: 500;
        }

        .metric-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1e293b;
        }

        .dark .metric-value {
            color: #f1f5f9;
        }

        .progress-container {
            width: 100%;
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            margin-top: 0.5rem;
            overflow: hidden;
        }

        .dark .progress-container {
            background: #334155;
        }

        .progress-bar {
            height: 100%;
            border-radius: 4px;
            transition: width 0.3s ease;
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

        .percentage-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.125rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .empty-state {
            text-align: center;
            padding: 4rem;
            color: #94a3b8;
        }
    </style>

    @php
        $data = $this->getViewData();
        $budget = $data['budget'];
    @endphp

    <div class="report-content">
        @if($budget)
            <div class="budget-header-card">
                <div>
                    <div class="metric-label">Nama Anggaran</div>
                    <div class="metric-value">{{ $budget->name }}</div>
                    <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.25rem;">
                        Periode: {{ $budget->start_date->format('d/m/Y') }} — {{ $budget->end_date->format('d/m/Y') }}
                    </div>
                </div>
                <div>
                    <div class="metric-label">Total Target</div>
                    <div class="metric-value">Rp {{ number_format($data['total_target'], 0, ',', '.') }}</div>
                </div>
                <div>
                    <div class="metric-label">Total Realisasi (Aktual)</div>
                    <div class="metric-value">Rp {{ number_format($data['total_actual'], 0, ',', '.') }}</div>
                </div>
                <div>
                    @php
                        $totalPercent = $data['total_target'] > 0 ? ($data['total_actual'] / $data['total_target']) * 100 : 0;
                        $barColor = $totalPercent > 100 ? '#ef4444' : ($totalPercent > 80 ? '#f59e0b' : '#10b981');
                    @endphp
                    <div class="metric-label">Tingkat Pencapaian</div>
                    <div class="metric-value">{{ number_format($totalPercent, 1) }}%</div>
                    <div class="progress-container">
                        <div class="progress-bar" style="width: {{ min($totalPercent, 100) }}%; background: {{ $barColor }};"></div>
                    </div>
                </div>
            </div>

            <div class="report-toolbar">
                <div class="search-container">
                    <x-filament::icon icon="heroicon-m-magnifying-glass" class="w-5 h-5 search-icon" />
                    <input type="text" wire:model.live.debounce.500ms="search" placeholder="Cari akun..." class="search-input">
                </div>
            </div>

            <div class="report-section">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Kode Akun</th>
                            <th>Nama Akun</th>
                            <th class="number-col">Target</th>
                            <th class="number-col">Aktual</th>
                            <th class="number-col">Selisih</th>
                            <th style="width: 150px; text-align: center;">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data['items'] as $item)
                            <tr>
                                <td style="color: #64748b;">{{ $item->account_code }}</td>
                                <td style="font-weight: 500;">{{ $item->account_name }}</td>
                                <td class="number-col">{{ number_format($item->target, 0, ',', '.') }}</td>
                                <td class="number-col" style="font-weight: 600;">{{ number_format($item->actual, 0, ',', '.') }}</td>
                                <td class="number-col" style="color: {{ $item->difference < 0 ? '#ef4444' : '#64748b' }}">
                                    {{ number_format($item->difference, 0, ',', '.') }}
                                </td>
                                <td style="text-align: center;">
                                    @php
                                        $p = $item->percentage;
                                        $bg = $p > 100 ? '#fef2f2' : ($p > 80 ? '#fffbeb' : '#f0fdf4');
                                        $fg = $p > 100 ? '#ef4444' : ($p > 80 ? '#d97706' : '#16a34a');
                                    @endphp
                                    <span class="percentage-badge" style="background: {{ $bg }}; color: {{ $fg }}">
                                        {{ number_format($p, 1) }}%
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="empty-state">Tidak ada rincian akun untuk anggaran ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <div class="report-section empty-state">
                <x-filament::icon icon="heroicon-o-calculator" class="w-12 h-12 mx-auto mb-3 opacity-20" />
                <p>Belum ada anggaran yang dikelola. Silakan buat anggaran baru terlebih dahulu.</p>
                <div style="margin-top: 1.5rem;">
                    <a href="{{ \App\Filament\Resources\BudgetResource::getUrl('create') }}" 
                       style="background: #3b82f6; color: white; padding: 0.5rem 1rem; border-radius: 8px; font-size: 0.875rem; text-decoration: none;">
                        Mulai Kelola Anggaran
                    </a>
                </div>
            </div>
        @endif
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
