<?php

namespace App\Filament\Resources\HutangResource\Pages;

use App\Filament\Resources\HutangResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Debt;

class ListHutang extends ListRecords
{
    protected static string $resource = HutangResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Tambah Hutang'),
            Actions\Action::make('print')
                ->label('Cetak')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->extraAttributes(['onclick' => 'window.print(); return false;']),
            Actions\Action::make('back')
                ->label('Kembali')
                ->color('gray')
                ->url(url('/admin/kontak-page')),
        ];
    }

    public function getFooter(): ?\Illuminate\Contracts\View\View
    {
        return view('filament.resources.hutang-resource.pages.list-hutang-footer');
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua')
                ->badge(Debt::where('number', 'like', 'CM/%')->count()),
            'unpaid' => Tab::make('Belum Bayar')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('payment_status', 'unpaid'))
                ->badge(Debt::where('number', 'like', 'CM/%')->where('payment_status', 'unpaid')->count()),
            'partial' => Tab::make('Dibayar Sebagian')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('payment_status', 'partial'))
                ->badge(Debt::where('number', 'like', 'CM/%')->where('payment_status', 'partial')->count()),
            'paid' => Tab::make('Lunas')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('payment_status', 'paid'))
                ->badge(Debt::where('number', 'like', 'CM/%')->where('payment_status', 'paid')->count()),
        ];
    }

    public function getTabsContentComponent(): \Filament\Schemas\Components\Component
    {
        return \Filament\Schemas\Components\Tabs::make()
            ->livewireProperty('activeTab')
            ->contained(true)
            ->tabs($this->getCachedTabs())
            ->hidden(empty($this->getCachedTabs()));
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            url('/admin/kontak-page') => 'Kontak',
            '#' => 'Hutang',
        ];
    }
}
