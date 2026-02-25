<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountResource\Pages;
use App\Models\Account;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ActionGroup;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static ?int $navigationSort = 8;

    protected static string|null $navigationLabel = 'Akun';
    protected static ?string $pluralModelLabel = 'Akun';
    protected static ?string $modelLabel = 'Akun';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-list-bullet';

    protected static string|\UnitEnum|null $navigationGroup = null;

    public static function form(\Filament\Schemas\Schema $form): \Filament\Schemas\Schema
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->label('Kode')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('category')
                    ->label('Kategori')
                    ->options([
                        'Kas & Bank' => 'Kas & Bank',
                        'Akun Piutang' => 'Akun Piutang',
                        'Persediaan' => 'Persediaan',
                        'Aktiva Lancar Lainnya' => 'Aktiva Lancar Lainnya',
                        'Aktiva Tetap' => 'Aktiva Tetap',
                        'Depresiasi & Amortisasi' => 'Depresiasi & Amortisasi',
                        'Aktiva Lainnya' => 'Aktiva Lainnya',
                        'Akun Hutang' => 'Akun Hutang',
                        'Kewajiban Lancar Lainnya' => 'Kewajiban Lancar Lainnya',
                        'Kewajiban Jangka Panjang' => 'Kewajiban Jangka Panjang',
                        'Ekuitas' => 'Ekuitas',
                        'Pendapatan' => 'Pendapatan',
                        'Harga Pokok Penjualan' => 'Harga Pokok Penjualan',
                        'Beban' => 'Beban',
                        'Pendapatan Lainnya' => 'Pendapatan Lainnya',
                        'Beban Lainnya' => 'Beban Lainnya',
                    ])
                    ->required(),
                Forms\Components\Select::make('parent_id')
                    ->label('Akun Induk')
                    ->relationship('parent', 'name')
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('current_balance')
                    ->label('Saldo Saat Ini')
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0)
                    ->disabled()
                    ->dehydrated(),
                Forms\Components\Textarea::make('description')
                    ->label('Deskripsi')
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ]);
    }

    public static function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Kode')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('category')
                    ->label('Kategori')
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_balance')
                    ->label('Saldo')
                    ->money('IDR')
                    ->sortable()
                    ->getStateUsing(function (Account $record) {
                        $items = \App\Models\JournalItem::where('account_id', $record->id);

                        if ($items->count() === 0) {
                            return 0;
                        }

                        $debit = $items->sum('debit');
                        $credit = $items->sum('credit');

                        $debitNormalCategories = [
                            'Kas & Bank',
                            'Akun Piutang',
                            'Persediaan',
                            'Aktiva Lancar Lainnya',
                            'Aktiva Tetap',
                            'Depresiasi & Amortisasi',
                            'Aktiva Lainnya',
                            'Harga Pokok Penjualan',
                            'Beban',
                            'Beban Lainnya',
                        ];

                        if (in_array($record->category, $debitNormalCategories)) {
                            return $debit - $credit;
                        }

                        return $credit - $debit;
                    })
                    ->badge()
                    ->color(fn($state) => $state < 0 ? 'danger' : 'primary')
                    ->action(
                        Action::make('history')
                            ->label('Riwayat Transaksi')
                            ->modalHeading('')
                            ->modalContent(fn(Account $record) => view('filament.components.account-history-modal', ['record' => $record]))
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalWidth('full')
                    ),
            ])
            ->defaultSort('code', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'Kas & Bank' => 'Kas & Bank',
                        'Akun Piutang' => 'Akun Piutang',
                        'Persediaan' => 'Persediaan',
                        'Aktiva Lancar Lainnya' => 'Aktiva Lancar Lainnya',
                        'Aktiva Tetap' => 'Aktiva Tetap',
                        'Depresiasi & Amortisasi' => 'Depresiasi & Amortisasi',
                        'Aktiva Lainnya' => 'Aktiva Lainnya',
                        'Akun Hutang' => 'Akun Hutang',
                        'Kewajiban Lancar Lainnya' => 'Kewajiban Lancar Lainnya',
                        'Kewajiban Jangka Panjang' => 'Kewajiban Jangka Panjang',
                        'Ekuitas' => 'Ekuitas',
                        'Pendapatan' => 'Pendapatan',
                        'Harga Pokok Penjualan' => 'Harga Pokok Penjualan',
                        'Beban' => 'Beban',
                        'Pendapatan Lainnya' => 'Pendapatan Lainnya',
                        'Beban Lainnya' => 'Beban Lainnya',
                    ]),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                ])
                    ->icon('heroicon-m-ellipsis-vertical'),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccounts::route('/'),
            'view' => Pages\ViewAccount::route('/{record}'),
            'saldo-awal' => Pages\SaldoAwalPage::route('/saldo-awal'),
        ];
    }
}
