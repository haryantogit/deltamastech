<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseResource\Pages;
use App\Models\Expense;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Tax;
use App\Models\Tag;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Illuminate\Database\Eloquent\Builder;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';
    protected static string|\UnitEnum|null $navigationGroup = null;
    protected static ?int $navigationSort = 30;
    protected static ?string $navigationLabel = 'Biaya';
    protected static ?string $modelLabel = 'Biaya';
    protected static ?string $pluralModelLabel = 'Biaya';
    protected static ?string $slug = 'biaya';

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Informasi Utama')
                    ->schema([
                        Grid::make(12)
                            ->schema([
                                Select::make('account_id')
                                    ->label('Dibayar Dari')
                                    ->relationship('account', 'name', fn($query) => $query->where('category', 'Kas & Bank'))
                                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} - {$record->name}")
                                    ->searchable(['code', 'name'])
                                    ->preload()
                                    ->default(fn() => \App\Models\Account::where('code', '1-10001')->value('id'))
                                    ->disabled(fn($get) => $get('is_pay_later'))
                                    ->required(fn($get) => !$get('is_pay_later'))
                                    ->columnSpan(6),

                                Toggle::make('is_pay_later')
                                    ->label('Bayar Nanti')
                                    ->reactive()
                                    ->default(false)
                                    ->inline(false)
                                    ->columnSpan(6),

                                Select::make('contact_id')
                                    ->relationship('contact', 'name')
                                    ->label('Penerima')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpan(fn($get) => $get('is_pay_later') ? 12 : 6),

                                DatePicker::make('transaction_date')
                                    ->label('Tgl. Transaksi')
                                    ->default(now())
                                    ->required()
                                    ->columnSpan(fn($get) => $get('is_pay_later') ? 4 : 6),

                                DatePicker::make('due_date')
                                    ->label('Tgl. Jatuh Tempo')
                                    ->required(fn($get) => $get('is_pay_later'))
                                    ->hidden(fn($get) => !$get('is_pay_later'))
                                    ->columnSpan(4),

                                Select::make('term_id')
                                    ->relationship('term', 'name')
                                    ->label('Termin')
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->label('Nama Termin (Misal: COD, 30 Hari)')
                                            ->required(),
                                        TextInput::make('days')
                                            ->label('Jumlah Hari')
                                            ->numeric()
                                            ->default(0)
                                            ->required(),
                                    ])
                                    ->required(fn($get) => $get('is_pay_later'))
                                    ->hidden(fn($get) => !$get('is_pay_later'))
                                    ->columnSpan(4),

                                TextInput::make('reference_number')
                                    ->label('Nomor')
                                    ->disabled()
                                    ->dehydrated()
                                    ->default(fn() => \App\Models\NumberingSetting::getNextNumber('expense') ?? 'EXP/' . date('Ymd') . '-' . rand(100, 999))
                                    ->columnSpan(4),

                                TextInput::make('reference')
                                    ->label('Referensi')
                                    ->columnSpan(4),

                                Select::make('tags')
                                    ->relationship('tags', 'name')
                                    ->label('Tag')
                                    ->multiple()
                                    ->preload()
                                    ->searchable()
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->required(),
                                    ])
                                    ->columnSpan(4),
                            ]),
                    ]),

                Section::make('Daftar Biaya')
                    ->schema([
                        Grid::make(1)
                            ->schema([
                                Toggle::make('tax_inclusive')
                                    ->label('Harga termasuk pajak')
                                    ->default(false)
                                    ->inline(false)
                                    ->reactive()
                                    ->afterStateUpdated(fn($set, $get) => static::calculateTotals($set, $get)),
                            ]),

                        Repeater::make('items')
                            ->relationship()
                            ->hiddenLabel()
                            ->schema([
                                Grid::make(12)
                                    ->schema([
                                        Select::make('account_id')
                                            ->label('Akun Biaya')
                                            ->relationship('account', 'name', fn($query) => $query->where(function ($q) {
                                                $q->where('code', 'like', '5%')
                                                    ->orWhere('code', 'like', '6%')
                                                    ->orWhere('code', 'like', '7%')
                                                    ->orWhere('code', 'like', '8%')
                                                    ->orWhere('code', 'like', '9%');
                                            }))
                                            ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} - {$record->name}")
                                            ->searchable(['code', 'name'])
                                            ->preload()
                                            ->required()
                                            ->columnSpan(3),

                                        TextInput::make('description')
                                            ->label('Deskripsi')
                                            ->columnSpan(4),

                                        Select::make('tax_id')
                                            ->label('Pajak')
                                            ->relationship('tax', 'name')
                                            ->preload()
                                            ->searchable()
                                            ->default(null)
                                            ->nullable()
                                            ->reactive()
                                            ->afterStateUpdated(fn($set, $get) => static::calculateTotals($set, $get))
                                            ->columnSpan(2),

                                        TextInput::make('amount')
                                            ->label('Total')
                                            ->numeric()
                                            ->required()
                                            ->reactive()
                                            ->afterStateUpdated(fn($set, $get) => static::calculateTotals($set, $get))
                                            ->columnSpan(3),
                                    ]),
                            ])
                            ->addActionLabel('+ Tambah baris')
                            ->reorderable(false)
                            ->afterStateUpdated(fn($set, $get) => static::calculateTotals($set, $get)),
                    ]),

                Grid::make(2)
                    ->schema([
                        Group::make()
                            ->schema([
                                Textarea::make('memo')
                                    ->label('Pesan')
                                    ->rows(3),

                                FileUpload::make('attachments')
                                    ->label('Lampiran')
                                    ->multiple()
                                    ->directory('expenses'),
                            ])->columnSpan(1),

                        Group::make()
                            ->schema([
                                TextInput::make('subtotal')
                                    ->label('Sub Total')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->readOnly()
                                    ->extraAttributes(['class' => 'font-bold pointer-events-none'])
                                    ->default(0),

                                Toggle::make('has_discount')
                                    ->label('Tambahan Diskon')
                                    ->dehydrated(false)
                                    ->afterStateHydrated(fn($component, $record) => $component->state($record ? ($record->discount_amount > 0) : false))
                                    ->reactive()
                                    ->afterStateUpdated(function ($set, $get, $state) {
                                        if (!$state) {
                                            $set('discount_amount', 0);
                                        }
                                        static::calculateTotals($set, $get);
                                    }),

                                TextInput::make('discount_amount')
                                    ->label('Nominal Diskon')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->reactive()
                                    ->extraAttributes(['class' => 'text-primary-600'])
                                    ->afterStateUpdated(fn($set, $get) => static::calculateTotals($set, $get))
                                    ->default(0)
                                    ->hidden(fn($get) => !$get('has_discount')),

                                TextInput::make('total_amount')
                                    ->label('Total')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->readOnly()
                                    ->extraAttributes(['class' => 'font-bold pointer-events-none'])
                                    ->default(0),

                                Hidden::make('remaining_amount')
                                    ->default(0),
                                Hidden::make('tax_total')
                                    ->default(0),
                            ])->columnSpan(1),
                    ]),
            ])
            ->columns(1);
    }

    public static function calculateTotals($set, $get)
    {
        $items = $get('items') ?? [];
        $subtotal = 0;
        $taxTotal = 0;

        foreach ($items as $item) {
            $amount = (float) ($item['amount'] ?? 0);
            $taxRate = 0;

            if (!empty($item['tax_id'])) {
                $tax = \App\Models\Tax::find($item['tax_id']);
                if ($tax) {
                    $taxRate = $tax->rate;
                }
            }

            if ($get('tax_inclusive')) {
                $subtotal += $amount;
                if ($taxRate > 0) {
                    $taxPortion = $amount - ($amount / (1 + ($taxRate / 100)));
                    $taxTotal += $taxPortion;
                }
            } else {
                $subtotal += $amount;
                if ($taxRate > 0) {
                    $taxTotal += $amount * ($taxRate / 100);
                }
            }
        }

        $discount = (float) ($get('discount_amount') ?? 0);
        $total = $subtotal + ($get('tax_inclusive') ? 0 : $taxTotal) - $discount;

        $set('subtotal', $subtotal);
        $set('tax_total', $taxTotal);
        $set('total_amount', $total);

        if (!$get('is_pay_later')) {
            $set('remaining_amount', 0);
        } else {
            $set('remaining_amount', $total);
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaction_date')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('reference_number')
                    ->label('Nomor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('contact.name')
                    ->label('Penerima')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('memo')
                    ->label('Keterangan')
                    ->limit(30)
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('Total')),
                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('Sisa Tagihan')
                    ->money('IDR')
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('Total')),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn($record) => $record->remaining_amount <= 0 ? 'Lunas' : 'Belum Dibayar')
                    ->color(fn($state) => $state === 'Lunas' ? 'success' : 'warning'),
            ])
            ->defaultSort('transaction_date', 'desc')
            ->filters([
                Tables\Filters\Filter::make('transaction_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Dari'),
                        Forms\Components\DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('transaction_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('transaction_date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = 'Dari ' . Carbon::parse($data['from'])->toFormattedDateString();
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = 'Sampai ' . Carbon::parse($data['until'])->toFormattedDateString();
                        }
                        return $indicators;
                    }),
                Tables\Filters\SelectFilter::make('contact_id')
                    ->label('Penerima')
                    ->relationship('contact', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'view' => Pages\ViewExpense::route('/{record}'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
}
