<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;

class ViewTransaction extends ViewRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected string $view = 'filament.resources.purchase-order-resource.pages.view-transaction';

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin/pembelian-page') => 'Pembelian',
            PurchaseOrderResource::getUrl('index') => 'Pesanan Pembelian',
            '#' => 'Detil Pesanan',
        ];
    }

    public function getTitle(): string
    {
        return 'Detil Pesanan Pembelian ' . $this->record->number;
    }

    public function getHeading(): string
    {
        return 'Detil Pesanan Pembelian ' . $this->record->number;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function approve(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('approve')
            ->label('Pesan')
            ->color('success')
            ->icon('heroicon-o-check')
            ->action(function () {
                $this->record->update(['status' => 'ordered']);

                Notification::make()
                    ->title('Pesanan Berhasil Dibuat')
                    ->success()
                    ->send();

                $this->redirect(PurchaseOrderResource::getUrl('view', ['record' => $this->record]));
            })
            ->requiresConfirmation()
            ->visible(fn() => $this->record->status === 'draft');
    }

    public function reject(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('reject')
            ->label('Batalkan')
            ->color('danger')
            ->icon('heroicon-o-x-mark')
            ->action(function () {
                $this->record->update(['status' => 'cancelled']);

                Notification::make()
                    ->title('Pesanan Dibatalkan')
                    ->danger()
                    ->send();

                $this->redirect(PurchaseOrderResource::getUrl('view', ['record' => $this->record]));
            })
            ->requiresConfirmation()
            ->visible(fn() => $this->record->status === 'draft');
    }

    public function auditLog(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('auditLog')
            ->modalHeading('Audit')
            ->modalContent(view('filament.components.audit-log-timeline', ['record' => $this->record]))
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->modalWidth('md');
    }
}
