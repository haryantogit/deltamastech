<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaxResource\Pages;
use App\Models\Tax;
use Illuminate\Database\Eloquent\Model;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;

class TaxResource extends Resource
{
    protected static ?string $model = Tax::class;

    protected static ?string $slug = 'pajak';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string|\UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 14;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'Pajak';
    protected static ?string $pluralModelLabel = 'Pajak';

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Informasi Pajak')
                    ->schema([
                        Radio::make('type')
                            ->options([
                                'single' => 'Satu',
                                'group' => 'Grup',
                            ])
                            ->default('single')
                            ->label('Tipe Pajak')
                            ->required(),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Nama'),
                        TextInput::make('rate')
                            ->required()
                            ->numeric()
                            ->label('Persentase Efektif %')
                            ->suffix('%'),
                        Toggle::make('is_deduction')
                            ->label('Pemotongan')
                            ->helperText('Aktifkan jika pajak ini mengurangi total tagihan (misal PPH)')
                            ->default(false),
                        Select::make('sales_account_id')
                            ->relationship('salesAccount', 'name')
                            ->getOptionLabelFromRecordUsing(fn(Model $record) => "{$record->code} - {$record->name}")
                            ->searchable(['code', 'name'])
                            ->preload()
                            ->label('Akun Pajak Penjualan')
                            ->required(),
                        Select::make('purchase_account_id')
                            ->relationship('purchaseAccount', 'name')
                            ->getOptionLabelFromRecordUsing(fn(Model $record) => "{$record->code} - {$record->name}")
                            ->searchable(['code', 'name'])
                            ->preload()
                            ->label('Akun Pajak Pembelian')
                            ->required(),
                    ])
                    ->columns(1)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->label('Nama'),
                TextColumn::make('rate')
                    ->numeric()
                    ->sortable()
                    ->label('Persentase Efektif %')
                    ->suffix('%'),
                TextColumn::make('salesAccount.name')
                    ->label('Akun Pajak Penjualan')
                    ->searchable(),
                TextColumn::make('purchaseAccount.name')
                    ->label('Akun Pajak Pembelian')
                    ->searchable(),
                IconColumn::make('is_deduction')
                    ->boolean()
                    ->label('Pemotongan'),
            ])
            ->filters([
                //
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make()
                        ->modalWidth('2xl'),
                    EditAction::make()
                        ->modalWidth('2xl'),
                    DeleteAction::make(),
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
            'index' => Pages\ListTaxes::route('/'),
        ];
    }
}
