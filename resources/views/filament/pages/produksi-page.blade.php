<x-filament-panels::page>
    <style>
        .produksi-section {
            margin-bottom: 32px;
        }

        .produksi-section-title {
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

        .produksi-section-title svg {
            width: 20px;
            height: 20px;
        }

        .produksi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }

        @media (max-width: 1200px) {
            .produksi-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 900px) {
            .produksi-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .produksi-grid {
                grid-template-columns: 1fr;
            }
        }

        .produksi-card {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            text-decoration: none;
            transition: all 0.15s;
        }

        .produksi-card:hover {
            border-color: #3b82f6;
            background: #eff6ff;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.1);
        }

        .produksi-card-icon {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .produksi-card-icon svg {
            width: 20px;
            height: 20px;
        }

        .produksi-card-title {
            font-size: 13px;
            font-weight: 500;
            color: #334155;
            line-height: 1.4;
        }

        .produksi-card:hover .produksi-card-title {
            color: #1e40af;
        }

        .dark .produksi-section-title {
            color: #f1f5f9;
            border-color: #334155;
        }

        .dark .produksi-card {
            background: #1e293b;
            border-color: #334155;
        }

        .dark .produksi-card:hover {
            background: #1e3a5f;
            border-color: #3b82f6;
        }

        .dark .produksi-card-title {
            color: #cbd5e1;
        }

        .dark .produksi-card:hover .produksi-card-title {
            color: #93c5fd;
        }
    </style>

    {{-- Daftar Menu Produksi --}}
    @if(auth()->user()->can('produksi.order.view'))
        <div class="produksi-section">
            <div class="produksi-section-title">
                <svg style="color:#3b82f6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                Operasional Produksi
            </div>
            <div class="produksi-grid">
                <a href="{{ \App\Filament\Resources\ProductionOrderResource::getUrl('index') }}" class="produksi-card">
            </div>
            <span class="produksi-card-title">Konversi Produk</span>
            </a>
            @can('produksi.order.add')
                <a href="{{ \App\Filament\Resources\ProductionOrderResource::getUrl('create') }}" class="produksi-card">
                    <div class="produksi-card-icon" style="background:#e0e7ff">
                        <svg style="color:#4f46e5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </div>
                    <span class="produksi-card-title">Buat Konversi Baru</span>
                </a>
            @endcan
        </div>
        </div>
    @endif

    {{-- Laporan Produksi --}}
    @can('produksi.result.view')
        <div class="produksi-section">
            <div class="produksi-section-title">
                <svg style="color:#22c55e" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Laporan
            </div>
            <div class="produksi-grid">
                <a href="{{ \App\Filament\Pages\ProductionReport::getUrl() }}" class="produksi-card">
                    <div class="produksi-card-icon" style="background:#dcfce7">
                        <svg style="color:#16a34a" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <span class="produksi-card-title">Laporan Produksi</span>
                </a>
            </div>
        </div>
    @endcan
</x-filament-panels::page>