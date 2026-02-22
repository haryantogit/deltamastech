<?php

namespace App\Filament\Resources\PurchaseInvoiceResource\Pages;

use App\Filament\Resources\PurchaseInvoiceResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

class ViewTransaction extends ViewRecord implements HasForms
{
    use InteractsWithForms;

    public ?array $paymentData = [];

    protected static string $resource = PurchaseInvoiceResource::class;

    protected string $view = 'filament.resources.purchase-invoice-resource.pages.view-transaction';

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin/pembelian-page') => 'Pembelian',
            PurchaseInvoiceResource::getUrl('index') => 'Tagihan Pembelian',
            '#' => 'Detail Tagihan',
        ];
    }

    public function mount($record): void
    {
        parent::mount($record);

        $this->paymentForm->fill([
            'amount' => $this->record->balance_due,
            'number' => $this->generateNextNumber(),
            'date' => date('Y-m-d'),
            'account_id' => \App\Models\Account::where('code', '1-10001')->first()?->id,
        ]);
    }

    protected function generateNextNumber(): string
    {
        $prefix = 'PP/';
        $lastPayment = \App\Models\DebtPayment::whereNotNull('number')
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
                Section::make('Lakukan Pembayaran')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('amount')
                                    ->label('Total Dibayar')
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
                                    ->label('Akun Kas/Bank')
                                    ->options(\App\Models\Account::where('code', 'LIKE', '1-100%')->get()->pluck('name_with_code', 'id'))
                                    ->searchable(['code', 'name'])
                                    ->required(),
                                TextInput::make('notes')
                                    ->label('Referensi')
                                    ->placeholder('Referensi pembayaran...')
                                    ->columnSpanFull(),
                                \Filament\Forms\Components\FileUpload::make('attachments')
                                    ->label('Bukti Pembayaran')
                                    ->disk('public')
                                    ->directory('payment-proofs')
                                    ->visibility('public')
                                    ->multiple()
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
        return 'Detil Tagihan Pembelian ' . $this->record->number;
    }

    public function getHeading(): string
    {
        return 'Detil Tagihan Pembelian ' . $this->record->number;
    }

    public function addPayment(): void
    {
        $data = $this->paymentForm->getState();
        $record = $this->record;
        $amount = (float) $data['amount'];

        if ($amount <= 0) {
            \Filament\Notifications\Notification::make()
                ->title('Jumlah pembayaran harus lebih dari 0')
                ->danger()
                ->send();
            return;
        }

        // Find or create Debt
        $debt = \App\Models\Debt::firstOrCreate(
            ['reference' => $record->number],
            [
                'number' => 'DBT/' . $record->number,
                'supplier_id' => $record->supplier_id,
                'date' => $record->date,
                'due_date' => $record->due_date,
                'total_amount' => $record->total_amount,
                'status' => 'posted',
                'payment_status' => 'unpaid',
            ]
        );

        // Create Payment
        $debt->payments()->create([
            'number' => $data['number'] ?? $this->generateNextNumber(), // Use form data or fallback to next number
            'date' => $data['date'],
            'account_id' => $data['account_id'],
            'amount' => $amount,
            'notes' => $data['notes'] ?? null,
            'attachments' => $data['attachments'] ?? [],
        ]);

        // Update Invoice status and fields
        $totalPaid = $debt->payments()->sum('amount') + ($record->down_payment ?? 0);
        $totalToPay = (float) $record->total_amount - (float) ($record->withholding_amount ?? 0);
        $actualBalance = $totalToPay - $totalPaid;

        if ($actualBalance <= 0.01) {
            $record->update([
                'payment_status' => 'paid',
                'status' => 'paid',
                'balance_due' => 0
            ]);
        } elseif ($totalPaid > 0) {
            $record->update([
                'payment_status' => 'partial',
                'status' => 'posted',
                'balance_due' => max(0, $actualBalance)
            ]);
        }

        \Filament\Notifications\Notification::make()
            ->title('Pembayaran berhasil ditambahkan')
            ->success()
            ->send();

        $this->refreshFormData(['status', 'payment_status', 'balance_due']);

        // Reset form
        $this->paymentForm->fill([
            'amount' => $record->fresh()->balance_due,
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
}
