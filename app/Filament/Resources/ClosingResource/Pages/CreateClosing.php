<?php

namespace App\Filament\Resources\ClosingResource\Pages;

use App\Filament\Resources\ClosingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateClosing extends CreateRecord
{
    use \Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;

    protected static string $resource = ClosingResource::class;

    public bool $stockValid = false;
    public array $invalidProducts = [];

    public function getBreadcrumbs(): array
    {
        return [
            \App\Filament\Resources\AccountResource::getUrl() => 'Akun',
            'Tutup Buku',
        ];
    }

    public function validateStock(): void
    {
        $this->invalidProducts = \App\Models\Product::where('stock', '<', 0)->get()->toArray();
        $this->stockValid = empty($this->invalidProducts);
    }

    public float $totalRevenue = 0;
    public float $totalExpense = 0;
    public float $netIncome = 0;

    public function calculateWorksheet(): void
    {
        $startDate = $this->data['period_start'];
        $endDate = $this->data['period_end'];

        // Calculate Revenue (Credit - Debit for Income accounts)
        $this->totalRevenue = \App\Models\JournalItem::whereHas('journalEntry', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('transaction_date', [$startDate, $endDate]);
        })->whereHas('account', function ($query) {
            $query->where('category', 'Pendapatan');
        })->sum(\Illuminate\Support\Facades\DB::raw('credit - debit'));

        // Calculate Expense (Debit - Credit for Expense accounts)
        $this->totalExpense = \App\Models\JournalItem::whereHas('journalEntry', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('transaction_date', [$startDate, $endDate]);
        })->whereHas('account', function ($query) {
            $query->where('category', 'Beban');
        })->sum(\Illuminate\Support\Facades\DB::raw('debit - credit'));

        $this->netIncome = $this->totalRevenue - $this->totalExpense;
    }

    protected function getSteps(): array
    {
        return [
            \Filament\Schemas\Components\Wizard\Step::make('Pilih Periode')
                ->schema([
                    \Filament\Forms\Components\DatePicker::make('period_start')
                        ->label('Mulai Tanggal')
                        ->default(function () {
                            $lastClosing = \App\Models\Closing::latest('period_end')->first();
                            return $lastClosing ? \Carbon\Carbon::parse($lastClosing->period_end)->addDay()->format('Y-m-d') : \App\Models\JournalEntry::min('transaction_date') ?? now()->startOfYear()->format('Y-m-d');
                        })
                        ->readOnly()
                        ->required(),
                    \Filament\Forms\Components\DatePicker::make('period_end')
                        ->label('Sampai Tanggal')
                        ->required()
                        ->after('period_start'),
                ]),
            \Filament\Schemas\Components\Wizard\Step::make('Validasi Stok Produk')
                ->schema([
                    \Filament\Forms\Components\ViewField::make('stock_validation')
                        ->view('filament.forms.components.stock-validation')
                        ->viewData([
                            'livewire' => $this,
                        ])
                        ->label('Validasi Stok'),
                ])
                ->afterValidation(function () {
                    if (!$this->stockValid) {
                        \Filament\Notifications\Notification::make()
                            ->title('Gagal Validasi')
                            ->body('Terdapat produk dengan stok minus. Harap perbaiki sebelum melanjutkan.')
                            ->danger()
                            ->send();

                        throw new \Illuminate\Validation\ValidationException(\Illuminate\Support\Facades\Validator::make([], []));
                    }
                    $this->calculateWorksheet();
                }),
            \Filament\Schemas\Components\Wizard\Step::make('Set Kertas Kerja')
                ->schema([
                    \Filament\Forms\Components\ViewField::make('worksheet')
                        ->view('filament.forms.components.closing-worksheet')
                        ->viewData([
                            'livewire' => $this,
                        ])
                        ->label('Kertas Kerja Closing'),
                    \Filament\Forms\Components\Hidden::make('total_revenue'),
                    \Filament\Forms\Components\Hidden::make('total_expense'),
                    \Filament\Forms\Components\Hidden::make('net_income'),
                ]),
            \Filament\Schemas\Components\Wizard\Step::make('Selesai')
                ->schema([
                    \Filament\Forms\Components\Placeholder::make('finish')
                        ->label('Siap untuk menutup buku')
                        ->content('Klik tombol Submit untuk memproses tutup buku.'),
                ]),
        ];
    }
    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            // 1. Create Closing Record
            $closing = static::getModel()::create($data + [
                'closed_by' => auth()->id(),
            ]);

            // 2. Create Journal Entry for Closing
            $journalEntry = new \App\Models\JournalEntry();
            $journalEntry->transaction_date = $data['period_end'];
            $journalEntry->reference_number = 'CLOSE-' . \Carbon\Carbon::parse($data['period_end'])->format('Ymd');
            $journalEntry->description = 'Tutup Buku Periode ' . $data['period_start'] . ' s/d ' . $data['period_end'];
            $journalEntry->save();

            // 3. Get Account IDs
            $revenueAccounts = \App\Models\Account::where('category', 'Pendapatan')->get();
            $expenseAccounts = \App\Models\Account::where('category', 'Beban')->get();
            $retainedEarningsAccount = \App\Models\Account::where('name', 'like', '%Laba Ditahan%')->first();

            if (!$retainedEarningsAccount) {
                $retainedEarningsAccount = \App\Models\Account::firstOrCreate(
                    ['code' => '3000-RE', 'name' => 'Laba Ditahan'],
                    ['category' => 'Ekuitas', 'is_active' => true]
                );
            }

            // 4. Calculate and Zero out Revenue (Debit Income)
            $totalRevenue = 0;
            foreach ($revenueAccounts as $account) {
                $balance = \App\Models\JournalItem::where('account_id', $account->id)
                    ->whereHas('journalEntry', function ($q) use ($data) {
                        $q->whereBetween('transaction_date', [$data['period_start'], $data['period_end']]);
                    })
                    ->sum(\Illuminate\Support\Facades\DB::raw('credit - debit'));

                if ($balance != 0) {
                    $journalEntry->items()->create([
                        'account_id' => $account->id,
                        'debit' => $balance,
                        'credit' => 0,
                        'description' => 'Closing Entry - Revenue',
                    ]);
                    $totalRevenue += $balance;
                }
            }

            // 5. Calculate and Zero out Expenses (Credit Expenses)
            $totalExpense = 0;
            foreach ($expenseAccounts as $account) {
                $balance = \App\Models\JournalItem::where('account_id', $account->id)
                    ->whereHas('journalEntry', function ($q) use ($data) {
                        $q->whereBetween('transaction_date', [$data['period_start'], $data['period_end']]);
                    })
                    ->sum(\Illuminate\Support\Facades\DB::raw('debit - credit'));

                if ($balance != 0) {
                    $journalEntry->items()->create([
                        'account_id' => $account->id,
                        'debit' => 0,
                        'credit' => $balance,
                        'description' => 'Closing Entry - Expense',
                    ]);
                    $totalExpense += $balance;
                }
            }

            // 6. Post Net Income/Loss to Retained Earnings
            $netIncome = $totalRevenue - $totalExpense;
            if ($netIncome > 0) {
                $journalEntry->items()->create([
                    'account_id' => $retainedEarningsAccount->id,
                    'debit' => 0,
                    'credit' => $netIncome,
                    'description' => 'Net Income to Retained Earnings',
                ]);
            } elseif ($netIncome < 0) {
                $journalEntry->items()->create([
                    'account_id' => $retainedEarningsAccount->id,
                    'debit' => abs($netIncome),
                    'credit' => 0,
                    'description' => 'Net Loss to Retained Earnings',
                ]);
            }

            return $closing;
        });
    }
}
