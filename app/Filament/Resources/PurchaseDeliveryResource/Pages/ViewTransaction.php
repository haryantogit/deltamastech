<?php

namespace App\Filament\Resources\PurchaseDeliveryResource\Pages;

use App\Filament\Resources\PurchaseDeliveryResource;
use Filament\Resources\Pages\ViewRecord;

class ViewTransaction extends ViewRecord
{
    protected static string $resource = PurchaseDeliveryResource::class;

    protected string $view = 'filament.resources.purchase-delivery-resource.pages.view-transaction';

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin/pembelian-page') => 'Pembelian',
            PurchaseDeliveryResource::getUrl('index') => 'Pengiriman Pembelian',
            '#' => 'Detail Pengiriman',
        ];
    }

    public function getTitle(): string
    {
        return 'Detil Pengiriman Pembelian ' . $this->record->number;
    }

    public function getHeading(): string
    {
        return 'Detil Pengiriman Pembelian ' . $this->record->number;
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('confirm')
                ->label('Konfirmasi')
                ->icon('heroicon-o-check-circle')
                ->color('info')
                ->action(function () {
                    $updateData = ['status' => 'pending'];
                    if (!$this->record->shipping_date) {
                        $updateData['shipping_date'] = now();
                    }
                    $this->record->update($updateData);
                    $this->redirect(PurchaseDeliveryResource::getUrl('view', ['record' => $this->record->id]));
                })
                ->visible(fn() => $this->record->status === 'draft')
                ->requiresConfirmation(),
            \Filament\Actions\Action::make('receive')
                ->label('Terima Barang')
                ->icon('heroicon-o-archive-box-arrow-down')
                ->color('success')
                ->form([
                    \Filament\Forms\Components\Textarea::make('notes')
                        ->label('Catatan')
                        ->rows(3)
                        ->default(fn() => $this->record->notes),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status' => 'received',
                        'date' => now(),
                        'notes' => $data['notes'],
                    ]);
                    $this->redirect(PurchaseDeliveryResource::getUrl('view', ['record' => $this->record->id]));
                })
                ->visible(fn() => $this->record->status === 'pending')
                ->requiresConfirmation(),
            \Filament\Actions\Action::make('cancel')
                ->label('Batalkan')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->action(fn() => $this->record->update(['status' => 'cancelled']))
                ->visible(fn() => in_array($this->record->status, ['draft', 'pending']))
                ->requiresConfirmation(),

        ];
    }
}
