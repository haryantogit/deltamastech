<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Pengaturan extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected string $view = 'filament.pages.pengaturan';

    protected static ?string $title = 'Pengaturan';

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            'Pengaturan',
        ];
    }

    public static function getNavigationItemActiveRoutePattern(): string|array
    {
        return [
            'filament.admin.pages.pengaturan',
            'filament.admin.pages.data-perusahaan',
            'filament.admin.pages.notification-settings',
            'filament.admin.pages.invoice-layout-settings',
            'filament.admin.pages.profile',
            'filament.admin.pages.audit',
            'filament.admin.pages.penomoran-otomatis',
            'filament.admin.resources.roles.*',
            'filament.admin.resources.pajak.*',
            'filament.admin.resources.users.*',
            'filament.admin.resources.units.*',
            'filament.admin.resources.shipping-methods.*',
            'filament.admin.resources.tags.*',
            'filament.admin.resources.payment-terms.*',
        ];
    }

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 11;

    protected static ?string $navigationLabel = 'Pengaturan';

    public function getHeading(): string
    {
        return 'Pengaturan';
    }
}
