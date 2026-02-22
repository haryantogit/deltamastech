<?php

namespace App\Filament\Resources\AccountResource\Pages;

use App\Filament\Resources\AccountResource;
use App\Models\JournalItem;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;

class ViewAccount extends ViewRecord
{
    protected static string $resource = AccountResource::class;

    protected string $view = 'filament.resources.account-resource.pages.view-account';

    public $startBalance = 0;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\AccountBalanceChart::class,
        ];
    }

    public function getViewData(): array
    {
        $query = JournalItem::query()
            ->where('account_id', $this->record->id)
            ->with(['journalEntry'])
            ->orderBy('created_at', 'asc');

        $transactions = $query->paginate(50);

        // Sum for summary cards (Current view context)
        $totalDebit = (float) JournalItem::where('account_id', $this->record->id)->sum('debit');
        $totalCredit = (float) JournalItem::where('account_id', $this->record->id)->sum('credit');

        $isDebitNormal = in_array($this->record->category, [
            'Kas & Bank',
            'Akun Piutang',
            'Persediaan',
            'Aktiva Lancar Lainnya',
            'Aktiva Tetap',
            'Depresiasi & Amortisasi',
            'Aktiva Lainnya',
            'Harga Pokok Penjualan',
            'Beban',
            'Beban Lainnya'
        ]);

        $net = $isDebitNormal ? ($totalDebit - $totalCredit) : ($totalCredit - $totalDebit);

        return [
            'transactions' => $transactions,
            'record' => $this->record,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'net' => $net,
            'is_debit_normal' => $isDebitNormal,
        ];
    }
}
