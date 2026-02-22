<?php

namespace App\Filament\Resources\FixedAssetResource\Pages;

use App\Filament\Resources\FixedAssetResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewFixedAsset extends ViewRecord
{
    protected static string $resource = FixedAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('post_depreciation')
                ->label('Post Penyusutan')
                ->icon('heroicon-m-banknotes')
                ->color('success')
                ->visible(fn() => $this->record->status === 'registered')
                ->form([
                    \Filament\Forms\Components\DatePicker::make('date')
                        ->label('Bulan Penyusutan')
                        ->native(false)
                        ->displayFormat('F Y')
                        ->format('Y-m')
                        ->required()
                        ->default(now()),
                ])
                ->action(function (array $data) {
                    $record = $this->record;
                    $period = $data['date'];

                    if ($record->fixedAssetDepreciations()->where('period', $period)->exists()) {
                        \Filament\Notifications\Notification::make()
                            ->title('Gagal')
                            ->body("Penyusutan untuk periode {$period} sudah diposting.")
                            ->danger()
                            ->send();
                        return;
                    }

                    if (!$record->has_depreciation) {
                        \Filament\Notifications\Notification::make()
                            ->title('Gagal')
                            ->body("Penyusutan belum diaktifkan untuk aset ini.")
                            ->danger()
                            ->send();
                        return;
                    }

                    $purchasePrice = $record->purchase_price ?? 0;
                    $salvageValue = $record->salvage_value ?? 0;
                    $usefulLifeYears = $record->useful_life_years ?? 0;
                    $usefulLifeMonths = $record->useful_life_months ?? 0;
                    $totalMonths = ($usefulLifeYears * 12) + $usefulLifeMonths;
                    $rate = $record->depreciation_rate ?? 0;
                    $method = $record->depreciation_method ?? 'straight_line';

                    $amount = 0;
                    if ($totalMonths > 0) {
                        // We need to calculate what the accumulated value should be at the end of this period
                        $startDate = \Carbon\Carbon::parse($record->depreciation_start_date)->startOfMonth();
                        $targetDate = \Carbon\Carbon::parse($period)->startOfMonth();

                        if ($targetDate->lt($startDate)) {
                            \Filament\Notifications\Notification::make()
                                ->title('Gagal')
                                ->body("Periode {$period} mendahului tanggal mulai penyusutan.")
                                ->danger()
                                ->send();
                            return;
                        }

                        $elapsedMonths = $startDate->diffInMonths($targetDate) + 1;

                        if ($elapsedMonths > $totalMonths) {
                            \Filament\Notifications\Notification::make()
                                ->title('Gagal')
                                ->body("Aset sudah habis masa manfaatnya.")
                                ->danger()
                                ->send();
                            return;
                        }

                        $currentAccumRaw = ($purchasePrice - $salvageValue) * ($elapsedMonths / $totalMonths);
                        $prevAccumRaw = ($purchasePrice - $salvageValue) * (($elapsedMonths - 1) / $totalMonths);

                        $amount = round($currentAccumRaw) - round($prevAccumRaw);
                    } elseif ($rate > 0) {
                        $amount = round(($purchasePrice * ($rate / 100)) / 12);
                    }

                    if ($amount <= 0) {
                        \Filament\Notifications\Notification::make()
                            ->title('Gagal')
                            ->body("Nilai penyusutan tidak valid (0).")
                            ->danger()
                            ->send();
                        return;
                    }

                    $journal = \App\Models\JournalEntry::create([
                        'transaction_date' => \Carbon\Carbon::parse($period)->endOfMonth(),
                        'reference_number' => "DEPR/{$record->sku}/" . str_replace('-', '', $period),
                        'description' => "Penyusutan {$record->name} periode {$period}",
                        'total_amount' => $amount,
                    ]);

                    \App\Models\JournalItem::create([
                        'journal_entry_id' => $journal->id,
                        'account_id' => $record->depreciation_expense_account_id,
                        'debit' => $amount,
                        'credit' => 0,
                    ]);

                    \App\Models\JournalItem::create([
                        'journal_entry_id' => $journal->id,
                        'account_id' => $record->accumulated_depreciation_account_id,
                        'debit' => 0,
                        'credit' => $amount,
                    ]);

                    $record->fixedAssetDepreciations()->create([
                        'journal_entry_id' => $journal->id,
                        'period' => $period,
                        'amount' => $amount,
                    ]);

                    $record->increment('accumulated_depreciation_value', $amount);

                    \Filament\Notifications\Notification::make()
                        ->title('Berhasil')
                        ->body("Penyusutan periode {$period} senilai " . number_format($amount, 0, ',', '.') . " berhasil diposting.")
                        ->success()
                        ->send();
                }),
            Actions\Action::make('dispose')
                ->label('Lepas/Jual')
                ->icon('heroicon-m-banknotes')
                ->color('danger')
                ->visible(fn() => $this->record->status === 'registered')
                ->url(fn() => FixedAssetResource::getUrl('dispose', ['record' => $this->record])),
        ];
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);

        if ($this->record->status === 'draft') {
            redirect()->to(FixedAssetResource::getUrl('edit', ['record' => $this->record]));
        }
    }
}
