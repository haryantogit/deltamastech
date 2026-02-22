<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Support\Facades\Blade;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->maxContentWidth('full')
            ->login(\App\Filament\Auth\CustomLogin::class)
            // Menggunakan halaman kustom sebagai profil
            ->userMenuItems([
                'profile' => \Filament\Navigation\MenuItem::make()
                    ->label('Profilku')
                    ->icon('heroicon-o-user-circle')
                    ->url(fn() => \App\Filament\Pages\CustomProfile::getUrl()),
            ])

            // --- PENGATURAN SIDEBAR & BRANDING ---
            ->sidebarCollapsibleOnDesktop()
            // Nama Perusahaan (muncul/sembunyi di view custom)
            ->brandName(fn() => \App\Models\Company::first()?->name ?? 'Delta Mas Tech')
            // Menggunakan view custom untuk Logo + Teks dinamis
            ->brandLogo(fn() => view('filament.components.brand-logo'))
            ->brandLogoHeight('2rem')
            ->favicon(asset('images/logo.png'))

            ->darkMode(true)
            ->colors([
                'primary' => Color::Blue,
                'danger' => Color::Rose,
                'gray' => Color::Slate,
                'info' => Color::Sky,
                'success' => Color::Emerald,
                'warning' => Color::Orange,
            ])
            ->databaseNotifications()
            ->globalSearch(false)
            ->renderHook(
                'panels::global-search.before',
                fn(): string => \Illuminate\Support\Facades\Blade::render(<<<HTML
                    <div class="hidden lg:flex gap-2 items-center custom-topbar-buttons">
                        <x-filament::button
                            tag="a"
                            href="{{ \App\Filament\Pages\BiayaPage::getUrl() }}"
                            color="danger"
                            size="sm"
                            icon="heroicon-m-banknotes"
                            outlined
                        >
                            Biaya
                        </x-filament::button>
                        <x-filament::button
                            tag="a"
                            href="{{ \App\Filament\Pages\PembelianPage::getUrl() }}"
                            color="success"
                            size="sm"
                            icon="heroicon-m-truck"
                            outlined
                        >
                            Pembelian
                        </x-filament::button>
                        <x-filament::button
                            tag="a"
                            href="{{ \App\Filament\Pages\PenjualanPage::getUrl() }}"
                            size="sm"
                            icon="heroicon-m-shopping-cart"
                            outlined
                        >
                            Penjualan
                        </x-filament::button>
                    </div>
                HTML)
            )
            ->renderHook(
                'panels::user-menu.before',
                fn(): string => \Illuminate\Support\Facades\Blade::render('<div class="hidden lg:flex items-center gap-2 mr-4 text-sm font-medium text-gray-500 dark:text-gray-400">Selamat Bekerja, {{ auth()->user()->name }}</div>')
            )
            ->font('Inter')
            // --- GANTI IKON TOGGLE KE HAMBURGER ---
            ->icons([
                // Menggunakan ikon 'bars-3' (garis tiga) untuk kedua status
                'panels::sidebar.collapse-button' => 'heroicon-o-bars-3',
                'panels::sidebar.expand-button' => 'heroicon-o-bars-3',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                // Widgets are auto-discovered
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->navigationGroups([
                \Filament\Navigation\NavigationGroup::make()
                    ->label('Menu Utama')
                    ->icon('heroicon-o-home')
                    ->collapsible()
                    ->collapsed(false),
                \Filament\Navigation\NavigationGroup::make()
                    ->label('Kontak')
                    ->icon('heroicon-o-users')
                    ->collapsible()
                    ->collapsed(),
                \Filament\Navigation\NavigationGroup::make()
                    ->label('Penjualan')
                    ->icon('heroicon-o-shopping-cart')
                    ->collapsible()
                    ->collapsed(),
                \Filament\Navigation\NavigationGroup::make()
                    ->label('Pembelian')
                    ->icon('heroicon-o-truck')
                    ->collapsible()
                    ->collapsed(),
                \Filament\Navigation\NavigationGroup::make()
                    ->label('Inventori')
                    ->icon('heroicon-o-cube')
                    ->collapsible()
                    ->collapsed(),
                \Filament\Navigation\NavigationGroup::make()
                    ->label('Mengelola Akses')
                    ->icon('heroicon-o-shield-check')
                    ->collapsible()
                    ->collapsed(),
                \Filament\Navigation\NavigationGroup::make()
                    ->label('Lainnya')
                    ->icon('heroicon-o-ellipsis-horizontal-circle')
                    ->collapsible()
                    ->collapsed(),
            ])

            // --- CSS TAMBAHAN (Padding Konten & Footer Style) ---
            ->renderHook(
                'panels::head.end',
                fn(): string => <<<'HTML'
                <style>
                    /* Memberikan Jarak Konten (Agar tidak mepet Sidebar) */
                    .fi-main {
                        padding-left: 1rem !important; 
                        padding-right: 1rem !important;
                    }
                    @media (min-width: 1024px) {
                        .fi-main {
                            padding-left: 2rem !important; 
                            padding-right: 2rem !important;
                        }
                    }

                    /* Responsive Header Actions (Buttons) */
                    @media (max-width: 640px) {
                        .fi-ac-actions {
                            display: grid !important;
                            grid-template-columns: repeat(2, 1fr) !important;
                            gap: 0.5rem !important;
                            width: 100% !important;
                        }
                        .fi-ac-actions > * {
                            width: 100% !important;
                        }
                        .fi-ac-actions button, 
                        .fi-ac-actions a {
                            justify-content: center !important;
                            width: 100% !important;
                        }
                        .fi-header-heading {
                            font-size: 1.25rem !important;
                        }
                    }

                    /* Style Footer */
                    .fi-sidebar-footer {
                        border-top: none !important;
                        padding: 1.25rem 1rem !important;
                        margin-top: auto !important;
                        width: 100% !important;
                    }
                    .dark .fi-sidebar-footer {
                        border-top: none !important;
                    }

                    /* Jarak antar widget */
                    .fi-wi {
                        margin-bottom: 2rem !important;
                    }

                    /* Hide custom topbar buttons on mobile/tablet */
                    @media (max-width: 1023px) {
                        .custom-topbar-buttons {
                            display: none !important;
                        }
                    }

                    /* --- PRINT OPTIMIZATION --- */
                    @media print {
                        .fi-sidebar,
                        .fi-sidebar-active,
                        .fi-topbar,
                        .fi-header,
                        .fi-header-actions,
                        .fi-breadcrumbs,
                        .fi-footer,
                        .fi-sidebar-header,
                        .fi-sidebar-footer,
                        .fi-wi-dashboard-filter,
                        aside, nav, header, footer, button, .fi-btn, .fi-ac-actions,
                        .custom-topbar-buttons,
                        [role="navigation"],
                        [data-sidebar] {
                            display: none !important;
                        }

                        main, .fi-main {
                            margin: 0 !important;
                            margin-inline-start: 0 !important;
                            padding: 0 !important;
                            width: 100% !important;
                            display: block !important;
                        }

                        .fi-main-ctn, .fi-page, body {
                            background: white !important;
                        }

                        .fi-wi-chart, .fi-wi-stats-overview, .fi-wi-widget {
                            page-break-inside: avoid;
                            break-inside: avoid;
                            margin-bottom: 20px !important;
                            box-shadow: none !important;
                            border: none !important;
                        }

                        * {
                            -webkit-print-color-adjust: exact !important;
                            print-color-adjust: exact !important;
                        }
                    }
                </style>
                HTML
            )

            // --- RENDER HOOK FOOTER VERSI ---
            ->renderHook(
                'panels::sidebar.footer',
                fn(): string => <<<'HTML'
                    <div class="fi-sidebar-footer" style="display: flex; justify-content: flex-start; width: 100%;">
                        <span style="
                            display: inline-flex; 
                            align-items: center; 
                            justify-content: center; 
                            padding: 4px 12px; 
                            font-size: 11px; 
                            font-weight: 600; 
                            color: #1e40af; 
                            background-color: #dbeafe; 
                            border: 1px solid #93c5fd; 
                            border-radius: 6px;
                        ">
                            sierp.v18226
                        </span>
                    </div>
                HTML
            )
            ->renderHook(
                'panels::body.start',
                fn() => view('filament.hooks.sidebar-active-fix')
            );
    }
}