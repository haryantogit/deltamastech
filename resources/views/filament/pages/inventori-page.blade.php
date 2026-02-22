<x-filament-panels::page>
    <style>
        .inventori-section {
            margin-bottom: 32px;
        }

        .inventori-section-title {
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

        .inventori-section-title svg {
            width: 20px;
            height: 20px;
        }

        .inventori-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }

        @media (max-width: 1200px) {
            .inventori-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 900px) {
            .inventori-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .inventori-grid {
                grid-template-columns: 1fr;
            }
        }

        .inventori-card {
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

        .inventori-card:hover {
            border-color: #3b82f6;
            background: #eff6ff;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.1);
        }

        .inventori-card-icon {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .inventori-card-icon svg {
            width: 20px;
            height: 20px;
        }

        .inventori-card-title {
            font-size: 13px;
            font-weight: 500;
            color: #334155;
            line-height: 1.4;
        }

        .inventori-card:hover .inventori-card-title {
            color: #1e40af;
        }

        .dark .inventori-section-title {
            color: #f1f5f9;
            border-color: #334155;
        }

        .dark .inventori-card {
            background: #1e293b;
            border-color: #334155;
        }

        .dark .inventori-card:hover {
            background: #1e3a5f;
            border-color: #3b82f6;
        }

        .dark .inventori-card-title {
            color: #cbd5e1;
        }

        .dark .inventori-card:hover .inventori-card-title {
            color: #93c5fd;
        }

        .dark .coming-soon-badge {
            background: #451a03;
            color: #fbbf24;
            border-color: #78350f;
        }

        .coming-soon-badge {
            position: absolute;
            top: -6px;
            right: -6px;
            background: #fef3c7;
            color: #d97706;
            font-size: 9px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 6px;
            border: 1px solid #fbbf24;
            box-shadow: 0 2px 4px rgba(217, 119, 6, 0.1);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            z-index: 10;
        }
    </style>

    {{-- Gudang --}}
    <div class="inventori-section">
        <div class="inventori-section-title"><svg style="color:#3b82f6" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>Gudang</div>
        <div class="inventori-grid">
            <a href="{{ \App\Filament\Resources\Warehouses\WarehouseResource::getUrl('index') }}"
                class="inventori-card">
                <div class="inventori-card-icon" style="background:#dbeafe"><svg style="color:#2563eb" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg></div><span class="inventori-card-title">Daftar Gudang</span>
            </a>
            <a href="{{ \App\Filament\Resources\Warehouses\WarehouseResource::getUrl('create') }}"
                class="inventori-card">
                <div class="inventori-card-icon" style="background:#dcfce7"><svg style="color:#16a34a" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg></div><span class="inventori-card-title">Tambah Gudang</span>
            </a>
        </div>
    </div>

    {{-- Transfer Gudang --}}
    <div class="inventori-section">
        <div class="inventori-section-title"><svg style="color:#22c55e" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
            </svg>Transfer Gudang</div>
        <div class="inventori-grid">
            <a href="{{ \App\Filament\Resources\WarehouseTransfers\WarehouseTransferResource::getUrl('index') }}"
                class="inventori-card">
                <div class="inventori-card-icon" style="background:#dbeafe"><svg style="color:#2563eb" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg></div><span class="inventori-card-title">Daftar Transfer</span>
            </a>
            <a href="{{ \App\Filament\Resources\WarehouseTransfers\WarehouseTransferResource::getUrl('create') }}"
                class="inventori-card">
                <div class="inventori-card-icon" style="background:#dcfce7"><svg style="color:#16a34a" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg></div><span class="inventori-card-title">Buat Transfer Baru</span>
            </a>
        </div>
    </div>

    {{-- Penyesuaian Stok --}}
    <div class="inventori-section">
        <div class="inventori-section-title"><svg style="color:#f97316" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>Penyesuaian Stok</div>
        <div class="inventori-grid">
            <a href="{{ \App\Filament\Resources\StockAdjustments\StockAdjustmentResource::getUrl('index') }}"
                class="inventori-card">
                <div class="inventori-card-icon" style="background:#dbeafe"><svg style="color:#2563eb" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg></div><span class="inventori-card-title">Daftar Penyesuaian</span>
            </a>
            <a href="{{ \App\Filament\Resources\StockAdjustments\StockAdjustmentResource::getUrl('create') }}"
                class="inventori-card">
                <div class="inventori-card-icon" style="background:#dcfce7"><svg style="color:#16a34a" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg></div><span class="inventori-card-title">Buat Penyesuaian</span>
            </a>
        </div>
    </div>

    {{-- Riwayat Stok --}}
    <div class="inventori-section">
        <div class="inventori-section-title"><svg style="color:#a855f7" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>Riwayat Stok</div>
        <div class="inventori-grid">
            <a href="{{ \App\Filament\Resources\StockMovementResource::getUrl('index') }}" class="inventori-card">
                <div class="inventori-card-icon" style="background:#dbeafe"><svg style="color:#2563eb" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg></div><span class="inventori-card-title">Riwayat Pergerakan Stok</span>
            </a>
        </div>
    </div>

    {{-- Laporan Inventori --}}
    <div class="inventori-section">
        <div class="inventori-section-title"><svg style="color:#06b6d4" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>Laporan</div>
        <div class="inventori-grid">
            <a href="{{ \App\Filament\Pages\ComingSoon::getUrl(['feature' => 'Ringkasan Inventori']) }}"
                class="inventori-card" style="position: relative;">
                <div class="inventori-card-icon" style="background:#dbeafe"><svg style="color:#2563eb" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg></div><span class="inventori-card-title">Ringkasan Inventori</span>
                <span class="coming-soon-badge">Coming Soon</span>
            </a>
            <a href="{{ \App\Filament\Pages\ComingSoon::getUrl(['feature' => 'Ringkasan Aset Gudang']) }}"
                class="inventori-card" style="position: relative;">
                <div class="inventori-card-icon" style="background:#dcfce7"><svg style="color:#16a34a" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg></div><span class="inventori-card-title">Ringkasan Aset Gudang</span>
                <span class="coming-soon-badge">Coming Soon</span>
            </a>
            <a href="{{ \App\Filament\Pages\ComingSoon::getUrl(['feature' => 'Ringkasan Stok Gudang']) }}"
                class="inventori-card" style="position: relative;">
                <div class="inventori-card-icon" style="background:#fef3c7"><svg style="color:#d97706" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                    </svg></div><span class="inventori-card-title">Ringkasan Stok Gudang</span>
                <span class="coming-soon-badge">Coming Soon</span>
            </a>
            <a href="{{ \App\Filament\Pages\ComingSoon::getUrl(['feature' => 'Perubahan Stok Gudang']) }}"
                class="inventori-card" style="position: relative;">
                <div class="inventori-card-icon" style="background:#f3e8ff"><svg style="color:#9333ea" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg></div><span class="inventori-card-title">Perubahan Stok Gudang</span>
                <span class="coming-soon-badge">Coming Soon</span>
            </a>
            <a href="{{ \App\Filament\Pages\ComingSoon::getUrl(['feature' => 'Laporan Produk']) }}"
                class="inventori-card" style="position: relative;">
                <div class="inventori-card-icon" style="background:#fce7f3"><svg style="color:#db2777" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg></div><span class="inventori-card-title">Laporan Produk</span>
                <span class="coming-soon-badge">Coming Soon</span>
            </a>
            <a href="{{ \App\Filament\Pages\ComingSoon::getUrl(['feature' => 'Laporan Penyesuaian Stok']) }}"
                class="inventori-card" style="position: relative;">
                <div class="inventori-card-icon" style="background:#e0e7ff"><svg style="color:#4f46e5" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg></div><span class="inventori-card-title">Lap. Penyesuaian Stok</span>
                <span class="coming-soon-badge">Coming Soon</span>
            </a>
            <a href="{{ \App\Filament\Pages\ComingSoon::getUrl(['feature' => 'Laporan Transfer Gudang']) }}"
                class="inventori-card" style="position: relative;">
                <div class="inventori-card-icon" style="background:#cffafe"><svg style="color:#0891b2" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg></div><span class="inventori-card-title">Lap. Transfer Gudang</span>
                <span class="coming-soon-badge">Coming Soon</span>
            </a>
            <a href="{{ \App\Filament\Pages\ComingSoon::getUrl(['feature' => 'Laporan Produksi Stok']) }}"
                class="inventori-card" style="position: relative;">
                <div class="inventori-card-icon" style="background:#fee2e2"><svg style="color:#dc2626" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg></div><span class="inventori-card-title">Lap. Produksi Stok</span>
                <span class="coming-soon-badge">Coming Soon</span>
            </a>
        </div>
    </div>
</x-filament-panels::page>