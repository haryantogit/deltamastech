<x-filament-panels::page>
    <style>
        .pos-section {
            margin-bottom: 32px;
        }

        .pos-section-title {
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

        .pos-section-title svg {
            width: 20px;
            height: 20px;
        }

        .pos-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }

        @media (max-width: 1200px) {
            .pos-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 900px) {
            .pos-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .pos-grid {
                grid-template-columns: 1fr;
            }
        }

        .pos-card {
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

        .pos-card:hover {
            border-color: #3b82f6;
            background: #eff6ff;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.1);
        }

        .pos-card-icon {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .pos-card-icon svg {
            width: 20px;
            height: 20px;
        }

        .pos-card-title {
            font-size: 13px;
            font-weight: 500;
            color: #334155;
            line-height: 1.4;
        }

        .pos-card:hover .pos-card-title {
            color: #1e40af;
        }

        .dark .pos-section-title {
            color: #f1f5f9;
            border-color: #334155;
        }

        .dark .pos-card {
            background: #1e293b;
            border-color: #334155;
        }

        .dark .pos-card:hover {
            background: #1e3a5f;
            border-color: #3b82f6;
        }

        .dark .pos-card-title {
            color: #cbd5e1;
        }

        .dark .pos-card:hover .pos-card-title {
            color: #93c5fd;
        }
    </style>

    {{-- POS Hub Section --}}
    @if(auth()->user()->can('view_menu_kasir') || auth()->user()->can('view_menu_produk_favorit') || auth()->user()->can('view_any_outlet') || auth()->user()->can('view_menu_pesanan_pos') || auth()->user()->can('view_menu_pengaturan_pos'))
        <div class="pos-section">
            <div class="pos-section-title">
                <svg style="color:#3b82f6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                POS Hub
            </div>
            <div class="pos-grid">
                {{-- Web POS --}}
                @can('view_menu_kasir')
                    <a href="{{ \App\Filament\Pages\WebPosPage::getUrl() }}" class="pos-card">
                        <div class="pos-card-icon" style="background:#dbeafe">
                            <svg style="color:#2563eb" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <span class="pos-card-title">Web Pos</span>
                    </a>
                @endcan

                {{-- Produk Favorit --}}
                @can('view_menu_produk_favorit')
                    <a href="{{ \App\Filament\Pages\Pos\FavoriteProductPage::getUrl() }}" class="pos-card">
                        <div class="pos-card-icon" style="background:#fce7f3">
                            <svg style="color:#db2777" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                        <span class="pos-card-title">Produk Favorit</span>
                    </a>
                @endcan

                {{-- Outlet --}}
                @can('view_any_outlet')
                    <a href="{{ \App\Filament\Resources\OutletResource::getUrl('index') }}" class="pos-card">
                        <div class="pos-card-icon" style="background:#fef3c7">
                            <svg style="color:#d97706" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <span class="pos-card-title">Outlet</span>
                    </a>
                @endcan

                {{-- Pesanan --}}
                @can('view_menu_pesanan_pos')
                    <a href="{{ \App\Filament\Pages\Pos\PosOrderPage::getUrl() }}" class="pos-card">
                        <div class="pos-card-icon" style="background:#e0e7ff">
                            <svg style="color:#4f46e5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                        </div>
                        <span class="pos-card-title">Pesanan</span>
                    </a>
                @endcan

                {{-- Pengaturan --}}
                @can('view_menu_pengaturan_pos')
                    <a href="{{ \App\Filament\Pages\Pos\PosSettings::getUrl() }}" class="pos-card">
                        <div class="pos-card-icon" style="background:#f1f5f9">
                            <svg style="color:#64748b" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <span class="pos-card-title">Pengaturan</span>
                    </a>
                @endcan
            </div>
        </div>
    @endif
</x-filament-panels::page>