<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DebtPayment;
use App\Models\ReceivablePayment;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\Account;

class BackfillJournalEntries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'journal:backfill';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill journal entries for existing payments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting backfill for Debt Payments...');
        $debtPayments = DebtPayment::all();
        $bar = $this->output->createProgressBar(count($debtPayments));

        foreach ($debtPayments as $payment) {
            $this->createDebtPaymentJournal($payment);
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();

        $this->info('Starting backfill for Receivable Payments...');
        $receivablePayments = ReceivablePayment::all();
        $bar = $this->output->createProgressBar(count($receivablePayments));

        foreach ($receivablePayments as $payment) {
            $this->createReceivablePaymentJournal($payment);
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();

        $this->info('Backfill completed.');
    }

    protected function createDebtPaymentJournal(DebtPayment $payment)
    {
        // Skip if exists
        if (JournalEntry::where('reference_number', $payment->number)->exists()) {
            return;
        }

        // Create Journal Entry
        $journalEntry = JournalEntry::create([
            'transaction_date' => $payment->date,
            'reference_number' => $payment->number,
            'description' => 'Purchase Payment ' . $payment->number,
            'total_amount' => $payment->amount,
        ]);

        // 1. DEBIT: Accounts Payable (Hutang Usaha)
        $apAccount = Account::where('code', '2-10001')->first() ?? Account::where('name', 'like', '%Hutang%')->first();

        if ($apAccount) {
            JournalItem::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $apAccount->id,
                'debit' => $payment->amount,
                'credit' => 0,
            ]);
        }

        // 2. CREDIT: Payment Account (Cash/Bank)
        if ($payment->account_id) {
            JournalItem::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $payment->account_id,
                'debit' => 0,
                'credit' => $payment->amount,
            ]);
        }
    }

    protected function createReceivablePaymentJournal(ReceivablePayment $payment)
    {
        // Skip if exists
        if (JournalEntry::where('reference_number', $payment->number)->exists()) {
            return;
        }

        // Create Journal Entry
        $journalEntry = JournalEntry::create([
            'transaction_date' => $payment->date,
            'reference_number' => $payment->number,
            'description' => 'Sales Payment ' . $payment->number,
            'total_amount' => $payment->amount,
        ]);

        // 1. DEBIT: Payment Account (Cash/Bank)
        if ($payment->account_id) {
            JournalItem::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $payment->account_id,
                'debit' => $payment->amount,
                'credit' => 0,
            ]);
        }

        // 2. CREDIT: Accounts Receivable (Piutang Usaha)
        $arAccount = Account::where('code', '1-10002')->first() ?? Account::where('name', 'like', '%Piutang%')->first();

        if ($arAccount) {
            JournalItem::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $arAccount->id,
                'debit' => 0,
                'credit' => $payment->amount,
            ]);
        }
    }
}
