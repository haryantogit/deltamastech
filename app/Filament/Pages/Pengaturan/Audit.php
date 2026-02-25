<?php

namespace App\Filament\Pages\Pengaturan;

use App\Models\User;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

class Audit extends Page
{
    use WithPagination;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected string $view = 'filament.pages.pengaturan.audit';

    protected static ?string $title = 'Audit';

    protected static ?string $slug = 'audit';

    protected static bool $shouldRegisterNavigation = false;

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\Pengaturan::getUrl() => 'Pengaturan',
            'Audit',
        ];
    }

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kembali')
                ->label('Kembali')
                ->color('gray')
                ->icon('heroicon-o-arrow-left')
                ->url(\App\Filament\Pages\Pengaturan::getUrl()),
        ];
    }

    public function getViewData(): array
    {
        $activities = Activity::query()
            ->with('causer')
            ->latest()
            ->paginate(25);

        return [
            'activities' => $activities,
        ];
    }
}
