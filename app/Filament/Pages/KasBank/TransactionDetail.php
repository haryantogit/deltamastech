<?php

namespace App\Filament\Pages\KasBank;

use App\Models\JournalEntry;
use Filament\Pages\Page;
use Illuminate\Support\HtmlString;

class TransactionDetail extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected string $view = 'filament.pages.kas-bank.transaction-detail';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'kas-bank/transaction/{id}/detail';

    public JournalEntry $record;

    public function mount(int $id): void
    {
        $this->record = JournalEntry::with(['items', 'items.account', 'tags'])->findOrFail($id);
    }

    public function getTitle(): string
    {
        $account = $this->record->items()
            ->whereHas('account', function ($q) {
                $q->where('category', 'Kas & Bank');
            })
            ->first()?->account;

        $accountName = $account ? $account->name : 'Kas';

        return "Kas {$this->record->reference_number}";
    }

    public function getTransactionData(): array
    {
        $ref = $this->record->reference_number;
        $data = [
            'type' => 'Jurnal Umum',
            'recipient' => '-',
            'recipient_url' => null,
            'reference' => $this->record->memo,
            'source_url' => null,
        ];

        if (str_starts_with($ref, 'EXP/')) {
            $data['type'] = 'Pembayaran Biaya';
            $expense = \App\Models\Expense::where('reference_number', $ref)->with('contact')->first();
            if ($expense) {
                $data['recipient'] = $expense->contact?->name ?? '-';
                $data['recipient_url'] = $expense->contact ? \App\Filament\Resources\ContactResource::getUrl('view', ['record' => $expense->contact]) : null;
                $data['reference'] = $expense->memo ?? $data['reference'];
                $data['source_url'] = \App\Filament\Resources\ExpenseResource::getUrl('view', ['record' => $expense]);
            }
        } elseif (str_starts_with($ref, 'DM/')) {
            $data['type'] = 'Penjualan';
            $receivable = \App\Models\Receivable::where('invoice_number', $ref)->with('contact')->first();
            if ($receivable) {
                $data['recipient'] = $receivable->contact?->name ?? '-';
                $data['recipient_url'] = $receivable->contact ? \App\Filament\Resources\ContactResource::getUrl('view', ['record' => $receivable->contact]) : null;
                $data['reference'] = $receivable->reference ?? $data['reference'];
                $data['source_url'] = \App\Filament\Resources\PiutangResource::getUrl('view', ['record' => $receivable]);
            }
        } elseif (str_starts_with($ref, 'CM/')) {
            $data['type'] = 'Pembelian';
            $debt = \App\Models\Debt::where('number', $ref)->with('supplier')->first();
            if ($debt) {
                $data['recipient'] = $debt->supplier?->name ?? '-';
                $data['recipient_url'] = $debt->supplier ? \App\Filament\Resources\ContactResource::getUrl('view', ['record' => $debt->supplier]) : null;
                $data['reference'] = $debt->reference ?? $data['reference'];
                $data['source_url'] = \App\Filament\Resources\HutangResource::getUrl('view', ['record' => $debt]);
            }
        } elseif (str_starts_with($ref, 'PP/')) {
            $data['type'] = 'Pembayaran Pembelian';
            $payment = \App\Models\DebtPayment::where('number', $ref)->with('debt.supplier')->first();
            if ($payment) {
                $data['recipient'] = $payment->debt?->supplier?->name ?? '-';
                $data['recipient_url'] = $payment->debt?->supplier ? \App\Filament\Resources\ContactResource::getUrl('view', ['record' => $payment->debt->supplier]) : null;
                $data['reference'] = $payment->notes ?? $data['reference'];
                // Link back to the invoice if exists
                if ($payment->debt?->reference) {
                    $invoice = \App\Models\PurchaseInvoice::where('number', $payment->debt->reference)->first();
                    if ($invoice) {
                        $data['source_url'] = \App\Filament\Resources\PurchaseInvoiceResource::getUrl('view', ['record' => $invoice]);
                    }
                }
            }
        } elseif (str_starts_with($ref, 'IP/')) {
            $data['type'] = 'Penerimaan Penjualan';
            $payment = \App\Models\ReceivablePayment::where('number', $ref)->with('receivable.contact')->first();
            if ($payment) {
                $data['recipient'] = $payment->receivable?->contact?->name ?? '-';
                $data['recipient_url'] = $payment->receivable?->contact ? \App\Filament\Resources\ContactResource::getUrl('view', ['record' => $payment->receivable->contact]) : null;
                $data['reference'] = $payment->notes ?? $data['reference'];
                if ($payment->receivable?->reference) {
                    $invoice = \App\Models\SalesInvoice::where('invoice_number', $payment->receivable->reference)->first();
                    if ($invoice) {
                        $data['source_url'] = \App\Filament\Resources\SalesInvoiceResource::getUrl('view', ['record' => $invoice]);
                    }
                }
            }
        } elseif (str_starts_with($ref, 'TR/')) {
            $data['type'] = 'Transfer Uang';
        }

        return $data;
    }

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        $account = $this->record->items()
            ->whereHas('account', function ($q) {
                $q->where('category', 'Kas & Bank');
            })
            ->first()?->account;

        $accountName = $account ? $account->name : 'Kas';
        $accountCode = $account ? $account->code : '';

        $txData = $this->getTransactionData();
        $type = $txData['type'];

        return new HtmlString("
            <div>
                <h1 class=\"text-2xl font-bold text-gray-900 dark:text-white\">
                    {$accountName}
                    <span class=\"text-gray-500 font-normal text-base ml-1\">({$accountCode})</span>
                </h1>
                <p class=\"text-sm text-gray-600 dark:text-gray-400 mt-1\">
                    Transaksi: {$type}
                </p>
            </div>
        ");
    }

    public function getBreadcrumbs(): array
    {
        $account = $this->record->items()
            ->whereHas('account', function ($q) {
                $q->where('category', 'Kas & Bank');
            })
            ->first()?->account;

        $accountName = $account ? $account->name : 'Kas';
        $accountId = $account ? $account->id : '';

        return [
            url('/admin') => 'Beranda',
            '/admin/kas-bank' => 'Kas & Bank',
            "/admin/kas-bank/detail/{$accountId}" => $accountName,
            'Detil',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
