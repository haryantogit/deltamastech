<?php

namespace App\Filament\Resources\SalesInvoiceResource\Pages;

use App\Filament\Resources\SalesInvoiceResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Contracts\HasForms;

class ViewTransaction extends ViewRecord implements HasForms
{
    use InteractsWithForms;

    public ?array $paymentData = [];

    protected static string $resource = SalesInvoiceResource::class;

    protected string $view = 'filament.resources.sales-invoice-resource.pages.view-transaction';

    public function mount($record): void
    {
        parent::mount($record);

        $this->paymentForm->fill([
            'amount' => $this->record->total_amount - ($this->record->down_payment ?? 0) -
                (\App\Models\Receivable::where('invoice_number', $this->record->invoice_number)->first()?->payments()->sum('amount') ?? 0),
            'number' => $this->generateNextNumber(),
            'date' => date('Y-m-d'),
            'account_id' => \App\Models\Account::where('code', '1-10001')->first()?->id,
        ]);
    }

    protected function generateNextNumber(): string
    {
        $prefix = 'IP/';
        $lastPayment = \App\Models\ReceivablePayment::whereNotNull('number')
            ->where('number', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if (!$lastPayment) {
            return $prefix . '00001';
        }

        $lastNumber = str_replace($prefix, '', $lastPayment->number);
        $nextNumber = (int) filter_var($lastNumber, FILTER_SANITIZE_NUMBER_INT) + 1;

        return $prefix . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }

    public function getForms(): array
    {
        return [
            'paymentForm',
        ];
    }

    public function paymentForm(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Terima pembayaran')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('amount')
                                    ->label('Total Diterima')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required()
                                    ->live(),
                                TextInput::make('number')
                                    ->label('Nomor')
                                    ->readOnly()
                                    ->dehydrated(),
                                DatePicker::make('date')
                                    ->label('Tanggal Transaksi')
                                    ->required()
                                    ->default(now()),
                                Select::make('account_id')
                                    ->label('Setor Ke')
                                    ->options(\App\Models\Account::where('code', 'LIKE', '1-100%')->get()->pluck('name_with_code', 'id'))
                                    ->searchable(['code', 'name'])
                                    ->required(),
                                TextInput::make('notes')
                                    ->label('Referensi')
                                    ->placeholder('Referensi pembayaran dari pelanggan...')
                                    ->columnSpanFull(),
                                FileUpload::make('attachments')
                                    ->label('Lampiran')
                                    ->multiple()
                                    ->directory('payment-attachments')
                                    ->columnSpanFull(),
                                \Filament\Forms\Components\Placeholder::make('footer')
                                    ->hiddenLabel()
                                    ->content(fn($get) => view('filament.resources.purchase-invoice-resource.components.payment-form-footer', [
                                        'amount' => $get('amount') ?: 0,
                                    ]))
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->compact(),
            ])
            ->statePath('paymentData');
    }

    public function getTitle(): string
    {
        return 'Detil Tagihan Penjualan ' . $this->record->invoice_number;
    }

    public function getHeading(): string
    {
        return 'Detil Tagihan Penjualan ' . $this->record->invoice_number;
    }

    public function addPayment(): void
    {
        $data = $this->paymentForm->getState();
        $record = $this->record;
        $amount = (float) $data['amount'];

        if ($amount <= 0) {
            \Filament\Notifications\Notification::make()->title('Jumlah pembayaran harus lebih dari 0')
                ->danger()
                ->send();
            return;
        }

        // Find or create Receivable
        $receivable = \App\Models\Receivable::firstOrCreate(
            ['invoice_number' => $record->invoice_number],
            [
                'contact_id' => $record->contact_id,
                'transaction_date' => $record->transaction_date,
                'due_date' => $record->due_date,
                'total_amount' => $record->total_amount,
                'status' => 'unpaid',
            ]
        );

        // Create Payment
        $receivable->payments()->create([
            'number' => $data['number'] ?? $this->generateNextNumber(),
            'date' => $data['date'],
            'account_id' => $data['account_id'],
            'amount' => $amount,
            'notes' => $data['notes'] ?? null,
            'attachments' => $data['attachments'] ?? null,
        ]);

        // Update Invoice status
        $totalPaid = $receivable->payments()->sum('amount') + ($record->down_payment ?? 0);
        $actualBalance = $record->total_amount - $totalPaid;

        if ($actualBalance <= 0.5) {
            $record->update(['status' => 'paid']);
        } elseif ($totalPaid > 0) {
            $record->update(['status' => 'partial']);
        }

        \Filament\Notifications\Notification::make()
            ->title('Pembayaran berhasil diterima')
            ->success()
            ->send();

        $this->refreshFormData(['status']);

        // Reset form
        $this->paymentForm->fill([
            'amount' => max(0, $actualBalance),
            'number' => $this->generateNextNumber(),
            'date' => date('Y-m-d'),
            'account_id' => $data['account_id'],
        ]);
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
            SalesInvoiceResource::getUrl('index') => 'Tagihan Penjualan',
            '#' => 'Detail Tagihan',
        ];
    }
}