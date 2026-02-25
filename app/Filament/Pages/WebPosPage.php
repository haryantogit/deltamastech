<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Outlet;

class WebPosPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-computer-desktop';

    protected string $view = 'filament.pages.web-pos';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Web Pos';

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin/pos-page') => 'POS',
            'Web Pos',
        ];
    }

    public function getViewData(): array
    {
        return [
            'outlets' => Outlet::all(),
        ];
    }
}
