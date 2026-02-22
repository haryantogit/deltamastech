<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HutangResource\Pages;
use App\Models\Debt;
use App\Models\DebtPayment;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use App\Models\Account;
use Illuminate\Database\Eloquent\Builder;

class HutangResource extends Resource
{
    protected static ?string $model = Debt::class;

    protected static ?string $modelLabel = 'Hutang';
    protected static ?string $pluralModelLabel = 'Hutang';

    protected static ?string $navigationLabel = 'Hutang';
    protected static string|\UnitEnum|null $navigationGroup = 'Kontak';
    protected static bool $shouldRegisterNavigation = false;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-minus';
    protected static ?int $navigationSort = 2;
    protected static ?string $slug = 'hutang';

    public static function form(Schema $form): Schema
    {
        return $form
            ->columns(1)
            ->schema([
                Section::make('Informasi Hutang')
                    ->schema([
                        Select::make('supplier_id')
                            ->relationship('supplier', 'name')
                            ->required()
                            ->label('Pelanggan / Vendor')
                            ->searchable()
                            ->preload()
                            ->default(fn() => request()->query('supplier_id')),
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('date')
                                    ->required()
                                    ->label('Tanggal Transaksi')
                                    ->default(now()),
                                DatePicker::make('due_date')
                                    ->label('Tgl. Jatuh Tempo'),
                            ]),
                        Grid::make(3)
                            ->schema([
                                TextInput::make('number')
                                    ->label('Nomor')
                                    ->default(function () {
                                        $last = Debt::where('number', 'like', 'CM/%')->latest('id')->first();
                                        $next = $last ? (int) substr($last->number, 3) + 1 : 1;
                                        return 'CM/' . str_pad($next, 5, '0', STR_PAD_LEFT);
                                    })
                                    ->required()
                                    ->disabled()
                                    ->dehydrated(),
                                TextInput::make('reference')
                                    ->label('Referensi'),
                                Select::make('tags')
                                    ->label('Tag')
                                    ->multiple()
                                    ->relationship('tags', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->required()
                                            ->unique('tags', 'name'),
                                    ]),
                            ]),
                    ]),

                Section::make('Rincian')
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Select::make('account_id')
                                    ->label('Akun')
                                    ->options(Account::query()
                                        ->get()
                                        ->mapWithKeys(fn($account) => [$account->id => "{$account->code} - {$account->name}"]))
                                    ->searchable()
                                    ->required()
                                    ->columnSpan(2),
                                TextInput::make('description')
                                    ->label('Deskripsi')
                                    ->columnSpan(3),
                                TextInput::make('total_price')
                                    ->label('Jumlah')
                                    ->numeric()
                                    ->required()
                                    ->columnSpan(2)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        $set('unit_price', $state);
                                        $set('quantity', 1);
                                    }),
                                Hidden::make('quantity')->default(1),
                                Hidden::make('unit_price')->default(0),
                            ])
                            ->columns(7)
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $items = $get('items') ?? [];
                                $total = 0;
                                foreach ($items as $item) {
                                    $total += $item['total_price'] ?? 0;
                                }
                                $set('total_amount', $total);
                            }),

                        Textarea::make('notes')
                            ->label('Pesan')
                            ->rows(3),

                        FileUpload::make('attachments')
                            ->label('Attachment')
                            ->multiple()
                            ->downloadable()
                            ->openable(),

                        Hidden::make('total_amount')->default(0),
                        Hidden::make('status')->default('posted'),
                        Hidden::make('payment_status')->default('unpaid'),
                    ]),
            ]);
    }

    public static function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->schema([
                Section::make('Informasi Hutang')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('number')
                                    ->label('No. Invoice')
                                    ->weight(\Filament\Support\Enums\FontWeight::Bold)
                                    ->copyable(),
                                \Filament\Infolists\Components\TextEntry::make('supplier.name')
                                    ->label('Supplier'),
                                \Filament\Infolists\Components\TextEntry::make('date')
                                    ->label('Tanggal')
                                    ->date('d/m/Y'),
                                \Filament\Infolists\Components\TextEntry::make('due_date')
                                    ->label('Jatuh Tempo')
                                    ->date('d/m/Y')
                                    ->color(fn($record) => $record->due_date < now() ? 'danger' : 'gray'),
                                \Filament\Infolists\Components\TextEntry::make('reference')
                                    ->label('Referensi')
                                    ->placeholder('-'),
                                \Filament\Infolists\Components\TextEntry::make('payment_status')
                                    ->label('Status Pembayaran')
                                    ->badge()
                                    ->colors(['danger' => 'unpaid', 'warning' => 'partial', 'success' => 'paid'])
                                    ->formatStateUsing(fn(string $state): string => match ($state) {
                                        'unpaid' => 'Belum Bayar',
                                        'partial' => 'Dibayar Sebagian',
                                        'paid' => 'Lunas',
                                        default => $state,
                                    }),
                                \Filament\Infolists\Components\TextEntry::make('attachments')
                                    ->label('Lampiran')
                                    ->html()
                                    ->formatStateUsing(fn($state) => collect($state)->map(fn($file) => '<a href="' . asset('storage/' . $file) . '" target="_blank" style="color:rgb(var(--primary-600)); text-decoration:underline;">' . basename($file) . '</a>')->join('<br>'))
                                    ->columnSpanFull()
                                    ->visible(fn($record) => !empty($record->attachments)),
                            ]),
                    ]),

                Section::make('Rincian Transaksi')
                    ->columnSpanFull()
                    ->schema([
                        \Filament\Infolists\Components\RepeatableEntry::make('items')
                            ->label('Item')
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('account.name')
                                    ->label('Akun'),
                                \Filament\Infolists\Components\TextEntry::make('description')
                                    ->label('Deskripsi'),
                                \Filament\Infolists\Components\TextEntry::make('total_price')
                                    ->label('Jumlah')
                                    ->money('IDR'),
                            ])
                            ->columns(3),

                        Grid::make(1)
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('total_amount')
                                    ->label('Total Keseluruhan')
                                    ->money('IDR')
                                    ->weight(\Filament\Support\Enums\FontWeight::Bold)
                                    ->size('lg')
                                    ->color('primary'),
                            ]),
                    ]),

                Section::make('History Pembayaran')
                    ->columnSpanFull()
                    ->schema([
                        \Filament\Infolists\Components\RepeatableEntry::make('payments')
                            ->label(false)
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('date')
                                    ->label('Tanggal Bayar')
                                    ->date('d/m/Y'),
                                \Filament\Infolists\Components\TextEntry::make('account.name')
                                    ->label('Dari Kas/Bank'),
                                \Filament\Infolists\Components\TextEntry::make('amount')
                                    ->label('Jumlah Bayar')
                                    ->money('IDR')
                                    ->weight(\Filament\Support\Enums\FontWeight::Bold)
                                    ->color('success'),
                                \Filament\Infolists\Components\TextEntry::make('notes')
                                    ->label('Catatan')
                                    ->placeholder('-'),
                                \Filament\Infolists\Components\TextEntry::make('attachments_list')
                                    ->label('Lampiran')
                                    ->state(fn($record) => $record->attachments)
                                    ->html()
                                    ->formatStateUsing(fn($state) => collect($state)->map(fn($file) => '<a href="' . asset('storage/' . $file) . '" target="_blank" style="color:rgb(var(--primary-600)); text-decoration:underline;">' . basename($file) . '</a>')->join(', '))
                                    ->visible(fn($state) => !empty($state)),
                            ])
                            ->columns(5),

                        Grid::make(3)
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('total_paid')
                                    ->label('Total Terbayar')
                                    ->state(fn($record) => $record->payments()->sum('amount'))
                                    ->money('IDR')
                                    ->color('success'),
                                \Filament\Infolists\Components\TextEntry::make('balance')
                                    ->label('Sisa Hutang')
                                    ->state(fn($record) => $record->total_amount - $record->payments()->sum('amount'))
                                    ->money('IDR')
                                    ->color('danger')
                                    ->weight(\Filament\Support\Enums\FontWeight::Bold),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')->label('No. Invoice')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('supplier.name')->label('Supplier')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('date')->label('Tanggal')->date('d/m/Y')->sortable(),
                Tables\Columns\TextColumn::make('due_date')->label('Jatuh Tempo')->date('d/m/Y')->sortable()
                    ->color(fn($record) => $record->due_date < now() ? 'danger' : 'gray'),
                Tables\Columns\TextColumn::make('total_amount')->label('Jumlah')->money('IDR')->sortable(),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Sisa')
                    ->getStateUsing(fn($record) => $record->total_amount - $record->payments()->sum('amount'))
                    ->money('IDR')
                    ->color('danger')
                    ->summarize(
                        Tables\Columns\Summarizers\Summarizer::make()
                            ->label('Total Sisa')
                            ->using(fn($query) => $query->sum(\Illuminate\Support\Facades\DB::raw('total_amount - (select coalesce(sum(amount), 0) from debt_payments where debt_id = debts.id)')))
                    ),
                Tables\Columns\TextColumn::make('payment_status')->label('Status')->badge()
                    ->colors(['danger' => 'unpaid', 'warning' => 'partial', 'success' => 'paid'])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'unpaid' => 'Belum Bayar',
                        'partial' => 'Dibayar Sebagian',
                        'paid' => 'Lunas',
                        default => $state,
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Status Pembayaran')
                    ->options([
                        'unpaid' => 'Belum Bayar',
                        'partial' => 'Dibayar Sebagian',
                        'paid' => 'Lunas',
                    ]),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    \Filament\Actions\Action::make('print')
                        ->label('Cetak')
                        ->icon('heroicon-o-printer')
                        ->url(fn(Debt $record) => static::getUrl('view', ['record' => $record]))
                        ->openUrlInNewTab(),
                    EditAction::make(),
                    Action::make('bayar')
                        ->label('Bayar')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->form([
                            DatePicker::make('date')
                                ->label('Tanggal Bayar')
                                ->default(now())
                                ->required(),
                            Select::make('account_id')
                                ->label('Bayar Dari (Kas/Bank)')
                                ->options(Account::query()
                                    ->get()
                                    ->mapWithKeys(fn($account) => [$account->id => "{$account->code} - {$account->name}"]))
                                ->searchable()
                                ->required(),
                            TextInput::make('amount')
                                ->label('Jumlah Bayar')
                                ->numeric()
                                ->required()
                                ->default(fn($record) => $record->total_amount - $record->payments()->sum('amount')),
                            Textarea::make('notes')
                                ->label('Catatan'),
                            FileUpload::make('attachments')
                                ->label('Bukti Bayar / Lampiran')
                                ->multiple()
                                ->directory('debt-payments')
                                ->downloadable()
                                ->openable(),
                        ])
                        ->action(function (Debt $record, array $data): void {
                            $record->payments()->create([
                                'date' => $data['date'],
                                'account_id' => $data['account_id'],
                                'amount' => $data['amount'],
                                'notes' => $data['notes'],
                                'attachments' => $data['attachments'],
                            ]);

                            $totalPaid = $record->payments()->sum('amount');

                            if ($totalPaid >= $record->total_amount) {
                                $record->update(['payment_status' => 'paid']);
                            } elseif ($totalPaid > 0) {
                                $record->update(['payment_status' => 'partial']);
                            }
                        })
                        ->visible(fn(Debt $record) => $record->payment_status !== 'paid'),
                ])
                    ->icon('heroicon-m-ellipsis-vertical'),
            ])
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('number', 'like', 'CM/%');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHutang::route('/'),
            'create' => Pages\CreateHutang::route('/create'),
            'view' => Pages\ViewHutang::route('/{record}'),
            'edit' => Pages\EditHutang::route('/{record}/edit'),
        ];
    }
}
