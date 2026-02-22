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
    protected static ?string $pluralModelLabel = 'Biaya';
    protected static ?string $slug = 'biaya';

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Informasi Utama')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('contact_id')
                                    ->relationship('contact', 'name')
                                    ->label('Penerima')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                DatePicker::make('transaction_date')
                                    ->label('Tgl. Transaksi')
                                    ->default(now())
                                    ->required(),
                                DatePicker::make('due_date')
                                    ->label('Tgl. Jatuh Tempo'),
                                TextInput::make('reference_number')
                                    ->label('Nomor Biaya')
                                    ->disabled()
                                    ->dehydrated()
                                    ->default(function () {
                                        $lastExpense = \App\Models\Expense::latest('id')->first();
                                        if ($lastExpense && preg_match('/EXP\/(\d{5})/', $lastExpense->reference_number, $matches)) {
                                            return 'EXP/' . str_pad(intval($matches[1]) + 1, 5, '0', STR_PAD_LEFT);
                                        }
                                        return 'EXP/00001';
                                    }),
                            ]),
                    ]),

                Section::make('Metode Pembayaran')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Toggle::make('is_pay_later')
                                    ->label('Bayar Nanti')
                                    ->reactive()
                                    ->default(false),
                                Toggle::make('is_recurring')
                                    ->label('Transaksi Berulang')
                                    ->default(false),
                                Select::make('account_id')
                                    ->label('Bayar Dari')
                                    ->options(function () {
                                        return Account::where('category', 'Asset')
                                            ->where(function ($query) {
                                                $query->where('name', 'like', '%Kas%')
                                                    ->orWhere('name', 'like', '%Bank%');
                                            })
                                            ->get()
                                            ->mapWithKeys(fn($item) => [$item->id => "{$item->code} - {$item->name}"]);
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->hidden(fn($get) => $get('is_pay_later'))
                                    ->required(fn($get) => !$get('is_pay_later')),
                            ]),
                    ]),

                Section::make('Daftar Biaya')
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        Select::make('account_id')
                                            ->label('Akun Biaya')
                                            ->options(function () {
                                                return Account::where('category', 'Expense')
                                                    ->get()
                                                    ->mapWithKeys(fn($item) => [$item->id => "{$item->code} - {$item->name}"]);
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->columnSpan(2),
                                        TextInput::make('amount')
                                            ->label('Jumlah')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->required()
                                            ->reactive()
                                            ->afterStateUpdated(fn($set, $get) => static::calculateTotals($set, $get)),
                                        Select::make('tax_id')
                                            ->label('Pajak')
                                            ->relationship('tax', 'name')
                                            ->preload()
                                            ->searchable()
                                            ->default(null)
                                            ->nullable(),
                                    ]),
                                TextInput::make('description')
                                    ->label('Deskripsi')
                                    ->columnSpanFull(),
                            ])
                            ->addActionLabel('Tambah Baris Biaya')
                            ->reorderable(false)
                            ->afterStateUpdated(fn($set, $get) => static::calculateTotals($set, $get)),

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
                                            ->readOnly()
                                            ->prefix('Rp')
                                            ->default(0),
                                        TextInput::make('total_amount')
                                            ->label('Total')
                                            ->numeric()
                                            ->readOnly()
                                            ->prefix('Rp')
                                            ->default(0),
                                        Hidden::make('remaining_amount')
                                            ->default(0),
                                    ])->columnSpan(1),
                            ]),
                    ]),

                Section::make('Tagging')
                    ->schema([
                        Select::make('tags')
                            ->relationship('tags', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->required(),
                            ]),
                    ])->collapsible()->collapsed(),
            ]);
    }

    public static function calculateTotals($set, $get)
    {
        $items = $get('items') ?? [];
        $subtotal = 0;

        foreach ($items as $item) {
            $subtotal += (float) ($item['amount'] ?? 0);
        }

        $set('subtotal', $subtotal);
        $set('total_amount', $subtotal);

        if (!$get('is_pay_later')) {
            $set('remaining_amount', 0);
        } else {
            $set('remaining_amount', $subtotal);
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
