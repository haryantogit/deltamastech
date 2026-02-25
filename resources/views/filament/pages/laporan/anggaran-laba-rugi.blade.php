<x-filament-panels::page>
    @if (!$budget)
        <div class="flex flex-col items-center justify-center min-h-[400px] py-12 px-6">
            <div
                class="w-full max-w-md p-8 text-center bg-white border border-gray-200 shadow-xl rounded-2xl dark:bg-gray-900/50 dark:border-gray-800 backdrop-blur-sm">
                <div
                    class="relative flex items-center justify-center w-24 h-24 mx-auto mb-6 bg-blue-50 rounded-2xl dark:bg-blue-900/20">
                    <svg width="48" height="48" class="text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24" style="width: 48px; height: 48px;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                    </svg>
                    <div
                        class="absolute -right-2 -bottom-2 flex items-center justify-center w-10 h-10 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-100 dark:border-gray-700">
                        <svg width="20" height="20" class="text-blue-500" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24" style="width: 20px; height: 20px;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>

                <h3 class="mb-2 text-xl font-extrabold tracking-tight text-gray-900 dark:text-white">Belum Ada Anggaran
                    Terpilih</h3>
                <p class="mb-8 text-base leading-relaxed text-gray-500 dark:text-gray-400">
                    Silakan pilih anggaran melalui tombol <span
                        class="font-bold text-gray-700 dark:text-gray-300">Filter</span> di atas atau buat anggaran baru
                    untuk mulai melacak performa keuangan bisnis Anda.
                </p>

                <div class="flex flex-col gap-3">
                    <x-filament::button color="primary" icon="heroicon-m-plus" tag="a" size="lg"
                        href="{{ \App\Filament\Resources\BudgetResource::getUrl('create') }}"
                        class="w-full shadow-lg shadow-blue-500/20">
                        Buat Anggaran Baru
                    </x-filament::button>

                    <x-filament::button color="gray" outlined tag="a" size="lg"
                        href="{{ \App\Filament\Pages\AnggaranPage::getUrl() }}" class="w-full">
                        Kembali ke Portal Anggaran
                    </x-filament::button>
                </div>
            </div>
        </div>
    @else
        <style>
            .report-card {
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 20px;
            }

            .dark .report-card {
                background: #1e293b;
                border-color: #334155;
            }

            .section-title {
                font-size: 14px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                margin-bottom: 16px;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .progress-bg {
                background: #e2e8f0;
                border-radius: 4px;
                height: 8px;
                width: 100%;
                overflow: hidden;
            }

            .dark .progress-bg {
                background: #334155;
            }

            .progress-bar {
                height: 100%;
                border-radius: 4px;
                transition: width 0.3s ease;
            }

            .variance-table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 0;
            }

            .variance-table th {
                text-align: left;
                padding: 12px;
                font-size: 12px;
                color: #64748b;
                border-bottom: 2px solid #f1f5f9;
            }

            .dark .variance-table th {
                color: #94a3b8;
                border-color: #334155;
            }

            .variance-table td {
                padding: 12px;
                font-size: 13px;
                border-bottom: 1px solid #f1f5f9;
            }

            .dark .variance-table td {
                border-color: #334155;
            }

            .money {
                font-family: 'JetBrains Mono', monospace;
                font-weight: 500;
            }
        </style>

        {{-- Header Info --}}
        <div class="grid grid-cols-1 gap-4 mb-6 md:grid-cols-3">
            <div class="report-card">
                <span class="text-xs font-bold tracking-wider text-gray-500 uppercase">Nama Anggaran</span>
                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $budget->name }}</p>
            </div>
            <div class="report-card">
                <span class="text-xs font-bold tracking-wider text-gray-500 uppercase">Periode</span>
                <p class="text-lg font-bold text-gray-900 dark:text-white">
                    {{ \Carbon\Carbon::parse($budget->start_date)->format('M Y') }} -
                    {{ \Carbon\Carbon::parse($budget->end_date)->format('M Y') }}
                </p>
            </div>
            <div class="report-card">
                <span class="text-xs font-bold tracking-wider text-gray-500 uppercase">Tipe Periode</span>
                <p class="text-lg font-bold text-gray-900 dark:text-white">
                    {{ $budget->period_type === 'monthly' ? 'Bulanan' : 'Tahunan' }}
                </p>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 gap-4 mb-8 md:grid-cols-4">
            {{-- Pendapatan --}}
            <div class="report-card">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold text-blue-600 uppercase dark:text-blue-400">Total Pendapatan</span>
                    @php $revPct = $summary['totalBudgetRevenue'] > 0 ? ($summary['totalActualRevenue'] / $summary['totalBudgetRevenue']) * 100 : 0; @endphp
                    <span
                        class="text-xs font-bold px-2 py-0.5 rounded {{ $revPct >= 100 ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' }}">
                        {{ round($revPct) }}%
                    </span>
                </div>
                <div class="flex flex-col">
                    <span class="text-2xl font-bold money">Rp
                        {{ number_format($summary['totalActualRevenue'], 0, ',', '.') }}</span>
                    <span class="text-xs text-gray-500">Target: Rp
                        {{ number_format($summary['totalBudgetRevenue'], 0, ',', '.') }}</span>
                </div>
            </div>

            {{-- Laba Kotor --}}
            <div class="report-card">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold text-emerald-600 uppercase dark:text-emerald-400">Laba Kotor</span>
                    @php $lkPct = $summary['labaKotorBudget'] > 0 ? ($summary['labaKotorActual'] / $summary['labaKotorBudget']) * 100 : 0; @endphp
                    <span
                        class="text-xs font-bold px-2 py-0.5 rounded {{ $lkPct >= 100 ? 'bg-green-100 text-green-700' : 'bg-emerald-100 text-emerald-700' }}">
                        {{ round($lkPct) }}%
                    </span>
                </div>
                <div class="flex flex-col">
                    <span class="text-2xl font-bold money">Rp
                        {{ number_format($summary['labaKotorActual'], 0, ',', '.') }}</span>
                    <span class="text-xs text-gray-500">Target: Rp
                        {{ number_format($summary['labaKotorBudget'], 0, ',', '.') }}</span>
                </div>
            </div>

            {{-- Total Biaya --}}
            <div class="report-card">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold text-amber-600 uppercase dark:text-amber-400">Total Biaya</span>
                    @php $exPct = $summary['totalBudgetExpense'] > 0 ? ($summary['totalActualExpense'] / $summary['totalBudgetExpense']) * 100 : 0; @endphp
                    <span
                        class="text-xs font-bold px-2 py-0.5 rounded {{ $exPct > 100 ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700' }}">
                        {{ round($exPct) }}%
                    </span>
                </div>
                <div class="flex flex-col">
                    <span class="text-2xl font-bold money">Rp
                        {{ number_format($summary['totalActualExpense'], 0, ',', '.') }}</span>
                    <span class="text-xs text-gray-500">Plafon: Rp
                        {{ number_format($summary['totalBudgetExpense'], 0, ',', '.') }}</span>
                </div>
            </div>

            {{-- Laba Bersih --}}
            <div class="report-card">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold text-indigo-600 uppercase dark:text-indigo-400">Laba Bersih</span>
                    @php $lbPct = $summary['labaBersihBudget'] > 0 ? ($summary['labaBersihActual'] / $summary['labaBersihBudget']) * 100 : 0; @endphp
                    <span
                        class="text-xs font-bold px-2 py-0.5 rounded {{ $lbPct >= 100 ? 'bg-green-100 text-green-700' : 'bg-indigo-100 text-indigo-700' }}">
                        {{ round($lbPct) }}%
                    </span>
                </div>
                <div class="flex flex-col">
                    <span class="text-2xl font-bold money">Rp
                        {{ number_format($summary['labaBersihActual'], 0, ',', '.') }}</span>
                    <span class="text-xs text-gray-500">Target: Rp
                        {{ number_format($summary['labaBersihBudget'], 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        {{-- Detailed Sections --}}
        <div class="space-y-8">
            @foreach ($sections as $section)
                <div class="report-card">
                    <div class="section-title" style="color: {{ $section['style']['color'] }}">
                        <div class="w-2 h-6 rounded-full" style="background: {{ $section['style']['color'] }}"></div>
                        {{ $section['category'] }}
                    </div>

                    <div class="overflow-x-auto">
                        <table class="variance-table">
                            <thead>
                                <tr>
                                    <th width="30%">Akun</th>
                                    <th width="15%" class="text-right">Anggaran</th>
                                    <th width="15%" class="text-right">Aktual</th>
                                    <th width="15%" class="text-right">Selisih</th>
                                    <th width="25%">Pencapaian (%)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($section['rows'] as $row)
                                    <tr>
                                        <td>
                                            <div class="flex flex-col">
                                                <span class="font-bold text-gray-900 dark:text-white">{{ $row['account'] }}</span>
                                                <span class="text-xs text-gray-400">{{ $row['code'] }}</span>
                                            </div>
                                        </td>
                                        <td class="text-right font-medium money">Rp {{ number_format($row['budget'], 0, ',', '.') }}
                                        </td>
                                        <td class="text-right font-medium money">Rp {{ number_format($row['actual'], 0, ',', '.') }}
                                        </td>
                                        <td
                                            class="text-right font-medium money {{ $row['diff'] < 0 ? 'text-red-500' : 'text-green-500' }}">
                                            Rp {{ number_format(abs($row['diff']), 0, ',', '.') }}
                                            <span class="text-[10px]">{{ $row['diff'] < 0 ? '▲' : '▼' }}</span>
                                        </td>
                                        <td>
                                            <div class="flex items-center gap-3">
                                                <div class="progress-bg">
                                                    @php
                                                        $pct = min($row['percentage'], 100);
                                                        $barColor = $row['percentage'] >= 100 ? 'bg-green-500' : ($row['percentage'] > 80 ? 'bg-blue-500' : 'bg-amber-500');
                                                        if (str_contains($section['category'], 'Beban') && $row['percentage'] > 100)
                                                            $barColor = 'bg-red-500';
                                                    @endphp
                                                    <div class="progress-bar {{ $barColor }}" style="width: {{ $pct }}%"></div>
                                                </div>
                                                <span
                                                    class="text-xs font-bold leading-none min-w-[32px]">{{ round($row['percentage']) }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-50 dark:bg-gray-900/50">
                                    <td class="font-bold py-4">TOTAL {{ strtoupper($section['category']) }}</td>
                                    <td class="text-right font-bold money py-4">Rp
                                        {{ number_format($section['budgetTotal'], 0, ',', '.') }}
                                    </td>
                                    <td class="text-right font-bold money py-4">Rp
                                        {{ number_format($section['actualTotal'], 0, ',', '.') }}
                                    </td>
                                    <td
                                        class="text-right font-bold money py-4 {{ ($section['budgetTotal'] - $section['actualTotal']) < 0 ? 'text-red-500' : 'text-green-500' }}">
                                        Rp
                                        {{ number_format(abs($section['budgetTotal'] - $section['actualTotal']), 0, ',', '.') }}
                                    </td>
                                    <td>
                                        @php $totalPct = $section['budgetTotal'] > 0 ? ($section['actualTotal'] / $section['budgetTotal']) * 100 : 0; @endphp
                                        <div class="flex items-center gap-3">
                                            <div class="progress-bg h-2.5">
                                                <div class="progress-bar bg-gray-400 dark:bg-gray-500"
                                                    style="width: {{ min($totalPct, 100) }}%"></div>
                                            </div>
                                            <span class="text-sm font-black">{{ round($totalPct) }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>