<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class ComingSoon extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.coming-soon';

    public ?string $feature = null;

    public function mount(): void
    {
        $this->feature = request()->query('feature', 'Fitur');
    }

    public function getTitle(): string
    {
        return '';
    }
}
