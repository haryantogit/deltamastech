<?php

namespace App\Filament\Resources\SalesDeliveryResource\Pages;

use App\Filament\Resources\SalesDeliveryResource;
use Filament\Resources\Pages\ViewRecord;

class ViewTransaction extends ViewRecord
{
    protected static string $resource = SalesDeliveryResource::class;

    protected string $view = 'filament.resources.sales-delivery-resource.pages.view-transaction';

    public function getTitle(): string
    {
        return 'Detil Pengiriman Penjualan ' . $this->record->number;
    }

    public function getHeading(): string
    {
        return 'Detil Pengiriman Penjualan ' . $this->record->number;
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin/penjualan-page') => 'Penjualan',
            SalesDeliveryResource::getUrl('index') => 'Pengiriman Penjualan',
            '#' => 'Detil Pengiriman',
        ];
    }

    public function deliverAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('deliver')
            ->label('Barang Terkirim')
            ->icon('heroicon-o-truck')
            ->color('success')
            ->action(function () {
                $this->record->update([
                    'status' => 'delivered',
                    'date' => now(),
                ]);
                $this->redirect(SalesDeliveryResource::getUrl('view', ['record' => $this->record->id]));
            })
            ->visible(fn() => in_array($this->record->status, ['draft', 'pending']))
            ->requiresConfirmation();
    }

    public function cancelAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('cancel')
            ->label('Batalkan')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->action(fn() => $this->record->update(['status' => 'cancelled']))
            ->visible(fn() => in_array($this->record->status, ['draft']))
            ->requiresConfirmation();
    }
}
