<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OutletResource\Pages;
use App\Models\Outlet;
use App\Models\OutletFloor;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class OutletResource extends Resource
{
    protected static ?string $model = Outlet::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    protected static string|null $navigationLabel = 'Outlet';
    protected static ?string $pluralModelLabel = 'Outlet';

    protected static string|\UnitEnum|null $navigationGroup = 'POS';
    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 1;

    public static function getBreadcrumbs(): array
    {
        return [
            url('/admin/pos-page') => 'POS',
            static::getUrl('index') => 'Outlet',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                // Section 1: Informasi Outlet
                Section::make('Informasi Outlet')
                    ->description('Masukkan informasi dasar outlet')
                    ->icon('heroicon-o-building-storefront')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nama')
                                    ->required()
                                    ->placeholder('Nama Outlet')
                                    ->maxLength(255),
                                TextInput::make('code')
                                    ->label('Kode')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('Contoh: OTL-001')
                                    ->maxLength(50),
                                Textarea::make('address')
                                    ->label('Alamat')
                                    ->placeholder('Alamat lengkap outlet')
                                    ->rows(3)
                                    ->maxLength(65535),
                                TextInput::make('phone')
                                    ->label('Telepon')
                                    ->placeholder('Nomor telepon outlet')
                                    ->tel()
                                    ->maxLength(20),
                            ]),
                    ]),

                // Section 2: Foto Outlet
                Section::make('Foto Outlet')
                    ->description('Upload foto outlet untuk tampilan di POS')
                    ->icon('heroicon-o-photo')
                    ->columnSpanFull()
                    ->schema([
                        FileUpload::make('image')
                            ->label('Foto')
                            ->image()
                            ->disk('public')
                            ->directory('outlets')
                            ->visibility('public'),
                    ]),

                // Section 3: Gudang
                Section::make('Gudang')
                    ->description('Hubungkan outlet ke gudang untuk sinkronisasi stok')
                    ->icon('heroicon-o-cube')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('warehouse_id')
                            ->label('Pilih Gudang')
                            ->relationship('warehouse', 'name')
                            ->placeholder('Pilih gudang')
                            ->searchable()
                            ->preload()
                            ->helperText('Hubungkan outlet ke gudang untuk mengambil stok produk'),
                    ]),

                // Section 4: Pengguna
                Section::make('Pengguna')
                    ->description('Kelola kasir dan pengguna yang memiliki akses ke outlet ini')
                    ->icon('heroicon-o-users')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('users')
                            ->label('Pilih Pengguna')
                            ->relationship('users', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->placeholder('Pilih pengguna')
                            ->helperText('Pengguna yang terpilih akan memiliki akses ke outlet ini'),
                    ]),

                // Section 5: Produk & Harga
                Section::make('Produk & Harga')
                    ->description('Atur produk yang ditampilkan dan harga pada outlet ini')
                    ->icon('heroicon-o-shopping-bag')
                    ->columnSpanFull()
                    ->schema([
                        ToggleButtons::make('product_display_type')
                            ->label('Produk yang ditampilkan pada outlet ini')
                            ->options([
                                'all' => 'Semua Produk',
                                'category' => 'Kategori Produk',
                                'per_product' => 'Per Produk',
                            ])
                            ->default('all')
                            ->inline()
                            ->required()
                            ->live(),
                        Select::make('categories')
                            ->label('Pilih Kategori Produk')
                            ->relationship('categories', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->visible(fn(Get $get) => $get('product_display_type') === 'category')
                            ->required(),
                        Select::make('products')
                            ->label('Pilih Produk')
                            ->relationship('products', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->visible(fn(Get $get) => $get('product_display_type') === 'per_product')
                            ->required(),
                        Grid::make(4)
                            ->schema([
                                ToggleButtons::make('price_type')
                                    ->label('Harga')
                                    ->options([
                                        'markup' => 'Markup',
                                        'discount' => 'Diskon',
                                    ])
                                    ->default('markup')
                                    ->inline()
                                    ->columnSpan(1),
                                TextInput::make('price_adjustment')
                                    ->label('Nilai')
                                    ->numeric()
                                    ->default(0)
                                    ->placeholder('0')
                                    ->columnSpan(1),
                                ToggleButtons::make('price_unit')
                                    ->label('Satuan')
                                    ->options([
                                        'percentage' => '%',
                                        'amount' => 'Rp',
                                    ])
                                    ->default('percentage')
                                    ->inline()
                                    ->columnSpan(1),
                            ]),
                    ]),

                // Section 6: Daftar Lantai
                Section::make('Daftar Lantai')
                    ->description('Kelola lantai dan meja pada outlet ini')
                    ->icon('heroicon-o-building-office')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('floors')
                            ->relationship()
                            ->label('')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Nama Lantai')
                                            ->required()
                                            ->placeholder('Contoh: Lantai 1'),
                                        TextInput::make('total_tables')
                                            ->label('Total Meja')
                                            ->numeric()
                                            ->default(0)
                                            ->placeholder('0'),
                                    ]),
                            ])
                            ->addActionLabel('+ Tambah Lantai')
                            ->collapsible()
                            ->defaultItems(0),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Standard columns are not used because we use a custom list view
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOutlets::route('/'),
            'create' => Pages\CreateOutlet::route('/create'),
            'edit' => Pages\EditOutlet::route('/{record}/edit'),
        ];
    }
}
