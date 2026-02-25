<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BudgetResource\Pages;
use App\Models\Account;
use App\Models\Budget;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class BudgetResource extends Resource
{
    protected static ?string $model = Budget::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calculator';

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 41;

    protected static ?string $modelLabel = 'Manajemen Anggaran';

    protected static ?string $pluralModelLabel = 'Manajemen Anggaran';

    public static function form(\Filament\Schemas\Schema $form): \Filament\Schemas\Schema
    {
        return $form
            ->schema([
                \Filament\Schemas\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Anggaran')
                            ->placeholder('Contoh: Anggaran Operasional Q1 2026')
                            ->required()
                            ->columnSpan(2),
                        Forms\Components\Select::make('period_type')
                            ->label('Tipe Periode')
                            ->options([
                                'monthly' => 'Bulanan',
                                'yearly' => 'Tahunan',
                            ])
                            ->default('monthly')
                            ->required(),
                    ]),
                \Filament\Schemas\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Tanggal Mulai')
                            ->required(),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Tanggal Selesai')
                            ->required(),
                    ]),
                Forms\Components\Textarea::make('description')
                    ->label('Keterangan')
                    ->placeholder('Catatan atau deskripsi anggaran...')
                    ->columnSpanFull(),

                Forms\Components\Repeater::make('items')
                    ->relationship()
                    ->schema([
                        Forms\Components\Select::make('account_id')
                            ->label('Akun')
                            ->options(
                                Account::whereIn('category', ['Pendapatan', 'Beban', 'Harga Pokok Penjualan', 'Pendapatan Lainnya', 'Beban Lainnya'])
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->required()
                            ->columnSpan(2),
                        Forms\Components\TextInput::make('amount')
                            ->label('Target Nominal')
                            ->numeric()
                            ->prefix('Rp')
                            ->required(),
                    ])
                    ->columns(3)
                    ->columnSpanFull()
                    ->label('Rincian Target Anggaran'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Anggaran')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('period_type')
                    ->label('Periode')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'monthly' => 'info',
                        'yearly' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state) => match ($state) {
                        'monthly' => 'Bulanan',
                        'yearly' => 'Tahunan',
                        default => $state,
                    }),
                TextColumn::make('start_date')
                    ->label('Mulai')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label('Selesai')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('items_count')
                    ->label('Jumlah Akun')
                    ->counts('items'),
                TextColumn::make('total_budget')
                    ->label('Total Anggaran')
                    ->money('idr')
                    ->getStateUsing(fn(Budget $record) => $record->items()->sum('amount')),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBudgets::route('/'),
            'create' => Pages\CreateBudget::route('/create'),
            'edit' => Pages\EditBudget::route('/{record}/edit'),
        ];
    }
}
