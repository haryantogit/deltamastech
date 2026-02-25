<?php

namespace App\Filament\Resources\ContactResource\Pages;

use App\Filament\Resources\ContactResource;
use App\Models\Contact;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListContacts extends ListRecords
{
    protected static string $resource = ContactResource::class;

    protected $queryString = [
        'activeTab' => ['except' => null, 'as' => 'tab'],
    ];

    public function getDefaultActiveTab(): string|int|null
    {
        return null;
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            url('/admin/kontak-page') => 'Kontak',
            '#' => 'Daftar Kontak',
        ];
    }

    public function getTabs(): array
    {
        return [
            null => Tab::make('Semua')
                ->badge(Contact::count()),
            'Pelanggan' => Tab::make('Pelanggan')
                ->badge(Contact::where('type', 'customer')->count())
                ->modifyQueryUsing(fn($query) => $query->where('type', 'customer')),
            'Vendor' => Tab::make('Vendor')
                ->badge(Contact::where('type', 'vendor')->count())
                ->modifyQueryUsing(fn($query) => $query->where('type', 'vendor')),
            'Karyawan' => Tab::make('Karyawan')
                ->badge(Contact::where('type', 'employee')->count())
                ->modifyQueryUsing(fn($query) => $query->where('type', 'employee')),
            'Lainnya' => Tab::make('Lainnya')
                ->badge(Contact::whereNotIn('type', ['customer', 'vendor', 'employee'])->count())
                ->modifyQueryUsing(fn($query) => $query->whereNotIn('type', ['customer', 'vendor', 'employee'])),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\ContactResource\Widgets\ContactListStatsWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 4;
    }

    public function getTabsContentComponent(): \Filament\Schemas\Components\Component
    {
        return parent::getTabsContentComponent()
            ->contained(true);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Kontak')
                ->color('primary'),
            Actions\Action::make('print')
                ->label('Cetak')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->extraAttributes(['onclick' => 'window.print(); return false;']),
            Actions\Action::make('kembali')
                ->label('Kembali')
                ->color('gray')
                ->icon('heroicon-o-arrow-left')
                ->url(url('/admin/kontak-page')),
        ];
    }

    public function getFooter(): ?\Illuminate\Contracts\View\View
    {
        return view('filament.resources.contact-resource.pages.list-contacts-footer');
    }
}
