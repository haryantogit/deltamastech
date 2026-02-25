<x-filament-panels::page>
    <div class="flex flex-col gap-y-6">
        <style>
            .activity-card {
                background: white;
                border: 1px solid #e5e7eb;
                border-radius: 12px;
                padding: 24px;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            }

            .dark .activity-card {
                background: #1e293b;
                border-color: #334155;
            }

            .stat-value {
                font-size: 32px;
                font-weight: 800;
                color: #2563eb;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .stat-label {
                font-size: 13px;
                color: #64748b;
                font-weight: 500;
            }

            .section-header {
                font-size: 16px;
                font-weight: 700;
                color: #1e293b;
                margin-bottom: 20px;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .dark .section-header {
                color: #f1f5f9;
            }

            .user-row {
                display: flex;
                align-items: center;
                padding: 12px 0;
                border-bottom: 1px solid #f1f5f9;
            }

            .dark .user-row {
                border-color: #334155;
            }

            .user-row:last-child {
                border-bottom: none;
            }

            .progress-container {
                flex-grow: 1;
                margin: 0 20px;
            }

            .progress-track {
                height: 8px;
                background: #f1f5f9;
                border-radius: 10px;
                overflow: hidden;
            }

            .dark .progress-track {
                background: #334155;
            }

            .progress-fill {
                height: 100%;
                background: #22c55e;
                border-radius: 10px;
            }

            .user-avatar {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                background: #e2e8f0;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 700;
                color: #64748b;
                flex-shrink: 0;
            }
        </style>

        {{-- Filters (Date Range Mockup) --}}
        <div class="flex justify-end">
            <div
                class="flex items-center gap-2 px-3 py-1.5 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <input type="date" wire:model.live="startDate"
                    class="p-0 text-xs bg-transparent border-none focus:ring-0">
                <span class="text-gray-400">→</span>
                <input type="date" wire:model.live="endDate"
                    class="p-0 text-xs bg-transparent border-none focus:ring-0">
                <svg width="16" height="16" style="width: 16px; height: 16px;" class="text-gray-400" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
        </div>

        {{-- Top Summary Stats --}}
        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
            <div class="flex flex-col items-center justify-center p-6 text-center activity-card">
                <div class="stat-value">
                    <svg width="32" height="32" style="width: 32px; height: 32px; min-width: 32px;" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                    </svg>
                    {{ $stats['loginHistory'] }}
                </div>
                <div class="stat-label">Hari Login Bulan Lalu</div>
            </div>
            <div class="flex flex-col items-center justify-center p-6 text-center activity-card">
                <div class="stat-value">
                    <svg width="32" height="32" style="width: 32px; height: 32px; min-width: 32px;" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                    </svg>
                    {{ $stats['totalChanges'] }}
                </div>
                <div class="stat-label">Aktivitas Perubahan Data</div>
            </div>
            <div class="flex flex-col items-center justify-center p-6 text-center activity-card">
                <div class="stat-value">
                    <svg width="32" height="32" style="width: 32px; height: 32px; min-width: 32px;" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    {{ $stats['activeTeam'] }}
                </div>
                <div class="stat-label">Jumlah Anggota Tim Login</div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                {{-- Data Changes Section --}}
                <div class="activity-card">
                    <div class="section-header">Perubahan Data</div>
                    <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
                        <div class="space-y-4">
                            <h4 class="text-sm font-bold text-blue-600">Tagihan Penjualan</h4>
                            <div class="flex flex-col">
                                <span class="text-sm font-medium">{{ $changes['sales']['new'] }} Baru</span>
                                <span class="text-sm font-medium">{{ $changes['sales']['modified'] }} Perubahan</span>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <h4 class="text-sm font-bold text-blue-600">Tagihan Pembelian</h4>
                            <div class="flex flex-col">
                                <span class="text-sm font-medium">{{ $changes['purchase']['new'] }} Baru</span>
                                <span class="text-sm font-medium">{{ $changes['purchase']['modified'] }}
                                    Perubahan</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Team Activity Section --}}
                <div class="activity-card">
                    <div class="section-header">Aktivitas Tim</div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($users as $user)
                            <div class="user-row">
                                <div class="user-avatar">
                                    @if ($user['avatar'])
                                        <img src="{{ $user['avatar'] }}" class="object-cover w-full h-full rounded-full">
                                    @else
                                        {{ $user['initial'] }}
                                    @endif
                                </div>
                                <div class="flex flex-col flex-grow ml-4">
                                    <span class="text-sm font-bold text-blue-600">{{ $user['name'] }}</span>
                                </div>
                                <div class="progress-container">
                                    <div class="progress-track">
                                        <div class="progress-fill" style="width: {{ $user['change_pct'] }}%"></div>
                                    </div>
                                </div>
                                <div class="text-xs font-medium text-gray-500 whitespace-nowrap">
                                    Login {{ $user['logins'] }} kali <span class="mx-1">•</span> Perubahan Data
                                    {{ $user['change_pct'] }}%
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Right Sidebar: Action Chart --}}
            <div class="lg:col-span-1">
                <div class="activity-card h-full flex flex-col items-center">
                    <div
                        class="w-full text-center text-[11px] font-black uppercase tracking-widest text-gray-500 mb-8 mt-2">
                        AKSI YANG DILAKUKAN TIM</div>

                    <div class="relative w-64 h-64">
                        <canvas id="actionChart"></canvas>
                    </div>

                    <div class="mt-8 space-y-3 w-full max-w-[180px]">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-2.5 rounded bg-[#f43f5e]"></div>
                            <span class="text-xs font-bold text-gray-600 dark:text-gray-400">Buat Data</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-2.5 rounded bg-[#facc15]"></div>
                            <span class="text-xs font-bold text-gray-600 dark:text-gray-400">Ubah Data</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-2.5 rounded bg-[#2dd4bf]"></div>
                            <span class="text-xs font-bold text-gray-600 dark:text-gray-400">Hapus Data</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('livewire:navigated', () => {
                initChart();
            });

            document.addEventListener('DOMContentLoaded', () => {
                initChart();
            });

            function initChart() {
                const ctx = document.getElementById('actionChart');
                if (!ctx) return;

                // Destroy existing chart if it exists
                if (window.teamActionChart) {
                    window.teamActionChart.destroy();
                }

                const data = {!! $chartData !!};

                window.teamActionChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: data,
                    options: {
                        cutout: '70%',
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        maintainAspectRatio: false
                    }
                });
            }
        </script>
    @endpush
</x-filament-panels::page>