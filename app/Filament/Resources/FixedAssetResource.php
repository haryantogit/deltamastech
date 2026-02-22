<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FixedAssetResource\Pages;
use App\Models\Product;
use App\Models\Account;
use App\Models\FixedAssetDepreciation;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\Unit;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section as InfolistSection;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;

class FixedAssetResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = 'Aset Tetap';

    protected static ?string $pluralModelLabel = 'Aset Tetap';

    protected static ?string $modelLabel = 'Aset Tetap';

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 10;

    public static function form(\Filament\Schemas\Schema $form): \Filament\Schemas\Schema
    {
        return $form
            ->columns(1)
            ->schema([
                Hidden::make('is_fixed_asset')->default(true),
                Hidden::make('type')->default('fixed_asset'),
                Hidden::make('status')->default('draft'),
                Hidden::make('can_be_purchased')->default(true),

                Section::make(fn($record) => ($record && $record->status === 'draft') ? 'Daftarkan Aset Tetap' : 'Detil')
                    ->schema([
                        FileUpload::make('image')
                            ->label('Tampilkan gambar aset tetap')
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('products')
                            ->columnSpanFull(),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nama Aset')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('sku')
                                    ->label('Nomor')
                                    ->default(function () {
                                        $nextId = (Product::where('is_fixed_asset', true)->max('id') ?? 0) + 1;
                                        return 'FA/' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
                                    })
                                    ->maxLength(255)
                                    ->disabled(fn(string $operation): bool => $operation === 'edit'),

                                DatePicker::make('purchase_date')
                                    ->label('Tanggal Pembelian')
                                    ->required()
                                    ->default(now()),
                                TextInput::make('purchase_price')
                                    ->label('Harga Beli')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required()
                                    ->default(0),

                                Select::make('asset_account_id')
                                    ->label('Akun Aset Tetap')
                                    ->relationship('assetAccount', 'name', fn(Builder $query) => $query->where('code', 'LIKE', '1-%')->orderBy('code'))
                                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} - {$record->name}")
                                    ->searchable(['code', 'name'])
                                    ->default(179)
                                    ->required(),
                                Select::make('credit_account_id')
                                    ->label('Dikreditkan Dari Akun')
                                    ->relationship('creditAccount', 'name', fn(Builder $query) => $query->orderBy('code'))
                                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} - {$record->name}")
                                    ->searchable(['code', 'name'])
                                    ->required(),

                                TextInput::make('description')
                                    ->label('Deskripsi')
                                    ->maxLength(65535),
                                Select::make('tags')
                                    ->label('Tag')
                                    ->placeholder('Pilih Tag')
                                    ->relationship('tags', 'name')
                                    ->multiple()
                                    ->searchable(),

                                TextInput::make('reference')
                                    ->label('Referensi')
                                    ->maxLength(255),
                                Select::make('purchase_invoice_id')
                                    ->label('Tagihan Pembelian')
                                    ->relationship('purchaseInvoice', 'number')
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        // Optional: Add quick create form if needed, but not requested for now.
                                    ]),
                            ]),
                    ]),

                Section::make('Penyusutan')
                    ->schema([
                        Toggle::make('has_depreciation')
                            ->label('Tanpa penyusutan')
                            ->formatStateUsing(fn($state) => !$state)
                            ->dehydrateStateUsing(fn($state) => !$state)
                            ->live(),

                        Grid::make(2)
                            ->schema([
                                Select::make('accumulated_depreciation_account_id')
                                    ->label('Akun Akumulasi Penyusutan')
                                    ->relationship('accumulatedDepreciationAccount', 'name', fn(Builder $query) => $query->where('code', 'LIKE', '1-%')->orderBy('code'))
                                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} - {$record->name}")
                                    ->searchable(['code', 'name'])
                                    ->required(),
                                Select::make('depreciation_expense_account_id')
                                    ->label('Akun penyusutan') // Updated label
                                    ->relationship('depreciationExpenseAccount', 'name', fn(Builder $query) => $query->where('code', 'LIKE', '6-%')->orderBy('code'))
                                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} - {$record->name}")
                                    ->searchable(['code', 'name'])
                                    ->required(),

                                Grid::make(12)
                                    ->schema([
                                        Group::make([
                                            Radio::make('depreciation_calc_type')
                                                ->label('')
                                                ->options([
                                                    'rate' => 'Nilai penyusutan per tahun',
                                                ])
                                                ->live()
                                                ->dehydrated(false),
                                            TextInput::make('depreciation_rate')
                                                ->label('')
                                                ->numeric()
                                                ->suffix('%')
                                                ->placeholder('0')
                                                ->live(onBlur: true)
                                                ->disabled(fn(Get $get) => $get('depreciation_calc_type') !== 'rate')
                                                ->afterStateUpdated(function (Set $set, ?string $state) {
                                                    if ($state > 0) {
                                                        $set('useful_life_years', 100 / $state);
                                                    }
                                                }),
                                        ])->columnSpan(6),

                                        Group::make([
                                            Radio::make('depreciation_calc_type')
                                                ->label('')
                                                ->options([
                                                    'useful_life' => 'Masa Manfaat',
                                                ])
                                                ->live()
                                                ->dehydrated(false),
                                            Grid::make(2)
                                                ->schema([
                                                    TextInput::make('useful_life_years')
                                                        ->label('Tahun')
                                                        ->numeric()
                                                        ->placeholder('0')
                                                        ->disabled(fn(Get $get) => $get('depreciation_calc_type') !== 'useful_life')
                                                        ->live(onBlur: true)
                                                        ->afterStateUpdated(function (Set $set, ?string $state) {
                                                            if ($state > 0) {
                                                                $set('depreciation_rate', 100 / $state);
                                                            }
                                                        }),
                                                    TextInput::make('useful_life_months')
                                                        ->label('Bulan')
                                                        ->numeric()
                                                        ->disabled(fn(Get $get) => $get('depreciation_calc_type') !== 'useful_life')
                                                        ->placeholder('0'),
                                                ]),
                                        ])->columnSpan(6),
                                    ])
                                    ->columnSpanFull(),

                                Toggle::make('show_more_options')
                                    ->label('Sembunyikan opsi lainnya')
                                    ->default(false)
                                    ->columnSpanFull()
                                    ->live(),

                                Grid::make(2)
                                    ->schema([
                                        Select::make('depreciation_method')
                                            ->label('Metode Penyusutan')
                                            ->options([
                                                'straight_line' => 'Straight Line',
                                                'declining_balance_100' => 'Declining Balance (100%)',
                                                'declining_balance_150' => 'Declining Balance (150%)',
                                                'declining_balance_200' => 'Declining Balance (200%)',
                                            ])
                                            ->default('straight_line'),
                                        DatePicker::make('depreciation_start_date')
                                            ->label('Tanggal Mulai Penyusutan')
                                            ->default(now()),

                                        Grid::make(3)
                                            ->schema([
                                                TextInput::make('accumulated_depreciation_value')
                                                    ->label('Akumulasi Penyusutan')
                                                    ->numeric()
                                                    ->default(0),
                                                TextInput::make('cost_limit')
                                                    ->label('Batas Biaya')
                                                    ->numeric()
                                                    ->default(0),
                                                TextInput::make('salvage_value')
                                                    ->label('Nilai Sisa / Residu')
                                                    ->numeric()
                                                    ->prefix('Rp')
                                                    ->default(0),
                                            ])
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpanFull()
                                    ->visible(fn(Get $get) => !$get('show_more_options')),
                            ])
                            ->visible(fn(Get $get) => !$get('has_depreciation')),
                    ]),
            ]);
    }

    public static function infolist(\Filament\Schemas\Schema $infolist): \Filament\Schemas\Schema
    {
        return $infolist
            ->schema([
                \Filament\Schemas\Components\Section::make('Detil')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('name')
                            ->label('Nama Aset')
                            ->inlineLabel(),
                        \Filament\Infolists\Components\TextEntry::make('sku')
                            ->label('Nomor')
                            ->inlineLabel(),
                        \Filament\Infolists\Components\TextEntry::make('purchase_date')
                            ->label('Tanggal Pembelian')
                            ->date('d/m/Y')
                            ->inlineLabel(),
                        \Filament\Infolists\Components\TextEntry::make('purchase_price')
                            ->label('Harga Beli')
                            ->money('IDR', locale: 'id')
                            ->inlineLabel(),
                        \Filament\Infolists\Components\TextEntry::make('creditAccount.name')
                            ->label('Dikreditkan Dari Akun')
                            ->formatStateUsing(fn($record) => $record->creditAccount ? "{$record->creditAccount->code} - {$record->creditAccount->name}" : '-')
                            ->inlineLabel(),
                        \Filament\Infolists\Components\TextEntry::make('description')
                            ->label('Deskripsi')
                            ->inlineLabel(),
                    ])->columns(1),

                \Filament\Schemas\Components\Section::make('Penyusutan')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('accumulatedDepreciationAccount.name')
                            ->label('Akun Akumulasi Penyusutan')
                            ->formatStateUsing(fn($record) => $record->accumulatedDepreciationAccount ? "{$record->accumulatedDepreciationAccount->code} - {$record->accumulatedDepreciationAccount->name}" : '-')
                            ->inlineLabel(),
                        \Filament\Infolists\Components\TextEntry::make('depreciationExpenseAccount.name')
                            ->label('Akun penyusutan')
                            ->formatStateUsing(fn($record) => $record->depreciationExpenseAccount ? "{$record->depreciationExpenseAccount->code} - {$record->depreciationExpenseAccount->name}" : '-')
                            ->inlineLabel(),
                        \Filament\Infolists\Components\TextEntry::make('depreciation_method')
                            ->label('Metode Penyusutan')
                            ->formatStateUsing(fn($state) => match ($state) {
                                'straight_line' => 'Straight Line',
                                'declining_balance_100' => 'Declining Balance (100%)',
                                'declining_balance_150' => 'Declining Balance (150%)',
                                'declining_balance_200' => 'Declining Balance (200%)',
                                default => $state,
                            })
                            ->inlineLabel(),
                        \Filament\Infolists\Components\TextEntry::make('depreciation_rate')
                            ->label('Nilai penyusutan per tahun')
                            ->suffix('%')
                            ->inlineLabel(),
                        \Filament\Infolists\Components\TextEntry::make('useful_life_years')
                            ->label('Masa Manfaat')
                            ->formatStateUsing(fn($record) => ($record->useful_life_years ?: 0) . " Tahun " . ($record->useful_life_months ?: 0) . " Bulan")
                            ->inlineLabel(),
                        \Filament\Infolists\Components\TextEntry::make('depreciation_start_date')
                            ->label('Tanggal Mulai Penyusutan')
                            ->date('d/m/Y')
                            ->inlineLabel(),
                        \Filament\Infolists\Components\TextEntry::make('accumulated_depreciation_value')
                            ->label('Akumulasi Penyusutan')
                            ->money('IDR', locale: 'id')
                            ->inlineLabel(),
                        \Filament\Infolists\Components\TextEntry::make('cost_limit')
                            ->label('Batas Biaya')
                            ->money('IDR')
                            ->inlineLabel(),
                    ])->columns(1),

                InfolistSection::make('Riwayat Transaksi')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('purchase_date')
                                    ->label('Tanggal Pembelian')
                                    ->date('d/m/Y'),
                                TextEntry::make('purchase_price')
                                    ->label('Harga Beli')
                                    ->money('IDR', locale: 'id'),
                                TextEntry::make('book_value')
                                    ->label('Nilai Buku')
                                    ->money('IDR', locale: 'id')
                                    ->getStateUsing(fn($record) => $record->purchase_price - $record->accumulated_depreciation_value),
                            ]),
                        ViewEntry::make('transactions_table')
                            ->view('filament.infolists.entries.fixed-asset-transactions-table')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                InfolistSection::make('Upgrade Aset')
                    ->headerActions([
                        \Filament\Actions\Action::make('add_upgrade')
                            ->label('Tambah')
                            ->icon('heroicon-m-plus')
                            ->form([
                                FileUpload::make('evidence_image')
                                    ->label('Tampilkan gambar aset tetap')
                                    ->image()
                                    ->disk('public')
                                    ->directory('fixed-asset-upgrades')
                                    ->columnSpanFull(),
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('asset_name')
                                            ->label('Nama Aset')
                                            ->default(fn($record) => $record->name)
                                            ->disabled(),
                                        TextInput::make('asset_sku')
                                            ->label('Nomor')
                                            ->default(fn($record) => $record->sku)
                                            ->disabled(),
                                        DatePicker::make('date')
                                            ->label('Tanggal')
                                            ->required()
                                            ->default(now()),
                                        TextInput::make('amount')
                                            ->label('Harga')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->required(),
                                        Select::make('credit_account_id')
                                            ->label('Dikreditkan Dari Akun')
                                            ->relationship('creditAccount', 'name', fn(Builder $query) => $query->orderBy('code'))
                                            ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} - {$record->name}")
                                            ->searchable(['code', 'name'])
                                            ->required()
                                            ->columnSpanFull(),
                                        Textarea::make('description')
                                            ->label('Deskripsi')
                                            ->columnSpanFull(),
                                    ]),
                            ])
                            ->action(function ($record, array $data) {
                                $record->fixedAssetUpgrades()->create([
                                    'date' => $data['date'],
                                    'amount' => $data['amount'],
                                    'credit_account_id' => $data['credit_account_id'],
                                    'description' => $data['description'],
                                    'evidence_image' => $data['evidence_image'],
                                ]);
                            })
                    ])
                    ->schema([
                        ViewEntry::make('upgrades_table')
                            ->view('filament.infolists.entries.fixed-asset-upgrades-table')
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->where('is_fixed_asset', true))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Aset')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sku')
                    ->label('Nomor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reference')
                    ->label('Referensi')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tags.name')
                    ->label('Tag')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('purchase_date')
                    ->label('Tanggal Pembelian')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchase_price')
                    ->label('Harga Beli')
                    ->money('IDR')
                    ->sortable()
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Total')
                            ->money('IDR', locale: 'id')
                    ),
                Tables\Columns\TextColumn::make('book_value')
                    ->label('Nilai Buku')
                    ->getStateUsing(fn($record) => $record->purchase_price - $record->accumulated_depreciation_value)
                    ->money('IDR')
                    ->summarize(
                        Tables\Columns\Summarizers\Summarizer::make()
                            ->label('Total')
                            ->using(fn($query) => 'Rp ' . number_format($query->get()->sum(fn($record) => $record->purchase_price - $record->accumulated_depreciation_value), 0, ',', '.'))
                    ),
            ])
            ->filters([
                //
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\Action::make('register')
                        ->label('Daftarkan')
                        ->color('primary')
                        ->icon('heroicon-m-check-circle')
                        ->visible(fn($record) => $record->status === 'draft')
                        ->action(fn($record) => $record->update(['status' => 'registered'])),
                    \Filament\Actions\Action::make('dispose')
                        ->label('Lepas/Jual')
                        ->icon('heroicon-m-banknotes')
                        ->color('danger')
                        ->visible(fn($record) => $record->status === 'registered')
                        ->url(fn($record) => static::getUrl('dispose', ['record' => $record])),
                    \Filament\Actions\ViewAction::make(),
                    \Filament\Actions\Action::make('print')
                        ->label('Cetak')
                        ->icon('heroicon-o-printer')
                        ->url(fn(Product $record) => static::getUrl('view', ['record' => $record]))
                        ->openUrlInNewTab(),
                    \Filament\Actions\EditAction::make(),
                    \Filament\Actions\DeleteAction::make(),
                ]),
            ])
            ->defaultSort('sku', 'asc')
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('print')
                        ->label('Cetak Terpilih')
                        ->icon('heroicon-o-printer')
                        ->action(fn() => null)
                        ->extraAttributes(['onclick' => 'window.print(); return false;']),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFixedAssets::route('/'),
            'create' => Pages\CreateFixedAsset::route('/create'),
            'view' => Pages\ViewFixedAsset::route('/{record}'),
            'edit' => Pages\EditFixedAsset::route('/{record}/edit'),
            'dispose' => Pages\DisposeFixedAsset::route('/{record}/dispose'),
        ];
    }
}
