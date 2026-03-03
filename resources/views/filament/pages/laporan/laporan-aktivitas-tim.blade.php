<x-filament-panels::page>
    <div class="report-main-container">
        <style>
            .delivery-report-container {
                background: white;
                border-radius: 12px;
                border: 1px solid #e2e8f0;
                overflow: hidden;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
                padding: 24px;
                margin-bottom: 1.5rem;
            }

            .dark .delivery-report-container {
                background: #111827;
                border-color: #374151;
            }

            .stat-card {
                background: white;
                border-radius: 8px;
                padding: 1.25rem;
                border: 1px solid #e2e8f0;
                display: flex;
                flex-direction: column;
                align-items: center;
                text-align: center;
                transition: all 0.2s ease;
                min-height: 140px;
                justify-content: center;
            }

            .stat-card:hover {
                border-color: #cbd5e1;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
                transform: translateY(-1px);
            }

            .dark .stat-card {
                background: #1e293b;
                border-color: #334155;
            }

            .stat-icon {
                width: 42px;
                height: 42px;
                background: #f8fafc;
                border-radius: 8px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-bottom: 0.75rem;
                color: #64748b;
                border: 1px solid #f1f5f9;
            }

            .dark .stat-icon {
                background: #0f172a;
                border-color: #1e293b;
            }

            .stat-value {
                font-size: 1.75rem;
                font-weight: 800;
                color: #1e293b;
                line-height: 1.2;
                margin-bottom: 0.125rem;
            }

            .dark .stat-value {
                color: #f1f5f9;
            }

            .stat-label {
                font-size: 0.75rem;
                font-weight: 700;
                color: #94a3b8;
                text-transform: uppercase;
                letter-spacing: 0.025em;
            }

            .section-title {
                font-size: 0.9375rem;
                font-weight: 800;
                color: #1e293b;
                margin-bottom: 1.5rem;
                display: flex;
                align-items: center;
                gap: 0.625rem;
            }

            .dark .section-title {
                color: #f1f5f9;
            }

            .change-item {
                padding: 1rem;
                background: #f8fafc;
                border-radius: 10px;
                border-left: 4px solid #3b82f6;
            }

            .dark .change-item {
                background: rgba(255, 255, 255, 0.02);
                border-left-color: #2563eb;
            }

            .user-row {
                display: flex;
                align-items: center;
                padding: 1rem 0;
                border-bottom: 1px solid #f1f5f9;
            }

            .dark .user-row {
                border-color: #374151;
            }

            .user-row:last-child {
                border-bottom: none;
            }

            .user-avatar {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                background: #e2e8f0;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 800;
                color: #2563eb;
                flex-shrink: 0;
                font-size: 0.875rem;
            }

            .dark .user-avatar {
                background: #1e293b;
                color: #60a5fa;
            }

            .progress-track {
                height: 8px;
                background: #f1f5f9;
                border-radius: 999px;
                overflow: hidden;
                flex-grow: 1;
                margin: 0 1.5rem;
            }

            .dark .progress-track {
                background: #374151;
            }

            .progress-fill {
                height: 100%;
                background: linear-gradient(90deg, #3b82f6, #22c55e);
                border-radius: 999px;
                transition: width 0.5s ease-out;
            }

            @media print {
                .fi-header-actions {
                    display: none !important;
                }

                .delivery-report-container {
                    border: 1px solid #000 !important;
                    box-shadow: none !important;
                    break-inside: avoid;
                }
            }
        </style>

        @php
            $data = $this->getViewData();
        @endphp

        <div class="report-content">
            {{-- Top Summary Stats --}}
            <div style="display: grid; grid-template-columns: repeat(1, minmax(0, 1fr)); gap: 1rem; margin-bottom: 1.5rem;"
                class="md:grid-cols-4 lg:grid-cols-4">
                <style>
                    @media (min-width: 768px) {
                        .stats-grid-container {
                            grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
                        }
                    }
                </style>
                <div class="stats-grid-container"
                    style="display: grid; grid-template-columns: repeat(1, minmax(0, 1fr)); gap: 1rem; width: 100%;">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                            </svg>
                        </div>
                        <div class="stat-value">{{ $stats['loginHistory'] }}</div>
                        <div class="stat-label">Hari Login Lalu</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="color: #059669;">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div class="stat-value">{{ $stats['totalLogins'] }}</div>
                        <div class="stat-label">Total Login Sesi</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="color: #2563eb;">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                            </svg>
                        </div>
                        <div class="stat-value">{{ $stats['totalChanges'] }}</div>
                        <div class="stat-label">Aktivitas Perubahan</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="color: #f59e0b;">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <div class="stat-value">{{ $stats['activeTeam'] }}</div>
                        <div class="stat-label">Anggota Tim Login</div>
                    </div>
                </div>
            </div>

            {{-- Section 1: Perubahan Data (Full Width with side-by-side cards) --}}
            <div class="delivery-report-container">
                <div class="section-title">
                    <div style="width: 4px; height: 16px; background: #3b82f6; border-radius: 999px;"></div>
                    Perubahan Data
                </div>
                <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.5rem;">
                    <div class="change-item">
                        <div
                            style="font-size: 0.8125rem; font-weight: 800; color: #2563eb; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.025em;">
                            Tagihan Penjualan
                        </div>
                        <div class="flex gap-4">
                            <div>
                                <div style="font-size: 1.125rem; font-weight: 800; color: #1e293b;"
                                    class="dark:text-white">{{ $changes['sales']['new'] }}</div>
                                <div style="font-size: 0.75rem; color: #94a3b8; font-weight: 600;">Data Baru
                                </div>
                            </div>
                            <div style="width: 1px; background: #e2e8f0;" class="dark:bg-gray-700"></div>
                            <div>
                                <div style="font-size: 1.125rem; font-weight: 800; color: #1e293b;"
                                    class="dark:text-white">{{ $changes['sales']['modified'] }}</div>
                                <div style="font-size: 0.75rem; color: #94a3b8; font-weight: 600;">Perubahan
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="change-item" style="border-left-color: #10b981;">
                        <div
                            style="font-size: 0.8125rem; font-weight: 800; color: #059669; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.025em;">
                            Tagihan Pembelian
                        </div>
                        <div class="flex gap-4">
                            <div>
                                <div style="font-size: 1.125rem; font-weight: 800; color: #1e293b;"
                                    class="dark:text-white">{{ $changes['purchase']['new'] }}</div>
                                <div style="font-size: 0.75rem; color: #94a3b8; font-weight: 600;">Data Baru
                                </div>
                            </div>
                            <div style="width: 1px; background: #e2e8f0;" class="dark:bg-gray-700"></div>
                            <div>
                                <div style="font-size: 1.125rem; font-weight: 800; color: #1e293b;"
                                    class="dark:text-white">{{ $changes['purchase']['modified'] }}</div>
                                <div style="font-size: 0.75rem; color: #94a3b8; font-weight: 600;">Perubahan
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section 2: Team Activity and Chart (Side-by-side) --}}
            <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.5rem;">
                {{-- Team Activity Section --}}
                <div class="delivery-report-container">
                    <div class="section-title">
                        <div style="width: 4px; height: 16px; background: #3b82f6; border-radius: 999px;"></div>
                        Aktivitas Tim
                    </div>
                    <div class="divide-y divide-gray-50 dark:divide-gray-800">
                        @foreach ($users as $user)
                            <div class="user-row">
                                <div class="user-avatar">
                                    @if ($user['avatar'])
                                        <img src="{{ $user['avatar'] }}" class="object-cover w-full h-full rounded-full">
                                    @else
                                        {{ $user['initial'] }}
                                    @endif
                                </div>
                                <div style="flex-shrink: 0; width: 120px; margin-left: 1rem;">
                                    <div style="font-size: 0.875rem; font-weight: 700; color: #1e293b;"
                                        class="dark:text-white">{{ $user['name'] }}</div>
                                    <div style="font-size: 0.75rem; color: #94a3b8; font-weight: 600;">Login
                                        {{ $user['logins'] }}x
                                    </div>
                                </div>
                                <div class="progress-track">
                                    <div class="progress-fill" style="width: {{ $user['change_pct'] }}%"></div>
                                </div>
                                <div style="font-size: 0.8125rem; font-weight: 800; color: #1e293b;"
                                    class="dark:text-white whitespace-nowrap">
                                    {{ $user['change_pct'] }}% <span
                                        style="font-size: 0.6875rem; color: #94a3b8; font-weight: 600; text-transform: uppercase;"></span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Action Chart --}}
                <div class="delivery-report-container flex flex-col items-center">
                    <div
                        style="width: 100%; text-align: center; text-[10px] font-extrabold uppercase tracking-widest text-[#94a3b8] mb-8 mt-2">
                        TINDAKAN DATA TIM
                    </div>

                    <div class="relative w-64 h-64">
                        <canvas id="actionChart"></canvas>
                    </div>

                    <div class="mt-10 space-y-4 w-full">
                        <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800/50 rounded-lg">
                            <div class="flex items-center gap-3">
                                <div class="w-2.5 h-2.5 rounded-full bg-[#f43f5e]"></div>
                                <span style="font-size: 0.7rem; font-weight: 700; color: #64748b;">Buat Data</span>
                            </div>
                            <span style="font-size: 0.75rem; font-weight: 800; color: #1e293b;"
                                class="dark:text-white">65%</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800/50 rounded-lg">
                            <div class="flex items-center gap-3">
                                <div class="w-2.5 h-2.5 rounded-full bg-[#facc15]"></div>
                                <span style="font-size: 0.7rem; font-weight: 700; color: #64748b;">Ubah Data</span>
                            </div>
                            <span style="font-size: 0.75rem; font-weight: 800; color: #1e293b;"
                                class="dark:text-white">25%</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800/50 rounded-lg">
                            <div class="flex items-center gap-3">
                                <div class="w-2.5 h-2.5 rounded-full bg-[#2dd4bf]"></div>
                                <span style="font-size: 0.7rem; font-weight: 700; color: #64748b;">Hapus
                                    Data</span>
                            </div>
                            <span style="font-size: 0.75rem; font-weight: 800; color: #1e293b;"
                                class="dark:text-white">10%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            initChart();
        });

        document.addEventListener('livewire:navigated', () => {
            initChart();
        });

        function initChart() {
            const ctx = document.getElementById('actionChart');
            if (!ctx) return;

            if (window.teamActionChart) {
                window.teamActionChart.destroy();
            }

            const data = {!! $chartData !!};

            window.teamActionChart = new Chart(ctx, {
                type: 'doughnut',
                data: data,
                options: {
                    cutout: '75%',
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            enabled: false
                        }
                    },
                    maintainAspectRatio: false
                }
            });
        }
    </script>
@endpush