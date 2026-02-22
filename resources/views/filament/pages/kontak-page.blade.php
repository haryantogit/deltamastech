<x-filament-panels::page>
    <style>
        .kontak-section {
            margin-bottom: 32px;
        }

        .kontak-section-title {
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

        .kontak-section-title svg {
            width: 20px;
            height: 20px;
        }

        .kontak-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }

        @media (max-width: 1200px) {
            .kontak-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 900px) {
            .kontak-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .kontak-grid {
                grid-template-columns: 1fr;
            }
        }

        .kontak-card {
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

        .kontak-card:hover {
            border-color: #3b82f6;
            background: #eff6ff;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.1);
        }

        .kontak-card-icon {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .kontak-card-icon svg {
            width: 20px;
            height: 20px;
        }

        .kontak-card-title {
            font-size: 13px;
            font-weight: 500;
            color: #334155;
            line-height: 1.4;
        }

        .kontak-card:hover .kontak-card-title {
            color: #1e40af;
        }

        .dark .kontak-section-title {
            color: #f1f5f9;
            border-color: #334155;
        }

        .dark .kontak-card {
            background: #1e293b;
            border-color: #334155;
        }

        .dark .kontak-card:hover {
            background: #1e3a5f;
            border-color: #3b82f6;
        }

        .dark .kontak-card-title {
            color: #cbd5e1;
        }

        .dark .kontak-card:hover .kontak-card-title {
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

    {{-- Daftar Mitra --}}
    <div class="kontak-section">
        <div class="kontak-section-title"><svg style="color:#3b82f6" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>Daftar Kontak</div>
        <div class="kontak-grid">
            <a href="{{ \App\Filament\Resources\ContactResource::getUrl('index') }}" class="kontak-card">
                <div class="kontak-card-icon" style="background:#dbeafe"><svg style="color:#2563eb" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg></div><span class="kontak-card-title">Semua Kontak</span>
            </a>
            <a href="{{ \App\Filament\Resources\ContactResource::getUrl('index') }}?tab=Pelanggan" class="kontak-card">
                <div class="kontak-card-icon" style="background:#dcfce7"><svg style="color:#16a34a" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg></div><span class="kontak-card-title">Pelanggan</span>
            </a>
            <a href="{{ \App\Filament\Resources\ContactResource::getUrl('index') }}?tab=Vendor" class="kontak-card">
                <div class="kontak-card-icon" style="background:#fef3c7"><svg style="color:#d97706" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg></div><span class="kontak-card-title">Vendor / Supplier</span>
            </a>
            <a href="{{ \App\Filament\Resources\ContactResource::getUrl('index') }}?tab=Karyawan" class="kontak-card">
                <div class="kontak-card-icon" style="background:#f3e8ff"><svg style="color:#9333ea" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg></div><span class="kontak-card-title">Karyawan</span>
            </a>
            <a href="{{ \App\Filament\Resources\ContactResource::getUrl('create') }}" class="kontak-card">
                <div class="kontak-card-icon" style="background:#e0e7ff"><svg style="color:#4f46e5" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg></div><span class="kontak-card-title">Tambah Kontak Baru</span>
            </a>
        </div>
    </div>

    {{-- Hutang --}}
    <div class="kontak-section">
        <div class="kontak-section-title"><svg style="color:#dc2626" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>Hutang</div>
        <div class="kontak-grid">
            <a href="{{ \App\Filament\Resources\HutangResource::getUrl('index') }}" class="kontak-card">
                <div class="kontak-card-icon" style="background:#fee2e2"><svg style="color:#dc2626" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg></div><span class="kontak-card-title">Daftar Hutang</span>
            </a>
            <a href="{{ \App\Filament\Pages\ComingSoon::getUrl(['feature' => 'Umur Hutang']) }}" class="kontak-card"
                style="position: relative;">
                <div class="kontak-card-icon" style="background:#fef3c7"><svg style="color:#d97706" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg></div><span class="kontak-card-title">Umur Hutang</span>
                <span class="coming-soon-badge">Coming Soon</span>
            </a>
            <a href="{{ \App\Filament\Pages\ComingSoon::getUrl(['feature' => 'Kirim Pembayaran']) }}"
                class="kontak-card" style="position: relative;">
                <div class="kontak-card-icon" style="background:#dcfce7"><svg style="color:#16a34a" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg></div><span class="kontak-card-title">Kirim Pembayaran</span>
                <span class="coming-soon-badge">Coming Soon</span>
            </a>
        </div>
    </div>

    {{-- Laporan Mitra --}}
    <div class="kontak-section">
        <div class="kontak-section-title"><svg style="color:#22c55e" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>Piutang</div>
        <div class="kontak-grid">
            <a href="{{ \App\Filament\Resources\PiutangResource::getUrl('index') }}" class="kontak-card">
                <div class="kontak-card-icon" style="background:#dcfce7"><svg style="color:#16a34a" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg></div><span class="kontak-card-title">Daftar Piutang</span>
            </a>
            <a href="{{ \App\Filament\Pages\ComingSoon::getUrl(['feature' => 'Umur Piutang']) }}" class="kontak-card"
                style="position: relative;">
                <div class="kontak-card-icon" style="background:#fef3c7"><svg style="color:#d97706" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg></div><span class="kontak-card-title">Umur Piutang</span>
                <span class="coming-soon-badge">Coming Soon</span>
            </a>
            <a href="{{ \App\Filament\Pages\ComingSoon::getUrl(['feature' => 'Terima Pembayaran']) }}"
                class="kontak-card" style="position: relative;">
                <div class="kontak-card-icon" style="background:#dbeafe"><svg style="color:#2563eb" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg></div><span class="kontak-card-title">Terima Pembayaran</span>
                <span class="coming-soon-badge">Coming Soon</span>
            </a>
        </div>
    </div>
</x-filament-panels::page>