<x-filament-panels::page>
    <style>
        .settings-section {
            margin-bottom: 32px;
        }

        .settings-section-title {
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

        .settings-section-title svg {
            width: 20px;
            height: 20px;
        }

        .settings-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }

        @media (max-width: 1200px) {
            .settings-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 900px) {
            .settings-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .settings-grid {
                grid-template-columns: 1fr;
            }
        }

        .settings-card {
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

        .settings-card:hover {
            border-color: #3b82f6;
            background: #eff6ff;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.1);
        }

        .settings-card-icon {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .settings-card-icon svg {
            width: 20px;
            height: 20px;
        }

        .settings-card-title {
            font-size: 13px;
            font-weight: 500;
            color: #334155;
            line-height: 1.4;
        }

        .settings-card:hover .settings-card-title {
            color: #1e40af;
        }

        .coming-soon-badge {
            position: absolute;
            top: -6px;
            right: -6px;
            background: #fef3c7;
            color: #d97706;
            font-size: 9px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 12px;
            border: 1px solid #fbbf24;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .dark .settings-section-title {
            color: #f1f5f9;
            border-color: #334155;
        }

        .dark .settings-card {
            background: #1e293b;
            border-color: #334155;
        }

        .dark .settings-card:hover {
            background: #1e3a5f;
            border-color: #3b82f6;
        }

        .dark .settings-card-title {
            color: #cbd5e1;
        }

        .dark .settings-card:hover .settings-card-title {
            color: #93c5fd;
        }

        .dark .coming-soon-badge {
            background: #451a03;
            color: #fbbf24;
            border-color: #78350f;
        }
    </style>

    {{-- 1. Perusahaan --}}
    <div class="settings-section">
        <div class="settings-section-title"><svg style="color:#3b82f6" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>Perusahaan</div>
        <div class="settings-grid">
            <a href="{{ \App\Filament\Pages\DataPerusahaan::getUrl() }}" class="settings-card">
                <div class="settings-card-icon" style="background:#dbeafe"><svg style="color:#2563eb" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg></div><span class="settings-card-title">Perusahaan</span>
            </a>
            <a href="{{ \App\Filament\Pages\NotificationSettings::getUrl() }}" class="settings-card">
                <div class="settings-card-icon" style="background:#dbeafe"><svg style="color:#2563eb" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg></div><span class="settings-card-title">Notifikasi</span>
            </a>
        </div>
    </div>

    {{-- 2. Data Master --}}
    <div class="settings-section">
        <div class="settings-section-title"><svg style="color:#f97316" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
            </svg>Data Master</div>
        <div class="settings-grid">
            <a href="{{ \App\Filament\Resources\PaymentTermResource::getUrl('index') }}" class="settings-card">
                <div class="settings-card-icon" style="background:#dbeafe"><svg style="color:#2563eb" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg></div><span class="settings-card-title">Termin</span>
            </a>
            <a href="{{ \App\Filament\Resources\TaxResource::getUrl('index') }}" class="settings-card">
                <div class="settings-card-icon" style="background:#fee2e2"><svg style="color:#dc2626" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z" />
                    </svg></div><span class="settings-card-title">Pajak</span>
            </a>
            <a href="{{ \App\Filament\Resources\UnitResource::getUrl('index') }}" class="settings-card">
                <div class="settings-card-icon" style="background:#dcfce7"><svg style="color:#16a34a" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                    </svg></div><span class="settings-card-title">Satuan</span>
            </a>
            <a href="{{ \App\Filament\Resources\ShippingMethodResource::getUrl('index') }}" class="settings-card">
                <div class="settings-card-icon" style="background:#fef3c7"><svg style="color:#d97706" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25m0 0h-2.25m0 11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v.958" />
                    </svg></div><span class="settings-card-title">Ekspedisi</span>
            </a>
            <a href="{{ \App\Filament\Resources\TagResource::getUrl('index') }}" class="settings-card">
                <div class="settings-card-icon" style="background:#f3e8ff"><svg style="color:#9333ea" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg></div><span class="settings-card-title">Tags</span>
            </a>
            <a href="{{ \App\Filament\Pages\ComingSoon::getUrl(['feature' => 'Backup Database']) }}"
                class="settings-card">
                <div class="coming-soon-badge">Coming Soon</div>
                <div class="settings-card-icon" style="background:#e0e7ff"><svg style="color:#4f46e5" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" />
                    </svg></div><span class="settings-card-title">Backup Database</span>
            </a>
            <a href="{{ \App\Filament\Pages\ComingSoon::getUrl(['feature' => 'Biaya Transaksi']) }}"
                class="settings-card">
                <div class="coming-soon-badge">Coming Soon</div>
                <div class="settings-card-icon" style="background:#fce7f3"><svg style="color:#db2777" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg></div><span class="settings-card-title">Biaya Transaksi</span>
            </a>
        </div>
    </div>

    {{-- 3. Akun & Pengguna --}}
    <div class="settings-section">
        <div class="settings-section-title"><svg style="color:#6366f1" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>Akun & Pengguna</div>
        <div class="settings-grid">
            <a href="{{ \App\Filament\Resources\UserResource::getUrl('index') }}" class="settings-card">
                <div class="settings-card-icon" style="background:#dbeafe"><svg style="color:#2563eb" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg></div><span class="settings-card-title">Pengguna</span>
            </a>
            <a href="{{ \App\Filament\Resources\RoleResource::getUrl('index') }}" class="settings-card">
                <div class="settings-card-icon" style="background:#dcfce7"><svg style="color:#16a34a" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg></div><span class="settings-card-title">Peran</span>
            </a>
            <a href="{{ \App\Filament\Pages\CustomProfile::getUrl() }}" class="settings-card">
                <div class="settings-card-icon" style="background:#f3e8ff"><svg style="color:#9333ea" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg></div><span class="settings-card-title">Profilku</span>
            </a>
        </div>
    </div>

    {{-- 4. Integrasi --}}
    <div class="settings-section">
        <div class="settings-section-title"><svg style="color:#06b6d4" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
            </svg>Integrasi</div>
        <div class="settings-grid">
            <a href="{{ \App\Filament\Pages\ComingSoon::getUrl(['feature' => 'API Key']) }}" class="settings-card">
                <div class="coming-soon-badge">Coming Soon</div>
                <div class="settings-card-icon" style="background:#dbeafe"><svg style="color:#2563eb" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg></div><span class="settings-card-title">API Key</span>
            </a>
            <a href="{{ \App\Filament\Pages\ComingSoon::getUrl(['feature' => 'Marketplace Connect']) }}"
                class="settings-card">
                <div class="coming-soon-badge">Coming Soon</div>
                <div class="settings-card-icon" style="background:#dcfce7"><svg style="color:#16a34a" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg></div><span class="settings-card-title">Marketplace Connect</span>
            </a>
            <a href="{{ \App\Filament\Pages\ComingSoon::getUrl(['feature' => 'POS Connect']) }}" class="settings-card">
                <div class="coming-soon-badge">Coming Soon</div>
                <div class="settings-card-icon" style="background:#fef3c7"><svg style="color:#d97706" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                    </svg></div><span class="settings-card-title">POS Connect</span>
            </a>
            <a href="{{ \App\Filament\Pages\ComingSoon::getUrl(['feature' => 'Woocommerce Connect']) }}"
                class="settings-card">
                <div class="coming-soon-badge">Coming Soon</div>
                <div class="settings-card-icon" style="background:#e0e7ff"><svg style="color:#4f46e5" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg></div><span class="settings-card-title">Woocommerce Connect</span>
            </a>
            <a href="{{ \App\Filament\Pages\ComingSoon::getUrl(['feature' => 'Payment Connect']) }}"
                class="settings-card">
                <div class="coming-soon-badge">Coming Soon</div>
                <div class="settings-card-icon" style="background:#f3e8ff"><svg style="color:#9333ea" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg></div><span class="settings-card-title">Payment Connect</span>
            </a>
            <a href="{{ \App\Filament\Pages\ComingSoon::getUrl(['feature' => 'Pengaturan SSO']) }}"
                class="settings-card">
                <div class="coming-soon-badge">Coming Soon</div>
                <div class="settings-card-icon" style="background:#fce7f3"><svg style="color:#db2777" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg></div><span class="settings-card-title">Pengaturan SSO</span>
            </a>
            <a href="{{ \App\Filament\Pages\ComingSoon::getUrl(['feature' => 'Slack Connect']) }}"
                class="settings-card">
                <div class="coming-soon-badge">Coming Soon</div>
                <div class="settings-card-icon" style="background:#fee2e2"><svg style="color:#dc2626" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg></div><span class="settings-card-title">Slack Connect</span>
            </a>
            <a href="{{ \App\Filament\Pages\ComingSoon::getUrl(['feature' => 'Webhook']) }}" class="settings-card">
                <div class="coming-soon-badge">Coming Soon</div>
                <div class="settings-card-icon" style="background:#dbeafe"><svg style="color:#2563eb" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                    </svg></div><span class="settings-card-title">Webhook</span>
            </a>
        </div>
    </div>

    {{-- 5. Alur Bisnis --}}
    <div class="settings-section">
        <div class="settings-section-title"><svg style="color:#22c55e" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
            </svg>Alur Bisnis</div>
        <div class="settings-grid">
            <a href="{{ \App\Filament\Pages\ComingSoon::getUrl(['feature' => 'Alur Bisnis']) }}" class="settings-card">
                <div class="coming-soon-badge">Coming Soon</div>
                <div class="settings-card-icon" style="background:#dbeafe"><svg style="color:#2563eb" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                    </svg></div><span class="settings-card-title">Alur Bisnis</span>
            </a>
            <a href="{{ \App\Filament\Pages\ComingSoon::getUrl(['feature' => 'Penomoran Otomatis']) }}" class="settings-card">
                <div class="coming-soon-badge">Coming Soon</div>
                <div class="settings-card-icon" style="background:#fef3c7"><svg style="color:#d97706" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                    </svg></div><span class="settings-card-title">Penomoran Otomatis</span>
            </a>
            <a href="{{ \App\Filament\Pages\ComingSoon::getUrl(['feature' => 'Tanggal Penguncian']) }}" class="settings-card">
                <div class="coming-soon-badge">Coming Soon</div>
                <div class="settings-card-icon" style="background:#fce7f3"><svg style="color:#db2777" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg></div><span class="settings-card-title">Tanggal Penguncian</span>
            </a>
            <a href="{{ \App\Filament\Pages\ComingSoon::getUrl(['feature' => 'Pemetaan Akun']) }}" class="settings-card">
                <div class="coming-soon-badge">Coming Soon</div>
                <div class="settings-card-icon" style="background:#e0e7ff"><svg style="color:#4f46e5" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg></div><span class="settings-card-title">Pemetaan Akun</span>
            </a>
            <a href="{{ \App\Filament\Pages\ComingSoon::getUrl(['feature' => 'Audit']) }}" class="settings-card">
                <div class="coming-soon-badge">Coming Soon</div>
                <div class="settings-card-icon" style="background:#dcfce7"><svg style="color:#16a34a" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg></div><span class="settings-card-title">Audit</span>
            </a>
            <a href="{{ \App\Filament\Pages\ComingSoon::getUrl(['feature' => 'Konsolidasi']) }}" class="settings-card">
                <div class="coming-soon-badge">Coming Soon</div>
                <div class="settings-card-icon" style="background:#dbeafe"><svg style="color:#2563eb" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
                    </svg></div><span class="settings-card-title">Konsolidasi</span>
            </a>
            <a href="{{ \App\Filament\Pages\ComingSoon::getUrl(['feature' => 'Custom Fields']) }}" class="settings-card">
                <div class="coming-soon-badge">Coming Soon</div>
                <div class="settings-card-icon" style="background:#fef9c3"><svg style="color:#ca8a04" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg></div><span class="settings-card-title">Custom Fields</span>
            </a>
            <a href="{{ \App\Filament\Pages\ComingSoon::getUrl(['feature' => 'Multi Mata Uang']) }}" class="settings-card">
                <div class="coming-soon-badge">Coming Soon</div>
                <div class="settings-card-icon" style="background:#f3e8ff"><svg style="color:#9333ea" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg></div><span class="settings-card-title">Multi Mata Uang</span>
            </a>
        </div>
    </div>

    {{-- 6. Layout & Template --}}
    <div class="settings-section">
        <div class="settings-section-title"><svg style="color:#a855f7" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
            </svg>Layout & Template</div>
        <div class="settings-grid">
            <a href="{{ \App\Filament\Pages\InvoiceLayoutSettings::getUrl() }}" class="settings-card">
                <div class="settings-card-icon" style="background:#dbeafe"><svg style="color:#2563eb" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg></div><span class="settings-card-title">Layout Invoice</span>
            </a>
            <a href="{{ \App\Filament\Pages\ComingSoon::getUrl(['feature' => 'Layout Laporan']) }}" class="settings-card">
                <div class="coming-soon-badge">Coming Soon</div>
                <div class="settings-card-icon" style="background:#dcfce7"><svg style="color:#16a34a" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg></div><span class="settings-card-title">Layout Laporan</span>
            </a>
            <a href="{{ \App\Filament\Pages\ComingSoon::getUrl(['feature' => 'Translasi']) }}" class="settings-card">
                <div class="coming-soon-badge">Coming Soon</div>
                <div class="settings-card-icon" style="background:#fef3c7"><svg style="color:#d97706" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
                    </svg></div><span class="settings-card-title">Translasi</span>
            </a>
            <a href="{{ \App\Filament\Pages\ComingSoon::getUrl(['feature' => 'Template Email']) }}" class="settings-card">
                <div class="coming-soon-badge">Coming Soon</div>
                <div class="settings-card-icon" style="background:#fce7f3"><svg style="color:#db2777" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg></div><span class="settings-card-title">Template Email</span>
            </a>
            <a href="{{ \App\Filament\Pages\ComingSoon::getUrl(['feature' => 'Template WhatsApp']) }}" class="settings-card">
                <div class="coming-soon-badge">Coming Soon</div>
                <div class="settings-card-icon" style="background:#dcfce7"><svg style="color:#16a34a" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg></div><span class="settings-card-title">Template WhatsApp</span>
            </a>
            <a href="{{ \App\Filament\Pages\ComingSoon::getUrl(['feature' => 'Layout Barcode']) }}" class="settings-card">
                <div class="coming-soon-badge">Coming Soon</div>
                <div class="settings-card-icon" style="background:#e0e7ff"><svg style="color:#4f46e5" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                    </svg></div><span class="settings-card-title">Layout Barcode</span>
            </a>
            <a href="{{ \App\Filament\Pages\ComingSoon::getUrl(['feature' => 'Pengingat Jatuh Tempo']) }}" class="settings-card">
                <div class="coming-soon-badge">Coming Soon</div>
                <div class="settings-card-icon" style="background:#fef9c3"><svg style="color:#ca8a04" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg></div><span class="settings-card-title">Pengingat Jatuh Tempo</span>
            </a>
            <a href="{{ \App\Filament\Pages\ComingSoon::getUrl(['feature' => 'Ulang Tahun Pelanggan']) }}" class="settings-card">
                <div class="coming-soon-badge">Coming Soon</div>
                <div class="settings-card-icon" style="background:#fee2e2"><svg style="color:#dc2626" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 15.546c-.523 0-1.046.151-1.5.454a2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0 2.701 2.701 0 00-1.5-.454M9 6v2m3-2v2m3-2v2M9 3h.01M12 3h.01M15 3h.01M21 21v-7a2 2 0 00-2-2H5a2 2 0 00-2 2v7h18zm-3-9v-2a2 2 0 00-2-2H8a2 2 0 00-2 2v2h12z" />
                    </svg></div><span class="settings-card-title">Ulang Tahun Pelanggan</span>
            </a>
        </div>
    </div>
</x-filament-panels::page>