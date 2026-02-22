<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseOrder extends EditRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected static ?string $title = 'Edit Pesanan Pembelian';

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin/pembelian-page') => 'Pembelian',
            PurchaseOrderResource::getUrl('index') => 'Pesanan Pembelian',
            '#' => 'Edit Pesanan',
        ];
    }

    public function getMaxContentWidth(): string|null
    {
        return 'full';
    }

    protected function getHeaderActions(): array
    {
        return [
            // 1. Approve Action (Draft -> Ordered)
            Actions\Action::make('approve')
                ->label('Setujui')
                ->icon('heroicon-o-check')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn(\App\Models\PurchaseOrder $record) => $record->status === 'draft')
                ->action(function (\App\Models\PurchaseOrder $record) {
                    $record->update(['status' => 'ordered']);
                    \Filament\Notifications\Notification::make()
                        ->title('Pesanan Pembelian Disetujui')
                        ->success()
                        ->send();
                }),

            // 2. Create Delivery (Only if Ordered/Partial)
            Actions\Action::make('createDelivery')
                ->label('Buat Pengiriman Pembelian')
                ->icon('heroicon-o-truck')
                ->visible(fn(\App\Models\PurchaseOrder $record) => in_array($record->status, ['ordered', 'partial_delivery']))
                ->url(fn(\App\Models\PurchaseOrder $record) => \App\Filament\Resources\PurchaseDeliveryResource::getUrl('create', [
                    'purchase_order_id' => $record->id,
                ])),

            // 3. Create Invoice (Only if Ordered/Received/Partial)
            Actions\Action::make('createInvoice')
                ->label('Buat Tagihan Pembelian')
                ->icon('heroicon-o-document-currency-dollar')
                ->visible(fn(\App\Models\PurchaseOrder $record) => in_array($record->status, ['ordered', 'received', 'partial_billed', 'partial_delivery']))
                ->url(fn(\App\Models\PurchaseOrder $record) => \App\Filament\Resources\PurchaseInvoiceResource::getUrl('create', [
                    'purchase_order_id' => $record->id,
                ])),

            Actions\DeleteAction::make(),
        ];
    }
}
