<x-filament-panels::page>
    <style>
        .report-section {
            margin-bottom: 32px;
        }

        .report-section-title {
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

        .report-section-title svg {
            width: 20px;
            height: 20px;
        }

        .report-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }

        @media (max-width: 1200px) {
            .report-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 900px) {
            .report-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .report-grid {
                grid-template-columns: 1fr;
            }
        }

        .report-card {
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

        .report-card:hover {
            border-color: #3b82f6;
            background: #eff6ff;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.1);
        }

        .report-card-icon {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .report-card-icon svg {
            width: 20px;
            height: 20px;
        }

        .report-card-title {
            font-size: 13px;
            font-weight: 500;
            color: #334155;
            line-height: 1.4;
        }

        .report-card:hover .report-card-title {
            color: #1e40af;
        }

        .dark .report-section-title {
            color: #f1f5f9;
            border-color: #334155;
        }

        .dark .report-card {
            background: #1e293b;
            border-color: #334155;
        }

        .dark .report-card:hover {
            background: #1e3a5f;
            border-color: #3b82f6;
        }

        .dark .report-card-title {
            color: #cbd5e1;
        }

        .dark .report-card:hover .report-card-title {
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

    {{-- Finansial --}}
    <div class="report-section">
        <div class="report-section-title"><svg style="color:#2563eb" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>Finansial</div>
        <div class="report-grid">
            <a href="{{ \App\Filament\Pages\Laporan\Neraca::getUrl() }}" class="report-card">
                <div class="report-card-icon" style="background:#dbeafe"><svg style="color:#2563eb" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg></div><span class="report-card-title">Neraca</span>
            </a>
            <a href="{{ \App\Filament\Pages\Laporan\ArusKas::getUrl() }}" class="report-card">
                <div class="report-card-icon" style="background:#dcfce7"><svg style="color:#16a34a" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                    </svg></div><span class="report-card-title">Arus Kas</span>
            </a>
            <a href="{{ \App\Filament\Pages\Laporan\LabaRugi::getUrl() }}" class="report-card">
                <div class="report-card-icon" style="background:#fef3c7"><svg style="color:#d97706" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg></div><span class="report-card-title">Laba Rugi</span>
            </a>
            <a href="{{ \App\Filament\Pages\Laporan\PerubahanModal::getUrl() }}" class="report-card">
                <div class="report-card-icon" style="background:#f3e8ff"><svg style="color:#9333ea" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg></div><span class="report-card-title">Perubahan Modal</span>
            </a>
            <a href="{{ \App\Filament\Pages\Laporan\RingkasanEksekutif::getUrl() }}" class="report-card">
                <div class="report-card-icon" style="background:#fce7f3"><svg style="color:#db2777" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg></div><span class="report-card-title">Ringkasan Eksekutif</span>
            </a>
            <a href="{{ \App\Filament\Pages\Laporan\HutangPiutangPerKontak::getUrl() }}" class="report-card">
                <div class="report-card-icon" style="background:#e0e7ff"><svg style="color:#4f46e5" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg></div><span class="report-card-title">Hutang Piutang per Kontak</span>
            </a>
        </div>
    </div>

    {{-- Akuntansi --}}
    <div class="report-section">
        <div class="report-section-title"><svg style="color:#16a34a" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>Akuntansi</div>
        <div class="report-grid">
            <a href="{{ \App\Filament\Pages\Laporan\RingkasanBank::getUrl() }}" class="report-card">
                <div class="report-card-icon" style="background:#dbeafe"><svg style="color:#2563eb" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg></div><span class="report-card-title">Ringkasan Bank</span>
            </a>
            <a href="{{ \App\Filament\Pages\Laporan\BukuBesar::getUrl() }}" class="report-card">
                <div class="report-card-icon" style="background:#dcfce7"><svg style="color:#16a34a" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg></div><span class="report-card-title">Buku Besar</span>
            </a>
            <a href="{{ \App\Filament\Pages\Laporan\JurnalUmum::getUrl() }}" class="report-card">
                <div class="report-card-icon" style="background:#fef3c7"><svg style="color:#d97706" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg></div><span class="report-card-title">Jurnal Umum</span>
            </a>
            <a href="{{ \App\Filament\Pages\Laporan\TrialBalance::getUrl() }}" class="report-card">
                <div class="report-card-icon" style="background:#f3e8ff"><svg style="color:#9333ea" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg></div><span class="report-card-title">Trial Balance</span>
            </a>
        </div>
    </div>

    {{-- Penjualan --}}
    <div class="report-section">
        <div class="report-section-title"><svg style="color:#d97706" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>Penjualan</div>
        <div class="report-grid">
            <a href="{{ \App\Filament\Pages\Laporan\PengirimanPenjualan::getUrl() }}" class="report-card">
                <div class="report-card-icon" style="background:#ddd6fe"><svg style="color:#7c3aed" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg></div><span class="report-card-title">Pengiriman Penjualan</span>
            </a>
            <a href="{{ \App\Filament\Pages\Laporan\OngkosKirimEkspedisi::getUrl() }}" class="report-card">
                <div class="report-card-icon" style="background:#fef08a"><svg style="color:#eab308" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                    </svg></div><span class="report-card-title">Ongkos Kirim per Ekspedisi</span>
            </a>
            <a href="{{ \App\Filament\Pages\Laporan\DaftarPenjualan::getUrl() }}" class="report-card">
                <div class="report-card-icon" style="background:#dbeafe"><svg style="color:#2563eb" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg></div><span class="report-card-title">Daftar Penjualan</span>
            </a>

            <a href="{{ \App\Filament\Pages\Laporan\ProfitabilitasProduk::getUrl() }}" class="report-card">
                <div class="report-card-icon" style="background:#fce7f3"><svg style="color:#db2777" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg></div><span class="report-card-title">Profitabilitas Produk</span>
            </a>
            <a href="{{ \App\Filament\Pages\Laporan\ProfitabilitasTagihan::getUrl() }}" class="report-card">
                <div class="report-card-icon" style="background:#dbeafe"><svg style="color:#2563eb" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                    </svg></div><span class="report-card-title">Profitabilitas Tagihan</span>
            </a>
            <a href="{{ \App\Filament\Pages\Laporan\PendapatanPelanggan::getUrl() }}" class="report-card">
                <div class="report-card-icon" style="background:#e0e7ff"><svg style="color:#4f46e5" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg></div><span class="report-card-title">Pendapatan per Pelanggan</span>
            </a>
            <a href="{{ \App\Filament\Pages\Laporan\PenjualanProduk::getUrl() }}" class="report-card">
                <div class="report-card-icon" style="background:#cffafe"><svg style="color:#0891b2" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg></div><span class="report-card-title">Penjualan per Produk</span>
            </a>
            <a href="{{ \App\Filament\Pages\Laporan\PemesananProduk::getUrl() }}" class="report-card">
                <div class="report-card-icon" style="background:#cffafe"><svg style="color:#0891b2" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg></div><span class="report-card-title">Pemesanan per Produk</span>
            </a>
            <a href="{{ \App\Filament\Pages\Laporan\PenjualanPerKategori::getUrl() }}" class="report-card">
                <div class="report-card-icon" style="background:#fee2e2"><svg style="color:#dc2626" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                    </svg></div><span class="report-card-title">Penjualan per Kategori Produk</span>
            </a>
            <a href="{{ \App\Filament\Pages\Laporan\PelunasanPembayaranTagihan::getUrl() }}" class="report-card">
                <div class="report-card-icon" style="background:#fef9c3"><svg style="color:#ca8a04" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg></div><span class="report-card-title">Pelunasan Pembayaran Tagihan</span>
            </a>
            <a href="{{ \App\Filament\Pages\Laporan\PenjualanProdukPerPelanggan::getUrl() }}" class="report-card">
                <div class="report-card-icon" style="background:#dbeafe"><svg style="color:#2563eb" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg></div><span class="report-card-title">Penjualan Produk per Pelanggan</span>
            </a>
            <a href="{{ \App\Filament\Pages\Laporan\PenjualanPerPeriode::getUrl() }}" class="report-card">
                <div class="report-card-icon" style="background:#dcfce7"><svg style="color:#16a34a" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg></div><span class="report-card-title">Penjualan per Periode</span>
            </a>
            <a href="{{ \App\Filament\Pages\Laporan\PenjualanPerRegion::getUrl() }}" class="report-card">
                <div class="report-card-icon" style="background:#f3e8ff"><svg style="color:#a855f7" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg></div><span class="report-card-title">Penjualan per Region</span>
            </a>
        </div>
    </div>

    {{-- Pembelian --}}
    <div class="report-section">
        <div class="report-section-title"><svg style="color:#9333ea" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
            </svg>Pembelian</div>
        <div class="report-grid">
            <a href="{{ \App\Filament\Pages\Laporan\DetailPembelian::getUrl() }}" class="report-card">
                <div class="report-card-icon" style="background:#dbeafe"><svg style="color:#2563eb" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg></div><span class="report-card-title">Detail Pembelian</span>
            </a>
            <a href="{{ \App\Filament\Pages\Laporan\PembelianPerProduk::getUrl() }}" class="report-card">
                <div class="report-card-icon" style="background:#dcfce7"><svg style="color:#16a34a" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg></div><span class="report-card-title">Pembelian per Produk</span>
            </a>
            <a href="{{ \App\Filament\Pages\Laporan\PemesananPembelianPerProduk::getUrl() }}" class="report-card">
                <div class="report-card-icon" style="background:#fef3c7"><svg style="color:#d97706" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                    </svg></div><span class="report-card-title">Pemesanan Pembelian per Produk</span>
            </a>
            <a href="{{ \App\Filament\Pages\Laporan\PembelianPerVendor::getUrl() }}" class="report-card">
                <div class="report-card-icon" style="background:#f3e8ff"><svg style="color:#9333ea" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg></div><span class="report-card-title">Pembelian per Vendor</span>
            </a>
            <a href="{{ \App\Filament\Pages\Laporan\PengirimanPembelian::getUrl() }}" class="report-card">
                <div class="report-card-icon" style="background:#fce7f3"><svg style="color:#db2777" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg></div><span class="report-card-title">Pengiriman Pembelian</span>
            </a>
            <a href="{{ \App\Filament\Pages\Laporan\PelunasanPembayaranTagihanPembelian::getUrl() }}"
                class="report-card">
                <div class="report-card-icon" style="background:#dcfce7"><svg style="color:#16a34a" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg></div><span class="report-card-title">Pelunasan Pembayaran</span>
            </a>
            <a href="{{ \App\Filament\Pages\Laporan\PembelianProdukPerVendor::getUrl() }}" class="report-card">
                <div class="report-card-icon" style="background:#dbeafe"><svg style="color:#2563eb" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2m0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg></div><span class="report-card-title">Pembelian Produk per Vendor</span>
            </a>
            <a href="{{ \App\Filament\Pages\Laporan\PembelianPerPeriode::getUrl() }}" class="report-card">
                <div class="report-card-icon" style="background:#dcfce7"><svg style="color:#16a34a" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg></div><span class="report-card-title">Pembelian per Periode</span>
            </a>
            <a href="{{ \App\Filament\Pages\Laporan\PembelianPerRegion::getUrl() }}" class="report-card">
                <div class="report-card-icon" style="background:#f3e8ff"><svg style="color:#9333ea" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg></div><span class="report-card-title">Pembelian per Region</span>
            </a>
        </div>
    </div>

    {{-- Perpajakan --}}
    <div class="report-section">
        <div class="report-section-title"><svg style="color:#dc2626" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
            </svg>Perpajakan</div>
        <div class="report-grid">
            <a href="{{ \App\Filament\Pages\Laporan\PajakPenjualan::getUrl() }}" class="report-card">
                <div class="report-card-icon" style="background:#fee2e2"><svg style="color:#dc2626" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                    </svg></div><span class="report-card-title">Pajak Penjualan</span>
            </a>
        </div>
    </div>

    {{-- Biaya --}}
    <div class="report-section">
        <div class="report-section-title"><svg style="color:#ec4899" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>Biaya</div>
        <div class="report-grid">
            <a href="{{ \App\Filament\Pages\Laporan\BiayaPerKontak::getUrl() }}" class="report-card">
                <div class="report-card-icon" style="background:#fce7f3"><svg style="color:#db2777" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg></div><span class="report-card-title">Biaya per Kontak</span>
            </a>
        </div>
    </div>

    {{-- Inventori --}}
    <div class="report-section">
        <div class="report-section-title"><svg style="color:#0891b2" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>Inventori</div>
        <div class="report-grid">
            <a href="{{ \App\Filament\Pages\Laporan\RingkasanInventori::getUrl() }}" class="report-card">
                <div class="report-card-icon" style="background:#ecfeff"><svg style="color:#0891b2" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg></div><span class="report-card-title">Ringkasan Inventori</span>
            </a>
            <a href="{{ \App\Filament\Pages\Laporan\PergerakanStokGudang::getUrl() }}" class="report-card">
                <div class="report-card-icon" style="background:#dcfce7"><svg style="color:#16a34a" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg></div><span class="report-card-title">Pergerakan Stok Gudang</span>
            </a>
            <a href="{{ \App\Filament\Pages\Laporan\RingkasanStokGudang::getUrl() }}" class="report-card">
                <div class="report-card-icon" style="background:#fef3c7"><svg style="color:#d97706" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                    </svg></div><span class="report-card-title">Ringkasan Stok Gudang</span>
            </a>
            <a href="{{ \App\Filament\Pages\Laporan\PergerakanStokInventori::getUrl() }}" class="report-card">
                <div class="report-card-icon" style="background:#f3e8ff"><svg style="color:#9333ea" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg></div><span class="report-card-title">Pergerakan Stok Inventori</span>
            </a>
            <a href="{{ \App\Filament\Pages\Laporan\LaporanProduksi::getUrl() }}" class="report-card">
                <div class="report-card-icon" style="background:#fce7f3"><svg style="color:#db2777" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg></div><span class="report-card-title">Laporan Produksi</span>
            </a>
            <a href="{{ \App\Filament\Pages\Laporan\LaporanPenyesuaianStok::getUrl() }}" class="report-card">
                <div class="report-card-icon" style="background:#e0e7ff"><svg style="color:#4f46e5" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg></div><span class="report-card-title">Penyesuaian Stok</span>
            </a>
            <a href="{{ \App\Filament\Pages\Laporan\LaporanTransferGudang::getUrl() }}" class="report-card">
                <div class="report-card-icon" style="background:#cffafe"><svg style="color:#0891b2" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg></div><span class="report-card-title">Transfer Gudang</span>
            </a>
            <a href="{{ \App\Filament\Pages\Laporan\PerputaranPersediaan::getUrl() }}" class="report-card">
                <div class="report-card-icon" style="background:#fee2e2"><svg style="color:#dc2626" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg></div><span class="report-card-title">Perputaran Persediaan Barang</span>
            </a>
        </div>
    </div>

    {{-- Aset Tetap --}}
    <div class="report-section">
        <div class="report-section-title"><svg style="color:#ca8a04" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>Aset Tetap</div>
        <div class="report-grid">
            <a href="{{ \App\Filament\Pages\Laporan\RingkasanAsetTetap::getUrl() }}" class="report-card">
                <div class="report-card-icon" style="background:#dbeafe"><svg style="color:#2563eb" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg></div><span class="report-card-title">Ringkasan Aset Tetap</span>
            </a>
            <a href="{{ \App\Filament\Pages\Laporan\DetilAsetTetap::getUrl() }}" class="report-card">
                <div class="report-card-icon" style="background:#dcfce7"><svg style="color:#16a34a" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg></div><span class="report-card-title">Detil Aset Tetap</span>
            </a>
            <a href="{{ \App\Filament\Pages\Laporan\PelepasanAset::getUrl() }}" class="report-card">
                <div class="report-card-icon" style="background:#fef3c7"><svg style="color:#d97706" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                    </svg></div><span class="report-card-title">Pelepasan Aset</span>
            </a>
        </div>
    </div>

    {{-- POS & Lain-lain --}}
    <div class="report-section">
        <div class="report-section-title"><svg style="color:#4f46e5" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>POS & Lainnya</div>
        <div class="report-grid">
            <a href="{{ \App\Filament\Pages\Laporan\LaporanAktivitasTim::getUrl() }}" class="report-card">
                <div class="report-card-icon" style="background:#dbeafe"><svg style="color:#2563eb" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg></div><span class="report-card-title">Laporan Aktivitas Tim</span>
            </a>
            <a href="{{ \App\Filament\Pages\ComingSoon::getUrl(['feature' => 'Penjualan POS per Outlet']) }}"
                class="report-card">
                <div class="report-card-icon" style="background:#dcfce7"><svg style="color:#16a34a" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg></div><span class="report-card-title">Penjualan POS per Outlet</span>
                <span class="coming-soon-badge">Coming Soon</span>
            </a>
            <a href="{{ \App\Filament\Pages\ComingSoon::getUrl(['feature' => 'Detail Produk POS']) }}"
                class="report-card">
                <div class="report-card-icon" style="background:#fef3c7"><svg style="color:#d97706" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg></div><span class="report-card-title">Detail Produk POS</span>
                <span class="coming-soon-badge">Coming Soon</span>
            </a>
            <a href="{{ \App\Filament\Pages\ComingSoon::getUrl(['feature' => 'Attachment Reports']) }}"
                class="report-card">
                <div class="report-card-icon" style="background:#f3e8ff"><svg style="color:#9333ea" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg></div><span class="report-card-title">Attachment</span>
                <span class="coming-soon-badge">Coming Soon</span>
            </a>
            <a href="{{ \App\Filament\Pages\ComingSoon::getUrl(['feature' => 'Rekonsiliasi Reports']) }}"
                class="report-card">
                <div class="report-card-icon" style="background:#fce7f3"><svg style="color:#db2777" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg></div><span class="report-card-title">Rekonsiliasi</span>
                <span class="coming-soon-badge">Coming Soon</span>
            </a>
            <a href="{{ \App\Filament\Pages\ComingSoon::getUrl(['feature' => 'Approval Reports']) }}"
                class="report-card">
                <div class="report-card-icon" style="background:#e0e7ff"><svg style="color:#4f46e5" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg></div><span class="report-card-title">Approval</span>
                <span class="coming-soon-badge">Coming Soon</span>
            </a>
            <a href="{{ \App\Filament\Pages\ComingSoon::getUrl(['feature' => 'Laporan Coretax']) }}"
                class="report-card">
                <div class="report-card-icon" style="background:#cffafe"><svg style="color:#0891b2" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                    </svg></div><span class="report-card-title">Laporan Coretax</span>
                <span class="coming-soon-badge">Coming Soon</span>
            </a>
            <a href="{{ \App\Filament\Pages\ComingSoon::getUrl(['feature' => 'Laporan Faktur Pajak']) }}"
                class="report-card">
                <div class="report-card-icon" style="background:#fee2e2"><svg style="color:#dc2626" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg></div><span class="report-card-title">Laporan Faktur Pajak</span>
                <span class="coming-soon-badge">Coming Soon</span>
            </a>
        </div>
    </div>
</x-filament-panels::page>