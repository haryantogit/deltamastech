<?php

namespace App\Filament\Resources\AccountResource\Pages;

use App\Filament\Resources\AccountResource;
use App\Models\Account;
use App\Models\DebtPayment;
use Filament\Resources\Pages\Page;
use Filament\Actions\Action;

use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Filament\Resources\PurchaseInvoiceResource;
use App\Filament\Resources\PurchaseOrderResource;

class ViewTransaction extends Page
{
    protected static string $resource = AccountResource::class;

    protected string $view = 'filament.resources.account-resource.pages.view-transaction';

    public Account $record;
    public $payment;
    public string $sourceUrl = '#';
    public ?string $orderNumber = null;
    public ?string $orderUrl = null;
    public ?float $downPayment = 0;

    public function mount(Account $record, $payment)
    {
        $this->record = $record;
        // Adjust query as needed to include relationships
        $this->payment = DebtPayment::with(['debt.supplier', 'account'])->findOrFail($payment);

        // Resolve Source URL and Details
        $reference = $this->payment->debt->reference ?? null;
        if ($reference) {
            $invoice = PurchaseInvoice::where('number', $reference)->first();
            if ($invoice) {
                $this->sourceUrl = PurchaseInvoiceResource::getUrl('view', ['record' => $invoice->id]);
                $this->downPayment = $invoice->down_payment ?? 0;

                if ($invoice->purchaseOrder) {
                    $this->orderNumber = $invoice->purchaseOrder->number;
                    $this->orderUrl = PurchaseOrderResource::getUrl('view', ['record' => $invoice->purchaseOrder->id]);
                }
            } else {
                $order = PurchaseOrder::where('number', $reference)->first();
                if ($order) {
                    $this->sourceUrl = PurchaseOrderResource::getUrl('view', ['record' => $order->id]);
                    $this->orderNumber = $order->number;
                    $this->orderUrl = $this->sourceUrl;
                }
            }
        }

        // Dynamic title based on Account
        $this->heading = $this->record->name . ' (' . $this->record->code . ')';
        $this->subheading = 'Transaksi: Pembayaran Pembelian';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Kembali')
                ->color('warning')
                ->url(url()->previous()), // Or redirect to Account view/list
        ];
    }

    public function auditLog(): Action
    {
        return Action::make('auditLog')
            ->modalHeading('Audit')
            ->modalContent(view('filament.components.audit-log-timeline', ['record' => $this->payment]))
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->modalWidth('md');
    }
}
