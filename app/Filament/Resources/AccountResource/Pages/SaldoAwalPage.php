<?php

namespace App\Filament\Resources\AccountResource\Pages;

use App\Filament\Resources\AccountResource;
use App\Models\Account;
use Filament\Resources\Pages\Page;
use Filament\Actions\Action;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\Filter;
use Filament\Support\Enums\Alignment;
use Filament\Notifications\Notification;

class SaldoAwalPage extends Page implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static string $resource = AccountResource::class;

    protected string $view = 'filament.pages.saldo-awal-page';

    protected static ?string $title = 'Saldo Awal';

    public array $balances = [];

    public function mount(): void
    {
        // Load existing balances
        $accounts = Account::all();
        foreach ($accounts as $account) {
            $this->balances[$account->id]['debit'] = $account->current_balance >= 0 ? $account->current_balance : 0;
            $this->balances[$account->id]['credit'] = $account->current_balance < 0 ? abs($account->current_balance) : 0;
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Account::query())
            ->columns([
                TextColumn::make('account_info')
                    ->label('Akun')
                    ->getStateUsing(fn(Account $record) => $record->code . ' - ' . $record->name)
                    ->description(fn(Account $record) => $record->category)
                    ->searchable(['code', 'name'])
                    ->sortable(['code']),

                ViewColumn::make('debit')
                    ->label('Debit')
                    ->view('filament.tables.columns.saldo-input'),

                ViewColumn::make('credit')
                    ->label('Kredit')
                    ->view('filament.tables.columns.saldo-input'),
            ])
            ->filters([
                Filter::make('show_zero')
                    ->label('Tampilkan balance yang nol')
                    ->toggle()
                    ->query(fn($query) => $query), // Placeholder logic as actual balance check depends on implementation
            ])
            ->actions([])
            ->bulkActions([])
            ->paginated([50, 100, 'all']);
    }

    public function save()
    {
        // Save logic here using $this->balances
        foreach ($this->balances as $id => $data) {
            // Process $data['debit'] and $data['credit']
        }

        Notification::make()
            ->title('Saldo awal berhasil disimpan')
            ->success()
            ->send();
    }

    public $startDate = '2023-01-01';
    public $endDate = '2023-12-31';

    // ... existing mount ...

    protected function getHeaderActions(): array
    {
        return [
            Action::make('panduan')
                ->label('Panduan')
                ->icon('heroicon-o-book-open')
                ->color('gray')
                ->outlined(),
            Action::make('tanggal_konversi')
                ->label(fn() => \Carbon\Carbon::parse($this->startDate)->format('d/m/Y') . ' - ' . \Carbon\Carbon::parse($this->endDate)->format('d/m/Y'))
                ->icon('heroicon-o-calendar')
                ->color('gray')
                ->outlined()
                ->form([
                    \Filament\Forms\Components\DatePicker::make('start_date')
                        ->label('Dari Tanggal')
                        ->default(fn() => $this->startDate)
                        ->required(),
                    \Filament\Forms\Components\DatePicker::make('end_date')
                        ->label('Sampai Tanggal')
                        ->default(fn() => $this->endDate)
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->startDate = $data['start_date'];
                    $this->endDate = $data['end_date'];
                }),
            Action::make('saldo_pembanding')
                ->label('Saldo Pembanding')
                ->icon('heroicon-o-plus')
                ->color('primary'),
            Action::make('import')
                ->label('Import')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->outlined(),
            Action::make('print')
                ->label('Print')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->outlined(),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            \App\Filament\Resources\AccountResource::getUrl() => 'Akun',
            'Saldo Awal',
        ];
    }

    public function getSubheading(): \Illuminate\Contracts\Support\Htmlable|string|null
    {
        return 'Konfirmasi saldo akun per ' . \Carbon\Carbon::parse($this->endDate)->format('d/m/Y');
    }
}
