<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactResource\Pages;
use App\Models\Contact;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;

use Filament\Actions\ViewAction;
use Filament\Actions\BulkAction;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Grid as SchemaGrid;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Components\Tabs as SchemaTabs;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|\UnitEnum|null $navigationGroup = null;
    protected static ?string $navigationLabel = 'Kontak';
    protected static ?string $modelLabel = 'Kontak';
    protected static ?string $pluralModelLabel = 'Kontak';
    protected static ?int $navigationSort = 11;

    protected static ?string $slug = 'contacts';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Tabs::make('Tabs')
                    ->tabs([
                        Tabs\Tab::make('Profil Utama')
                            ->schema([
                                Forms\Components\FileUpload::make('photo')
                                    ->label('Foto')
                                    ->image()
                                    ->avatar()
                                    ->directory('contacts')
                                    ->columnSpanFull(),
                                Forms\Components\Select::make('type')
                                    ->label('Tipe Kontak')
                                    ->options([
                                        'customer' => 'Pelanggan',
                                        'vendor' => 'Vendor',
                                        'employee' => 'Karyawan',
                                        'others' => 'Lainnya',
                                    ])
                                    ->required()
                                    ->default('customer')
                                    ->columnSpanFull(),
                                Grid::make(4)
                                    ->schema([
                                        Forms\Components\Select::make('salutation')
                                            ->label('Sapaan')
                                            ->options([
                                                'Bapak' => 'Bapak',
                                                'Ibu' => 'Ibu',
                                                'Sdr' => 'Sdr',
                                                'Nona' => 'Nona',
                                            ])
                                            ->columnSpan(1),
                                        Forms\Components\TextInput::make('name')
                                            ->label('Nama Lengkap')
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpan(3),
                                    ]),
                                Forms\Components\TextInput::make('nik')
                                    ->label('NIK (Nomor Induk Kependudukan)')
                                    ->mask('9999999999999999')
                                    ->placeholder('16 digit NIK')
                                    ->minLength(16)
                                    ->maxLength(16),
                                Forms\Components\TextInput::make('company')
                                    ->label('Nama Perusahaan')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('tax_id')
                                    ->label('NPWP')
                                    ->mask('9999999999999999')
                                    ->placeholder('16 digit NPWP')
                                    ->minLength(16)
                                    ->maxLength(16),
                            ])->columns(2),
                        Tabs\Tab::make('Alamat & Kontak')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('phone')
                                            ->label('Telepon')
                                            ->tel()
                                            ->maxLength(15)
                                            ->extraInputAttributes(['oninput' => "this.value = this.value.replace(/[^0-9]/g, '').slice(0, 15)"]),
                                        Forms\Components\TextInput::make('email')
                                            ->label('Email')
                                            ->email()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('mobile')
                                            ->label('Seluler (Mobile)')
                                            ->tel()
                                            ->maxLength(15)
                                            ->extraInputAttributes(['oninput' => "this.value = this.value.replace(/[^0-9]/g, '').slice(0, 15)"]),
                                    ]),
                                Forms\Components\Textarea::make('address')
                                    ->label('Alamat Lengkap')
                                    ->maxLength(65535)
                                    ->columnSpanFull(),
                                Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('city')
                                            ->label('Kota'),
                                        Forms\Components\TextInput::make('province')
                                            ->label('Provinsi'),
                                        Forms\Components\TextInput::make('postal_code')
                                            ->label('Kode Pos')
                                            ->numeric(),
                                    ]),
                            ]),
                        Tabs\Tab::make('Pengaturan Keuangan')
                            ->schema([
                                Forms\Components\Select::make('receivable_account_id')
                                    ->label('Akun Piutang')
                                    ->relationship('receivableAccount', 'name')
                                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} - {$record->name}")
                                    ->searchable(['code', 'name'])
                                    ->default(14)
                                    ->preload(),
                                Forms\Components\Select::make('payable_account_id')
                                    ->label('Akun Hutang')
                                    ->relationship('payableAccount', 'name')
                                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} - {$record->name}")
                                    ->searchable(['code', 'name'])
                                    ->default(44)
                                    ->preload(),
                                Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('credit_limit')
                                            ->label('Batas Kredit (Credit Limit)')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->default(0),
                                        Forms\Components\TextInput::make('receivable_limit')
                                            ->label('Batas Piutang')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->default(0),
                                    ]),
                                SchemaSection::make('Informasi Bank')
                                    ->schema([
                                        Forms\Components\Repeater::make('bankAccounts')
                                            ->relationship()
                                            ->schema([
                                                Grid::make(3)
                                                    ->schema([
                                                        Forms\Components\TextInput::make('bank_name')
                                                            ->label('Nama Bank')
                                                            ->maxLength(255),
                                                        Forms\Components\TextInput::make('bank_account_no')
                                                            ->label('Nomor Rekening')
                                                            ->numeric()
                                                            ->maxLength(255),
                                                        Forms\Components\TextInput::make('bank_account_holder')
                                                            ->label('Atas Nama')
                                                            ->maxLength(255),
                                                    ]),
                                            ])
                                            ->addActionLabel('Tambah Rekening')
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpanFull(),
                            ])->columns(2),
                    ])->columnSpanFull(),
            ]);
    }

    public static function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->schema([
                \Filament\Schemas\Components\Group::make(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columnToggleFormColumns(2)
            ->columns([
                Tables\Columns\ImageColumn::make('photo')
                    ->label('Foto')
                    ->circular()
                    ->defaultImageUrl(fn($record) => match ($record?->type) {
                        'customer' => asset('images/default-avatar.png'),
                        'vendor' => asset('images/default-avatar.png'),
                        'employee' => asset('images/default-avatar.png'),
                        'others' => asset('images/default-avatar.png'),
                        default => asset('images/default-avatar.png'),
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('salutation')
                    ->label('Sapaan')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'customer' => 'Pelanggan',
                        'vendor' => 'Vendor',
                        'employee' => 'Karyawan',
                        'others' => 'Lainnya',
                        default => $state,
                    })
                    ->colors([
                        'success' => 'customer',
                        'warning' => 'vendor',
                        'gray' => 'employee',
                        'info' => 'others',
                    ])
                    ->toggleable(),
                Tables\Columns\TextColumn::make('nik')
                    ->label('NIK')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('tax_id')
                    ->label('NPWP')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('company')
                    ->label('Perusahaan')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Telepon')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('mobile')
                    ->label('Seluler')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('address')
                    ->label('Alamat')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('city')
                    ->label('Kota')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('province')
                    ->label('Provinsi')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('postal_code')
                    ->label('Kode Pos')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('receivableAccount.name')
                    ->label('Akun Piutang')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('payableAccount.name')
                    ->label('Akun Hutang')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('credit_limit')
                    ->label('Batas Kredit')
                    ->money('IDR')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('receivable_limit')
                    ->label('Batas Piutang')
                    ->money('IDR')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipe Kontak')
                    ->options([
                        'customer' => 'Pelanggan',
                        'vendor' => 'Vendor',
                        'employee' => 'Karyawan',
                        'others' => 'Lainnya',
                    ]),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Lihat'),
                    \Filament\Actions\Action::make('print')
                        ->label('Cetak')
                        ->icon('heroicon-o-printer')
                        ->url(fn(Contact $record) => static::getUrl('view', ['record' => $record]))
                        ->openUrlInNewTab(),
                    EditAction::make()
                        ->label('Ubah'),
                    DeleteAction::make()
                        ->label('Hapus'),
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

    public static function getRelations(): array
    {
        return [
            ContactResource\RelationManagers\SalesInvoicesRelationManager::class,
            ContactResource\RelationManagers\PurchaseInvoicesRelationManager::class,
            ContactResource\RelationManagers\ExpensesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContacts::route('/'),
            'create' => Pages\CreateContact::route('/create'),
            'view' => Pages\ViewContact::route('/{record}'),
            'edit' => Pages\EditContact::route('/{record}/edit'),
        ];
    }
}
