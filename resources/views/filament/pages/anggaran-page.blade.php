<x-filament-panels::page>
    <style>
        .anggaran-section {
            margin-bottom: 32px;
        }

        .anggaran-section-title {
            font-size: 15px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .anggaran-section-title svg {
            width: 20px;
            height: 20px;
        }

        .anggaran-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }

        @media (max-width: 1200px) {
            .anggaran-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 900px) {
            .anggaran-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .anggaran-grid {
                grid-template-columns: 1fr;
            }
        }

        .anggaran-card {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            text-decoration: none;
            transition: all 0.15s;
            position: relative;
        }

        .anggaran-card:hover {
            border-color: #3b82f6;
            background: #eff6ff;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.1);
        }

        .anggaran-card-icon {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .anggaran-card-icon svg {
            width: 20px;
            height: 20px;
        }

        .anggaran-card-title {
            font-size: 13px;
            font-weight: 500;
            color: #334155;
            line-height: 1.4;
        }

        .anggaran-card:hover .anggaran-card-title {
            color: #1e40af;
        }

        .dark .anggaran-section-title {
            color: #f1f5f9;
            border-color: #334155;
        }

        .dark .anggaran-card {
            background: #1e293b;
            border-color: #334155;
        }

        .dark .anggaran-card:hover {
            background: #1e3a5f;
            border-color: #3b82f6;
        }

        .dark .anggaran-card-title {
            color: #cbd5e1;
        }

        .dark .anggaran-card:hover .anggaran-card-title {
            color: #93c5fd;
        }

        .coming-soon-badge {
            position: absolute;
            top: 4px;
            right: 4px;
            background: rgba(245, 158, 11, 0.1);
            color: #d97706;
            font-size: 8px;
            font-weight: 800;
            padding: 2px 6px;
            border-radius: 4px;
            border: 1px solid rgba(245, 158, 11, 0.2);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            z-index: 10;
            backdrop-filter: blur(4px);
        }

        .dark .coming-soon-badge {
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
            border-color: rgba(245, 158, 11, 0.3);
        }
    </style>

    {{-- Daftar Menu Anggaran --}}
    @if(auth()->user()->can('anggaran.management.view') || auth()->user()->can('anggaran.report.view'))
        <div class="anggaran-section">
            <div class="anggaran-section-title">
                <svg style="color:#db2777" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                Operasional Anggaran
            </div>
            <div class="anggaran-grid">
                @can('anggaran.management.view')
                    <a href="{{ \App\Filament\Resources\BudgetResource::getUrl('index') }}" class="anggaran-card">
                        <div class="anggaran-card-icon" style="background:#dcfce7">
                            <svg style="color:#16a34a" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <span class="anggaran-card-title">Manajemen Anggaran</span>
                    </a>
                @endcan
                @can('anggaran.report.view')
                    <a href="{{ \App\Filament\Pages\Laporan\AnggaranLabaRugi::getUrl() }}" class="anggaran-card">
                        <div class="anggaran-card-icon" style="background:#fef3c7">
                            <svg style="color:#d97706" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <span class="anggaran-card-title">Anggaran Laba Rugi</span>
                    </a>
                @endcan
            </div>
        </div>
    @endif

    {{-- Laporan --}}
    @can('anggaran.report.view')
        <div class="anggaran-section">
            <div class="anggaran-section-title">
                <svg style="color:#22c55e" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Laporan
            </div>
            <div class="anggaran-grid">
                <a href="{{ \App\Filament\Pages\Laporan\ManajemenAnggaran::getUrl() }}" class="anggaran-card">
                    <div class="anggaran-card-icon" style="background:#dcfce7">
                        <svg style="color:#16a34a" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <span class="anggaran-card-title">Laporan Manajemen Anggaran</span>
                </a>
            </div>
        </div>
    @endcan
</x-filament-panels::page>