<?php

namespace App\Filament\Resources\PurchaseInvoiceResource\Pages;

use App\Filament\Resources\PurchaseInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Models\PurchaseInvoice;

class ListPurchaseInvoices extends ListRecords
{
    protected static string $resource = PurchaseInvoiceResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua')
                ->badge(PurchaseInvoice::count()),
            'unpaid' => Tab::make('Belum Dibayar')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'posted')->where(fn($q) => $q->whereNull('payment_status')->orWhere('payment_status', 'unpaid')))
                ->badge(PurchaseInvoice::where('status', 'posted')->where(fn($q) => $q->whereNull('payment_status')->orWhere('payment_status', 'unpaid'))->count()),
            'partial' => Tab::make('Dibayar Sebagian')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'posted')->where('payment_status', 'partial'))
                ->badge(PurchaseInvoice::where('status', 'posted')->where('payment_status', 'partial')->count()),
            'paid' => Tab::make('Lunas')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'paid')->orWhere('payment_status', 'paid'))
                ->badge(PurchaseInvoice::where('status', 'paid')->orWhere('payment_status', 'paid')->count()),
            'overdue' => Tab::make('Jatuh tempo')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('due_date', '<', now())->where('status', 'posted'))
                ->badge(PurchaseInvoice::where('due_date', '<', now())->where('status', 'posted')->count()),
            'return' => Tab::make('Retur')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('id', '<', 0))
                ->badge(0),
            'recurring' => Tab::make('Transaksi Berulang')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('id', '<', 0))
                ->badge(0),
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

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Tagihan')
                ->color('primary'),
            Actions\Action::make('print')
                ->label('Cetak')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->extraAttributes(['onclick' => 'window.print(); return false;']),
            Actions\Action::make('back')
                ->label('Kembali')
                ->url(url('/admin'))
                ->color('gray'),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin/pembelian-page') => 'Pembelian',
            '#' => 'Tagihan Pembelian',
        ];
    }

    public function getFooter(): ?\Illuminate\Contracts\View\View
    {
        return view('filament.resources.purchase-invoice-resource.pages.list-purchase-invoices-footer');
    }
}
