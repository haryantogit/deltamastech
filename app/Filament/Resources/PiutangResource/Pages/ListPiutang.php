<?php

namespace App\Filament\Resources\PiutangResource\Pages;

use App\Filament\Resources\PiutangResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Receivable;

class ListPiutang extends ListRecords
{
    protected static string $resource = PiutangResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Tambah Piutang'),
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
        return view('filament.resources.piutang-resource.pages.list-piutang-footer');
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua')
                ->badge(Receivable::where('invoice_number', 'like', 'DM/%')->count()),
            'unpaid' => Tab::make('Belum Bayar')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'unpaid'))
                ->badge(Receivable::where('invoice_number', 'like', 'DM/%')->where('status', 'unpaid')->count()),
            'partial' => Tab::make('Dibayar Sebagian')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'partial'))
                ->badge(Receivable::where('invoice_number', 'like', 'DM/%')->where('status', 'partial')->count()),
            'paid' => Tab::make('Lunas')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'paid'))
                ->badge(Receivable::where('invoice_number', 'like', 'DM/%')->where('status', 'paid')->count()),
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
            '#' => 'Piutang',
        ];
    }
}
