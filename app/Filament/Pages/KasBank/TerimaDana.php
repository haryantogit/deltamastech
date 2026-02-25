<?php

namespace App\Filament\Pages\KasBank;

use App\Models\Account;
use App\Models\JournalItem;
use App\Models\Contact;
use App\Models\Tag;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Forms;
use Livewire\Attributes\Url;
use Illuminate\Support\Facades\DB;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class TerimaDana extends Page implements HasForms
{
    use InteractsWithForms;



    protected string $view = 'filament.pages.kas-bank.terima-dana';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'kas-bank/{record}/terima';

    #[Url]
    public ?string $record = null;

    public $account;

    // Form Data
    public ?array $data = [];

    public function mount(): void
    {
        $this->account = \App\Models\Account::findOrFail($this->record);

        // Generate Auto Number BANK/00001
        $lastTransaction = \App\Models\JournalEntry::where('reference_number', 'like', 'BANK/%')
            ->orderBy('id', 'desc')
            ->first();

        $number = 1;
        if ($lastTransaction) {
            $parts = explode('/', $lastTransaction->reference_number);
            if (isset($parts[1]) && is_numeric($parts[1])) {
                $number = (int) $parts[1] + 1;
            }
        }
        $transNo = 'BANK/' . str_pad($number, 5, '0', STR_PAD_LEFT);

        $this->form->fill([
            'transaction_date' => now()->format('Y-m-d'),
            'trans_no' => $transNo,
            'items' => [
                [
                    'account_id' => null,
                    'desc' => null,
                    'tax_id' => null,
                    'amount' => 0,
                ]
            ],
            'sub_total' => 0,
            'withholding_amount' => 0,
            'total_amount' => 0,
        ]);
    }

    public function getTitle(): string
    {
        return 'Terima Dana';
    }

    public static function getNavigationParentItem(): ?string
    {
        return 'Kas & Bank';
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            url('/admin/kas-bank') => 'Kas & Bank',
            url('/admin/kas-bank/detail/' . $this->record) => $this->account->name,
            'Terima Dana',
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->components([
                        Group::make()
                            ->components([
                                Forms\Components\Select::make('contact_id')
                                    ->label('Dari')
                                    ->options(Contact::all()->pluck('name', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')->required(),
                                        Forms\Components\TextInput::make('company'),
                                    ])
                                    ->createOptionUsing(function (array $data) {
                                        return Contact::create($data)->id;
                                    }),
                                Forms\Components\DatePicker::make('transaction_date')
                                    ->label('Tanggal Transaksi')
                                    ->required(),
                                Forms\Components\Select::make('tags')
                                    ->label('Tag')
                                    ->options(Tag::all()->pluck('name', 'id'))
                                    ->multiple()
                                    ->preload(),
                            ])->columnSpan(1),
                        Group::make()
                            ->components([
                                Forms\Components\TextInput::make('trans_no')
                                    ->label('Nomor')
                                    ->required(),
                                Forms\Components\TextInput::make('reference')
                                    ->label('Referensi'),
                            ])->columnSpan(1),
                    ])->columns(2),

                Section::make()
                    ->components([
                        Forms\Components\Repeater::make('items')
                            ->label('Akun')
                            ->schema([
                                Forms\Components\Select::make('account_id')
                                    ->label('Akun')
                                    ->options(Account::where('category', '!=', 'Kas & Bank')
                                        ->get()
                                        ->mapWithKeys(fn($account) => [$account->id => "{$account->code} - {$account->name}"]))
                                    ->required()
                                    ->searchable()
                                    ->columnSpan(4),
                                Forms\Components\TextInput::make('desc')
                                    ->label('Deskripsi')
                                    ->columnSpan(4),
                                Forms\Components\Select::make('tax_id')
                                    ->label('Pajak')
                                    ->options([])
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('amount')
                                    ->label('Total')
                                    ->numeric()
                                    ->default(0)
                                    ->required()
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(fn(Get $get, Set $set) => self::updateTotals($get, $set))
                                    ->columnSpan(2),
                            ])
                            ->columns(12)
                            ->defaultItems(1)
                            ->addActionLabel('Tambah baris')
                    ]), // End of Section Item Tagihan

                Grid::make(2)
                    ->components([
                        Group::make()
                            ->components([
                                Forms\Components\Textarea::make('memo')
                                    ->label('Pesan')
                                    ->rows(3),
                                Forms\Components\FileUpload::make('attachment')
                                    ->label('Lampiran')
                                    ->columnSpanFull(),
                            ])->columnSpan(1),

                        Group::make()
                            ->components([
                                Forms\Components\TextInput::make('sub_total')
                                    ->label('Sub Total')
                                    ->numeric()
                                    ->readOnly()
                                    ->prefix('Rp')
                                    ->extraInputAttributes(['class' => 'text-right']),

                                Forms\Components\Toggle::make('has_withholding')
                                    ->label('Pemotongan')
                                    ->inline()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                        if (!$state)
                                            $set('withholding_amount', 0);
                                        self::updateTotals($get, $set);
                                    }),

                                Group::make()
                                    ->components([
                                        Forms\Components\Select::make('withholding_account_id')
                                            ->label('Akun Pemotongan')
                                            ->options(Account::where('category', '!=', 'Kas & Bank')
                                                ->get()
                                                ->mapWithKeys(fn($account) => [$account->id => "{$account->code} - {$account->name}"]))
                                            ->searchable()
                                            ->required(),
                                        Forms\Components\TextInput::make('withholding_amount')
                                            ->label('Nominal')
                                            ->numeric()
                                            ->default(0)
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(fn(Get $get, Set $set) => self::updateTotals($get, $set))
                                            ->prefix('Rp'),
                                    ])
                                    ->columns(2)
                                    ->visible(fn(Get $get) => $get('has_withholding')),

                                Forms\Components\TextInput::make('total_amount')
                                    ->label('Total Diterima')
                                    ->numeric()
                                    ->readOnly()
                                    ->prefix('Rp')
                                    ->extraInputAttributes(['class' => 'text-right font-bold', 'style' => 'font-size: 1.125rem;']),
                            ])
                            ->columnSpan(1),
                    ])->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public static function updateTotals(Get $get, Set $set): void
    {
        $items = $get('items') ?? [];
        $subTotal = 0;

        foreach ($items as $item) {
            $subTotal += (float) ($item['amount'] ?? 0);
        }

        $withholdingAmount = (float) $get('withholding_amount');
        $total = $subTotal - $withholdingAmount;

        $set('sub_total', $subTotal);
        $set('total_amount', $total);
    }

    public function createAnother(): void
    {
        $this->create(true);
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('create')
                ->label('Buat')
                ->submit('create')
                ->color('primary'),
            \Filament\Actions\Action::make('createAnother')
                ->label('Buat & buat lainnya')
                ->action('createAnother')
                ->color('gray'),
            \Filament\Actions\Action::make('cancel')
                ->label('Batal')
                ->url(fn() => '/admin/kas-bank/detail/' . $this->record)
                ->color('gray'),
        ];
    }

    public function create($createAnother = false): void
    {
        $data = $this->form->getState();

        DB::transaction(function () use ($data) {
            // 1. Create Journal Entry
            $entry = \App\Models\JournalEntry::create([
                'transaction_date' => $data['transaction_date'],
                'reference_number' => $data['trans_no'],
                'description' => 'Terima Dana from ' . Contact::find($data['contact_id'])->name,
                'total_amount' => $data['total_amount'] ?? 0,
                'memo' => $data['memo'] ?? null,
                // removed contact_id as it is likely not in fillable or handled by relation
            ]);

            // Save Tags - Fix: Ensure tags are synced
            if (!empty($data['tags'])) {
                $entry->tags()->sync($data['tags']);
            }

            $totalCredit = 0;

            // 2. Create Credit Items (Income/Liability) from Repeater
            foreach ($data['items'] as $item) {
                $amount = (float) $item['amount'];
                $totalCredit += $amount;

                JournalItem::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $item['account_id'],
                    'credit' => $amount,
                    'debit' => 0,
                    'desc' => $item['desc'] ?? null,
                ]);
            }

            // 3. Handle Withholding (Debit)
            // For Income (Terima Dana), Withholding is an expense/prepaid tax, so it's a DEBIT.
            // It reduces the amount we receive in the Bank.
            $withholdingAmount = (float) ($data['withholding_amount'] ?? 0);
            if ($data['has_withholding'] && $withholdingAmount > 0 && !empty($data['withholding_account_id'])) {
                JournalItem::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $data['withholding_account_id'],
                    'debit' => $withholdingAmount,
                    'credit' => 0,
                    'desc' => 'Pemotongan / Withholding',
                ]);
            }

            // 4. Create Debit Item (Kas/Bank) - The destination account
            // Net received = Total Credit - Withholding
            $netReceived = $totalCredit - $withholdingAmount;

            JournalItem::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $this->account->id,
                'debit' => $netReceived,
                'credit' => 0,
                'desc' => 'Payment to ' . $this->account->name,
            ]);
        });

        Notification::make()
            ->title('Transaksi Berhasil Disimpan')
            ->success()
            ->send();

        if ($createAnother) {
            $this->redirect(request()->header('Referer'));
        } else {
            $this->redirect('/admin/kas-bank/detail/' . $this->account->id);
        }
    }
}
