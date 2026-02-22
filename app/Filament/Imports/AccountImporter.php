<?php

namespace App\Filament\Imports;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\DB;

class AccountImporter extends Importer
{
    protected static ?string $model = Account::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->label('*Nama Akun')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('code')
                ->label('*Kode Akun')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('category')
                ->label('*Kategori')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('description')
                ->label('Deskripsi')
                ->rules(['nullable']),
            ImportColumn::make('current_balance')
                ->label('Saldo')
                ->numeric()
                ->rules(['numeric']),
        ];
    }

    public function resolveRecord(): ?Account
    {
        $record = Account::firstOrNew([
            'code' => $this->data['*Kode Akun'] ?? $this->data['code'],
        ]);

        $record->category = $this->data['*Kategori'] ?? $this->data['category'];

        return $record;
    }

    protected function afterSave(): void
    {
        $account = $this->record;
        $openingBalance = (float) ($this->data['Saldo'] ?? $this->data['current_balance'] ?? 0);

        if ($openingBalance != 0 && $account->code !== '3-30999') {
            DB::transaction(function () use ($account, $openingBalance) {
                // Find or create the Opening Balance Equity account
                $equityAccount = Account::firstOrCreate(
                    ['code' => '3-30999'],
                    [
                        'name' => 'Ekuitas Saldo Awal',
                        'category' => 'Equity',
                        'is_active' => true,
                    ]
                );

                $journalEntry = JournalEntry::create([
                    'transaction_date' => now(),
                    'reference_number' => 'OB-' . $account->code,
                    'description' => 'Saldo Awal: ' . $account->name,
                    'total_amount' => abs($openingBalance),
                ]);

                // Determine debit/credit based on account category and balance sign
                // Normal Balance: 
                // Asset/Expense: Debit (+), Credit (-)
                // Liability/Equity/Income: Credit (+), Debit (-)

                $isDebitNormal = in_array($account->category, ['Asset', 'Expense']);

                if ($openingBalance > 0) {
                    $debitAccountId = $isDebitNormal ? $account->id : $equityAccount->id;
                    $creditAccountId = $isDebitNormal ? $equityAccount->id : $account->id;
                } else {
                    $debitAccountId = $isDebitNormal ? $equityAccount->id : $account->id;
                    $creditAccountId = $isDebitNormal ? $account->id : $equityAccount->id;
                }

                JournalItem::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $debitAccountId,
                    'debit' => abs($openingBalance),
                    'credit' => 0,
                ]);

                JournalItem::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $creditAccountId,
                    'debit' => 0,
                    'credit' => abs($openingBalance),
                ]);
            });
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Impor akun telah selesai dan ' . number_format($import->successful_rows) . ' ' . str('baris')->plural($import->successful_rows) . ' berhasil diimpor.';

        if ($failedRowsCount = $import->failed_rows) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('baris')->plural($failedRowsCount) . ' gagal diimpor.';
        }

        return $body;
    }
}
