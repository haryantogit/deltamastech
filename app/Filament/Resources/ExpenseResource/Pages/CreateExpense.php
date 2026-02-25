<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use App\Filament\Resources\ExpenseResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use Illuminate\Support\Facades\DB;

class CreateExpense extends CreateRecord
{
    protected static string $resource = ExpenseResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            $this->getResource()::getUrl('index') => 'Biaya',
            '' => 'Buat Biaya',
        ];
    }

    protected function afterCreate(): void
    {
        $expense = $this->record;

        DB::transaction(function () use ($expense) {
            // Create Journal Entry
            $entry = JournalEntry::create([
                'transaction_date' => $expense->transaction_date,
                'reference_number' => $expense->reference_number,
                'description' => 'Biaya: ' . ($expense->contact ? $expense->contact->name : 'General'),
                'total_amount' => $expense->total_amount,
            ]);

            // Credit the Source Account (The one we paid from)
            JournalItem::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $expense->account_id,
                'credit' => $expense->total_amount,
                'debit' => 0,
            ]);

            // Debit the Expense Accounts
            foreach ($expense->items as $item) {
                JournalItem::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $item->account_id,
                    'debit' => $item->amount,
                    'credit' => 0,
                ]);
            }
        });
    }

}
