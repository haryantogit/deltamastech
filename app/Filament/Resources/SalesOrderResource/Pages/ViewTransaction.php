<?php

namespace App\Filament\Resources\SalesOrderResource\Pages;

use App\Filament\Resources\SalesOrderResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;

class ViewTransaction extends ViewRecord
{
    protected static string $resource = SalesOrderResource::class;

    protected string $view = 'filament.resources.sales-order-resource.pages.view-transaction';

    public function getTitle(): string
    {
        return 'Detil Pesanan Penjualan ' . $this->record->number;
    }

    public function getHeading(): string
    {
        return 'Detil Pesanan Penjualan ' . $this->record->number;
    }

    public function approve(): void
    {
        $this->record->update(['status' => 'ordered']);

        Notification::make()
            ->title('Pesanan Disetujui')
            ->success()
            ->send();

        $this->redirect(SalesOrderResource::getUrl('view', ['record' => $this->record]));
    }

    public function reject(): void
    {
        $this->record->update(['status' => 'cancelled']);

        Notification::make()
            ->title('Pesanan Ditolak')
            ->danger()
            ->send();

        $this->redirect(SalesOrderResource::getUrl('view', ['record' => $this->record]));
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

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin/penjualan-page') => 'Penjualan',
            SalesOrderResource::getUrl('index') => 'Pesanan Penjualan',
            '#' => 'Detil Pesanan',
        ];
    }
}
