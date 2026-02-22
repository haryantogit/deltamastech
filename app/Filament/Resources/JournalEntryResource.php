<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JournalEntryResource\Pages;
use App\Models\JournalEntry;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Filament\Actions\ViewAction;
use Filament\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;

class JournalEntryResource extends Resource
{
    protected static ?string $model = JournalEntry::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static string|\UnitEnum|null $navigationGroup = 'Menu Utama';

    protected static ?string $navigationLabel = 'Jurnal Umum';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 4;

    public static function form(\Filament\Schemas\Schema $form): \Filament\Schemas\Schema
    {
        return $form
            ->schema([
                Section::make('Informasi Jurnal')
                    ->schema([
                        DatePicker::make('transaction_date')
                            ->label('Tanggal')
                            ->required()
                            ->readOnly(),
                        TextInput::make('reference_number')
                            ->label('Nomor Referensi')
                            ->required()
                            ->readOnly(),
                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->columnSpanFull()
                            ->readOnly(),
                    ])->columns(2),

                Section::make('Detail Jurnal (Item)')
                    ->schema([
                        Repeater::make('items')
                            ->relationship('items')
                            ->label('')
                            ->schema([
                                Select::make('account_id')
                                    ->label('Akun')
                                    ->options(\App\Models\Account::get()->mapWithKeys(fn($account) => [$account->id => $account->code . ' - ' . $account->name]))
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->columnSpan(6),

                                TextInput::make('debit')
                                    ->label('Debit')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->readOnly()
                                    ->columnSpan(3),

                                TextInput::make('credit')
                                    ->label('Kredit')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->readOnly()
                                    ->columnSpan(3),
                            ])
                            ->columns(12)
                            ->disableItemCreation()
                            ->disableItemDeletion()
                            ->disableItemMovement(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transaction_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('reference_number')
                    ->label('No. Ref')
                    ->searchable(),
                TextColumn::make('description')
                    ->label('Keterangan')
                    ->limit(50),
                TextColumn::make('items_sum_debit')
                    ->label('Total Debit')
                    ->money('IDR')
                    ->sum('items', 'debit')
                    ->sortable(),
                TextColumn::make('items_sum_credit')
                    ->label('Total Kredit')
                    ->money('IDR')
                    ->sum('items', 'credit')
                    ->sortable(),
            ])
            ->defaultSort('transaction_date', 'desc')
            ->filters([
                Filter::make('transaction_date')
                    ->form([
                        DatePicker::make('created_from')->label('Dari Tanggal'),
                        DatePicker::make('created_until')->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('transaction_date', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('transaction_date', '<=', $date),
                            );
                    })
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                ])
                    ->icon('heroicon-m-ellipsis-vertical'),
            ])
            ->bulkActions([
                // Read-only
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
            'index' => Pages\ListJournalEntries::route('/'),
        ];
    }
}
