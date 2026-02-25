<?php

namespace App\Filament\Resources\SalesOrderResource\Pages;

use App\Filament\Resources\SalesOrderResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditSalesOrder extends EditRecord
{
    protected static string $resource = SalesOrderResource::class;

    protected static ?string $title = 'Edit Pesanan Penjualan';

    public function getMaxContentWidth(): string|null
    {
        return 'full';
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin/penjualan-page') => 'Penjualan',
            SalesOrderResource::getUrl('index') => 'Pesanan Penjualan',
            '#' => 'Edit Pesanan',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            // 1. Approve Action (Draft -> Confirmed)
            \Filament\Actions\Action::make('approve')
                ->label('Konfirmasi Pesanan')
                ->icon('heroicon-o-check')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn(\App\Models\SalesOrder $record) => $record->status === 'draft')
                ->action(function (\App\Models\SalesOrder $record) {
                    $record->update(['status' => 'ordered']);
                    \Filament\Notifications\Notification::make()
                        ->title('Pesanan Dikonfirmasi')
                        ->success()
                        ->send();
                }),

            // 2. Create Delivery (Only if Confirmed)
            \Filament\Actions\Action::make('createDelivery')
                ->label('Buat Pengiriman')
                ->icon('heroicon-o-truck')
                ->visible(fn(\App\Models\SalesOrder $record) => in_array($record->status, ['confirmed', 'processing', 'partial_shipped']))
                ->url(fn(\App\Models\SalesOrder $record) => \App\Filament\Resources\SalesDeliveryResource::getUrl('create', [
                    'sales_order_id' => $record->id,
                ])),

            // 3. Create Invoice (Only if Confirmed/Shipped)
            \Filament\Actions\Action::make('createInvoice')
                ->label('Buat Tagihan')
                ->icon('heroicon-o-document-currency-dollar')
                ->visible(fn(\App\Models\SalesOrder $record) => in_array($record->status, ['confirmed', 'shipped', 'delivered', 'partial_invoiced']))
                ->url(fn(\App\Models\SalesOrder $record) => \App\Filament\Resources\SalesInvoiceResource::getUrl('create', [
                    'sales_order_id' => $record->id,
                ])),

            \Filament\Actions\DeleteAction::make(),
            Action::make('kembali')
                ->label('Kembali')
                ->color('gray')
                ->icon('heroicon-o-arrow-left')
                ->url(static::getResource()::getUrl('index')),
        ];
    }
}
