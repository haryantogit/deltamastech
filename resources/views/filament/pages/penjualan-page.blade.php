<x-filament-panels::page>
    <style>
        .hub-section {
            margin-bottom: 32px;
        }

        .hub-section-title {
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

        .hub-section-title svg {
            width: 20px;
            height: 20px;
        }

        .hub-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }

        @media (max-width: 1200px) {
            .hub-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 900px) {
            .hub-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .hub-grid {
                grid-template-columns: 1fr;
            }
        }

        .hub-card {
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

        .hub-card:hover {
            border-color: #3b82f6;
            background: #eff6ff;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.1);
        }

        .hub-card-icon {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .hub-card-icon svg {
            width: 20px;
            height: 20px;
        }

        .hub-card-title {
            font-size: 13px;
            font-weight: 500;
            color: #334155;
            line-height: 1.4;
        }

        .hub-card:hover .hub-card-title {
            color: #1e40af;
        }

        .dark .hub-section-title {
            color: #f1f5f9;
            border-color: #334155;
        }

        .dark .hub-card {
            background: #1e293b;
            border-color: #334155;
        }

        .dark .hub-card:hover {
            background: #1e3a5f;
            border-color: #3b82f6;
        }

        .dark .hub-card-title {
            color: #cbd5e1;
        }

        .dark .hub-card:hover .hub-card-title {
            color: #93c5fd;
        }
    </style>

    {{-- Penawaran & Pesanan --}}
    <div class="hub-section">
        <div class="hub-section-title"><svg style="color:#f59e0b" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>Penawaran & Pesanan</div>
        <div class="hub-grid">
            <a href="{{ \App\Filament\Resources\SalesQuotations\SalesQuotationResource::getUrl('index') }}"
                class="hub-card">
                <div class="hub-card-icon" style="background:#fef3c7"><svg style="color:#d97706" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg></div><span class="hub-card-title">Penawaran Penjualan</span>
            </a>
            <a href="{{ \App\Filament\Resources\SalesOrderResource::getUrl('index') }}" class="hub-card">
                <div class="hub-card-icon" style="background:#dbeafe"><svg style="color:#2563eb" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg></div><span class="hub-card-title">Pesanan Penjualan</span>
            </a>
        </div>
    </div>

    {{-- Pengiriman & Tagihan --}}
    <div class="hub-section">
        <div class="hub-section-title"><svg style="color:#10b981" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>Pengiriman & Tagihan</div>
        <div class="hub-grid">
            <a href="{{ \App\Filament\Resources\SalesDeliveryResource::getUrl('index') }}" class="hub-card">
                <div class="hub-card-icon" style="background:#dcfce7"><svg style="color:#059669" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                    </svg></div><span class="hub-card-title">Pengiriman Penjualan</span>
            </a>
            <a href="{{ \App\Filament\Resources\SalesInvoiceResource::getUrl('index') }}" class="hub-card">
                <div class="hub-card-icon" style="background:#e0e7ff"><svg style="color:#4f46e5" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg></div><span class="hub-card-title">Tagihan Penjualan</span>
            </a>
        </div>
    </div>

    {{-- Lainnya --}}
    <div class="hub-section">
        <div class="hub-section-title"><svg style="color:#6366f1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>Lainnya</div>
        <div class="hub-grid">
            <a href="{{ \App\Filament\Pages\SalesOverview::getUrl() }}" class="hub-card">
                <div class="hub-card-icon" style="background:#e0e7ff"><svg style="color:#4338ca" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg></div><span class="hub-card-title">Ringkasan</span>
            </a>
        </div>
    </div>
</x-filament-panels::page>