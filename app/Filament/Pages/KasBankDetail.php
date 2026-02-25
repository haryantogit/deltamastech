<?php

namespace App\Filament\Pages;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use Filament\Forms\Components\DatePicker;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\FileUpload;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Filament\Schemas\Components\Tabs\Tab;

class KasBankDetail extends Page implements HasTable
{
    use InteractsWithTable;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'kas-bank/detail/{record}';

    protected string $view = 'filament.pages.kas-bank-detail';

    public $record = null;
    public $account;

    #[Url]
    public ?string $activeTab = 'all';

    public bool $showStats = false;

    public function toggleStats(): void
    {
        $this->showStats = !$this->showStats;
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 4;
    }

    public function mount($record): void
    {
        $this->record = $record;
        $this->account = Account::findOrFail($record);
    }

    public function getTitle(): string
    {
        return $this->account->name ?? 'Kas & Bank Detail';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\AccountStats::class,
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            '/admin/kas-bank' => 'Kas & Bank',
            $this->account->name ?? 'Detail',
        ];
    }

    protected function getHeaderWidgetsData(): array
    {
        return [
            'account' => $this->account,
            'filters' => [],
        ];
    }

    public function getTabs(): array
    {
        $baseQuery = JournalItem::where('account_id', $this->account->id)
            ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id');

        return [
            'all' => Tab::make('Transaksi di Kledo')
                ->badge((clone $baseQuery)->count()),
            'bank' => Tab::make('Transaksi di Bank')
                ->badge((clone $baseQuery)->where('journal_entries.is_bank_transaction', true)->count())
                ->modifyQueryUsing(fn($query) => $query->whereHas('journalEntry', fn($q) => $q->where('is_bank_transaction', true))),
            'reconciliation' => Tab::make('Rekonsiliasi')
                ->badge((clone $baseQuery)->where('journal_entries.is_reconciled', true)->count())
                ->modifyQueryUsing(fn($query) => $query->whereHas('journalEntry', fn($q) => $q->where('is_reconciled', true))),
            'recurring' => Tab::make('Transaksi Berulang')
                ->badge((clone $baseQuery)->where('journal_entries.is_recurring', true)->count())
                ->modifyQueryUsing(fn($query) => $query->whereHas('journalEntry', fn($q) => $q->where('is_recurring', true))),
            'void' => Tab::make('Void')
                ->badge((clone $baseQuery)->where('journal_entries.description', 'like', '%Void%')->count())
                ->modifyQueryUsing(fn($query) => $query->whereHas('journalEntry', fn($q) => $q->where('description', 'like', '%Void%'))),
            'pending' => Tab::make('Menunggu Persetujuan')
                ->badge((clone $baseQuery)->where('journal_entries.status', 'pending')->count())
                ->modifyQueryUsing(fn($query) => $query->whereHas('journalEntry', fn($q) => $q->where('status', 'pending'))),
            'rejected' => Tab::make('Ditolak')
                ->badge((clone $baseQuery)->where('journal_entries.status', 'rejected')->count())
                ->modifyQueryUsing(fn($query) => $query->whereHas('journalEntry', fn($q) => $q->where('status', 'rejected'))),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn() =>
                JournalItem::query()
                    ->where('account_id', $this->account->id)
                    ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
                    ->select('journal_items.*')
                    ->with(['journalEntry', 'account'])
                    ->orderBy('journal_entries.transaction_date', 'desc')
                    ->orderBy('journal_entries.id', 'desc')
            )
            ->columns([
                TextColumn::make('journalEntry.transaction_date')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color('gray'),
                TextColumn::make('journalEntry.description')
                    ->label('Deskripsi')
                    ->searchable()
                    ->wrap()
                    ->html()
                    ->formatStateUsing(function (JournalItem $record) {
                        $entry = $record->journalEntry;
                        $ref = $entry->reference_number;
                        $desc = $entry->description;

                        $type = '';
                        $contactName = '';

                        if (str_starts_with($ref, 'EXP/')) {
                            $type = 'Pembayaran biaya';
                            $model = \App\Models\Expense::where('reference_number', $ref)->with('contact')->first();
                            $contactName = $model?->contact?->name;
                        } elseif (str_starts_with($ref, 'DM/')) {
                            $type = 'Penjualan';
                            $model = \App\Models\Receivable::where('invoice_number', $ref)->with('contact')->first();
                            $contactName = $model?->contact?->name;
                        } elseif (str_starts_with($ref, 'CM/')) {
                            $type = 'Pembelian';
                            $model = \App\Models\Debt::where('number', $ref)->with('supplier')->first();
                            $contactName = $model?->supplier?->name;
                        } elseif (str_starts_with($ref, 'TR/')) {
                            $type = 'Transfer';
                        }

                        // Fallback contact from description if not found in model
                        if (!$contactName && str_contains($desc, ':')) {
                            $contactName = trim(explode(':', $desc)[1]);
                        }

                        $action = '';
                        if ($record->debit > 0) {
                            $action = $type ? "$type dari" : "Terima dari";
                        } else {
                            $action = $type ? ($type == 'Pembayaran biaya' ? 'Pembayaran biaya' : "$type ke") : "Kirim ke";
                        }

                        // Special case for transfers
                        if ($type == 'Transfer') {
                            $action = $record->debit > 0 ? 'Terima transfer dari' : 'Transfer ke';
                        }

                        $displayText = $action;
                        if ($contactName) {
                            $displayText .= ": " . $contactName;
                        }

                        return '<div class="text-primary-600 font-medium">' . $displayText . '</div>' .
                            '<div class="text-xs text-gray-500 font-mono">' . ($ref ?: '-') . '</div>';
                    })
                    ->url(fn(JournalItem $record): string => url("/admin/kas-bank/transaction/{$record->journal_entry_id}/detail")),
                TextColumn::make('referensi_custom')
                    ->label('Referensi')
                    ->placeholder('-')
                    ->getStateUsing(function (JournalItem $record) {
                        $entry = $record->journalEntry;
                        $ref = $entry->reference_number;

                        if (str_starts_with($ref, 'EXP/')) {
                            return \App\Models\Expense::where('reference_number', $ref)->value('memo') ?? $entry->memo;
                        }
                        if (str_starts_with($ref, 'CM/')) {
                            return \App\Models\Debt::where('number', $ref)->value('reference') ?? $entry->memo;
                        }
                        if (str_starts_with($ref, 'DM/')) {
                            return \App\Models\Receivable::where('invoice_number', $ref)->value('reference') ?? $entry->memo;
                        }
                        return $entry->memo;
                    })
                    ->url(fn(JournalItem $record): string => url("/admin/kas-bank/transaction/{$record->journal_entry_id}/detail")),
                TextColumn::make('journalEntry.tags.name')
                    ->label('Tag')
                    ->badge()
                    ->color('info')
                    ->placeholder('-'),
                TextColumn::make('debit')
                    ->label('Terima')
                    ->money('IDR')
                    ->alignEnd()
                    ->placeholder('-')
                    ->summarize([
                        \Filament\Tables\Columns\Summarizers\Sum::make()
                            ->label('Total')
                            ->money('IDR'),
                    ]),
                TextColumn::make('credit')
                    ->label('Kirim')
                    ->money('IDR')
                    ->color('danger')
                    ->alignEnd()
                    ->placeholder('-')
                    ->summarize([
                        \Filament\Tables\Columns\Summarizers\Sum::make()
                            ->label('Total')
                            ->money('IDR'),
                    ]),
                TextColumn::make('balance')
                    ->label('Saldo')
                    ->money('IDR')
                    ->getStateUsing(function (JournalItem $record) {
                        return JournalItem::where('account_id', $record->account_id)
                            ->where('journal_entry_id', '<=', $record->journal_entry_id)
                            ->selectRaw('SUM(debit - credit) as balance')
                            ->value('balance');
                    })
                    ->alignEnd()
                    ->color('success'),
            ])
            ->filters([
                Filter::make('transaction_date')
                    ->form([
                        DatePicker::make('start_date')
                            ->label('Dari Tanggal'),
                        DatePicker::make('end_date')
                            ->label('Sampai Tanggal'),
                    ])
                    ->columns(2)
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_date'],
                                fn(Builder $query, $date): Builder => $query->whereHas('journalEntry', fn($q) => $q->whereDate('transaction_date', '>=', $date)),
                            )
                            ->when(
                                $data['end_date'],
                                fn(Builder $query, $date): Builder => $query->whereHas('journalEntry', fn($q) => $q->whereDate('transaction_date', '<=', $date)),
                            );
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('edit')
                        ->label('Edit')
                        ->icon('heroicon-o-pencil')
                        ->url(fn(JournalItem $record): string => url("/admin/kas-bank/transaction/{$record->journal_entry_id}/edit")),
                    Action::make('delete')
                        ->label('Hapus')
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->action(fn(JournalItem $record) => $record->journalEntry->delete()),
                ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kembali')
                ->label('Kembali')
                ->color('gray')
                ->outlined()
                ->size('sm')
                ->icon('heroicon-o-arrow-left')
                ->url('/admin/kas-bank'),
            Action::make('toggleStats')
                ->label(fn() => $this->showStats ? 'Sembunyikan Statistik' : 'Tampilkan Statistik')
                ->color('gray')
                ->outlined()
                ->size('sm')
                ->icon(fn() => $this->showStats ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                ->action(fn() => $this->toggleStats()),
            ActionGroup::make([
                Action::make('transfer')
                    ->label('Transfer Dana')
                    ->icon('heroicon-m-arrows-right-left')
                    ->modalWidth('2xl')
                    ->form([
                        \Filament\Schemas\Components\Grid::make(6)
                            ->schema([
                                Select::make('from_account_id')
                                    ->label('Dari')
                                    ->options(\App\Models\Account::whereIn('category', ['Kas & Bank', 'kas', 'bank'])->get()->mapWithKeys(fn($a) => [$a->id => "{$a->code} - {$a->name}"]))
                                    ->searchable()
                                    ->required()
                                    ->default($this->account->id)
                                    ->columnSpan(6),

                                Select::make('to_account_id')
                                    ->label('Ke')
                                    ->options(\App\Models\Account::whereIn('category', ['Kas & Bank', 'kas', 'bank'])->get()->mapWithKeys(fn($a) => [$a->id => "{$a->code} - {$a->name}"]))
                                    ->searchable()
                                    ->required()
                                    ->columnSpan(6),

                                DatePicker::make('transaction_date')
                                    ->label('Tanggal Transaksi')
                                    ->default(now())
                                    ->required()
                                    ->columnSpan(2),

                                TextInput::make('amount')
                                    ->label('Total')
                                    ->numeric()
                                    ->required()
                                    ->columnSpan(2),

                                TextInput::make('reference_number')
                                    ->label('Nomor')
                                    ->default(fn() => \App\Models\NumberingSetting::getNextNumber('journal_entry') ?? 'TR/00001')
                                    ->required()
                                    ->columnSpan(2),

                                Select::make('tags')
                                    ->label('Tag')
                                    ->multiple()
                                    ->options(\App\Models\Tag::pluck('name', 'id'))
                                    ->searchable()
                                    ->columnSpan(3),

                                TextInput::make('memo')
                                    ->label('Referensi')
                                    ->columnSpan(3),

                                FileUpload::make('attachment')
                                    ->label('Lampiran')
                                    ->directory('transfers')
                                    ->placeholder(new \Illuminate\Support\HtmlString('Seret & Jatuhkan berkas Anda atau <span class="text-primary-600 font-medium cursor-pointer">Jelajahi</span>'))
                                    ->columnSpan(6),
                            ]),
                    ])
                    ->action(function (array $data) {
                        $entry = JournalEntry::create([
                            'transaction_date' => $data['transaction_date'],
                            'description' => 'Transfer Dana',
                            'total_amount' => $data['amount'],
                            'reference_number' => $data['reference_number'],
                            'memo' => $data['memo'] ?? null,
                            'attachment' => $data['attachment'] ?? null,
                        ]);

                        if (!empty($data['tags'])) {
                            $entry->tags()->sync($data['tags']);
                        }

                        JournalItem::create([
                            'journal_entry_id' => $entry->id,
                            'account_id' => $data['to_account_id'],
                            'debit' => $data['amount'],
                            'credit' => 0,
                        ]);

                        JournalItem::create([
                            'journal_entry_id' => $entry->id,
                            'account_id' => $data['from_account_id'],
                            'debit' => 0,
                            'credit' => $data['amount'],
                        ]);
                    })
                    ->modalSubmitActionLabel('Transfer'),
                Action::make('kirim')
                    ->label('Kirim Dana')
                    ->icon('heroicon-m-arrow-up-tray')
                    ->url(fn() => "/admin/kas-bank/{$this->account->id}/kirim"),
                Action::make('terima')
                    ->label('Terima Dana')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->url(fn() => "/admin/kas-bank/{$this->account->id}/terima"),
            ])
                ->label('Transaksi Baru')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->button()
                ->size('sm'),
            Action::make('cetak')
                ->label('Cetak')
                ->color('gray')
                ->outlined()
                ->size('sm')
                ->icon('heroicon-o-printer')
                ->url('#'),
        ];
    }
}
