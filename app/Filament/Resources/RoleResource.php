<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Models\Role;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\CheckboxList;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Pengaturan';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?int $navigationSort = 2;
    protected static ?string $modelLabel = 'Peran';
    protected static ?string $pluralModelLabel = 'Peran';
    protected static ?string $navigationLabel = 'Peran';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Detail Peran')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Peran')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Hidden::make('guard_name')
                            ->default('web'),
                    ]),

                Section::make('Hak Akses')
                    ->description('Pilih hak akses untuk peran ini.')
                    ->schema(function () {
                        $structure = [
                            'penjualan' => [
                                'label' => 'Penjualan',
                                'submodules' => [
                                    'quote' => ['label' => 'Penawaran Penjualan', 'actions' => ['view' => 'Lihat', 'add' => 'Tambah', 'edit' => 'Ubah', 'delete' => 'Hapus', 'approve' => 'Setujui', 'reject' => 'Tolak']],
                                    'order' => ['label' => 'Pesanan Penjualan', 'actions' => ['view' => 'Lihat', 'add' => 'Tambah', 'edit' => 'Ubah', 'delete' => 'Hapus']],
                                    'delivery' => ['label' => 'Pengiriman Penjualan', 'actions' => ['view' => 'Lihat', 'add' => 'Tambah', 'edit' => 'Ubah', 'delete' => 'Hapus']],
                                    'invoice' => ['label' => 'Tagihan Penjualan', 'actions' => ['view' => 'Lihat', 'add' => 'Tambah', 'edit' => 'Ubah', 'delete' => 'Hapus', 'payment' => 'Pembayaran', 'return' => 'Retur', 'void' => 'Void']],
                                    'return' => ['label' => 'Retur Penjualan', 'actions' => ['view' => 'Lihat', 'add' => 'Tambah', 'edit' => 'Ubah', 'delete' => 'Hapus']],
                                ],
                                'globals' => [
                                    'access_other_users' => 'Akses Milik User Lain',
                                    'view_price' => 'Lihat Harga',
                                    'view_other_sales_person' => 'Tampilkan Data Milik Sales Person Lain',
                                ]
                            ],
                            'pembelian' => [
                                'label' => 'Pembelian',
                                'submodules' => [
                                    'quote' => ['label' => 'Penawaran Pembelian', 'actions' => ['view' => 'Lihat', 'add' => 'Tambah', 'edit' => 'Ubah', 'delete' => 'Hapus', 'approve' => 'Setujui', 'reject' => 'Tolak']],
                                    'order' => ['label' => 'Pesanan Pembelian', 'actions' => ['view' => 'Lihat', 'add' => 'Tambah', 'edit' => 'Ubah', 'delete' => 'Hapus']],
                                    'delivery' => ['label' => 'Penerimaan Pembelian', 'actions' => ['view' => 'Lihat', 'add' => 'Tambah', 'edit' => 'Ubah', 'delete' => 'Hapus']],
                                    'invoice' => ['label' => 'Tagihan Pembelian', 'actions' => ['view' => 'Lihat', 'add' => 'Tambah', 'edit' => 'Ubah', 'delete' => 'Hapus', 'payment' => 'Pembayaran', 'return' => 'Retur', 'void' => 'Void']],
                                    'return' => ['label' => 'Retur Pembelian', 'actions' => ['view' => 'Lihat', 'add' => 'Tambah', 'edit' => 'Ubah', 'delete' => 'Hapus']],
                                ]
                            ],
                            'produk' => [
                                'label' => 'Produk',
                                'submodules' => [
                                    'list' => ['label' => 'Daftar Produk', 'actions' => ['view' => 'Lihat', 'add' => 'Tambah', 'edit' => 'Ubah', 'delete' => 'Hapus', 'deactivate' => 'Nonaktifkan']],
                                ]
                            ],
                            'inventori' => [
                                'label' => 'Inventori',
                                'submodules' => [
                                    'warehouse' => ['label' => 'Daftar Gudang', 'actions' => ['view' => 'Lihat', 'add' => 'Tambah', 'edit' => 'Ubah', 'delete' => 'Hapus']],
                                    'transfer' => ['label' => 'Daftar Transfer', 'actions' => ['view' => 'Lihat', 'add' => 'Tambah', 'edit' => 'Ubah', 'delete' => 'Hapus']],
                                    'adjustment' => ['label' => 'Daftar Penyesuaian', 'actions' => ['view' => 'Lihat', 'add' => 'Tambah', 'edit' => 'Ubah', 'delete' => 'Hapus']],
                                    'movement' => ['label' => 'Riwayat Pergerakan Stok', 'actions' => ['view' => 'Lihat']],
                                ]
                            ],
                            'produksi' => [
                                'label' => 'Produksi',
                                'submodules' => [
                                    'order' => ['label' => 'Konversi Produk', 'actions' => ['view' => 'Lihat', 'add' => 'Tambah', 'edit' => 'Ubah', 'delete' => 'Hapus', 'confirm' => 'Konfirmasi']],
                                    'result' => ['label' => 'Laporan Produksi', 'actions' => ['view' => 'Lihat', 'add' => 'Tambah', 'edit' => 'Ubah', 'delete' => 'Hapus']],
                                ]
                            ],
                            'laporan' => [
                                'label' => 'Laporan',
                                'submodules' => [
                                    'financial' => ['label' => 'Laporan Keuangan', 'actions' => ['view' => 'Lihat']],
                                    'accounting' => ['label' => 'Laporan Akuntansi', 'actions' => ['view' => 'Lihat']],
                                    'sales' => ['label' => 'Laporan Penjualan', 'actions' => ['view' => 'Lihat']],
                                    'purchase' => ['label' => 'Laporan Pembelian', 'actions' => ['view' => 'Lihat']],
                                    'tax' => ['label' => 'Laporan Pajak', 'actions' => ['view' => 'Lihat']],
                                    'expense' => ['label' => 'Laporan Biaya', 'actions' => ['view' => 'Lihat']],
                                    'fixed_asset' => ['label' => 'Laporan Aset', 'actions' => ['view' => 'Lihat']],
                                    'inventory' => ['label' => 'Laporan Inventori', 'actions' => ['view' => 'Lihat']],
                                ]
                            ],
                            'akuntansi' => [
                                'label' => 'Akuntansi',
                                'submodules' => [
                                    'journal' => ['label' => 'Jurnal Umum', 'actions' => ['view' => 'Lihat', 'add' => 'Tambah', 'edit' => 'Ubah', 'delete' => 'Hapus', 'post' => 'Post/Confirm']],
                                    'account' => ['label' => 'Daftar Akun', 'actions' => ['view' => 'Lihat', 'add' => 'Tambah', 'edit' => 'Ubah', 'delete' => 'Hapus']],
                                ]
                            ],
                            'fixed_asset' => [
                                'label' => 'Aset Tetap',
                                'submodules' => [
                                    'list' => ['label' => 'Daftar Aset Tetap', 'actions' => ['view' => 'Lihat', 'add' => 'Tambah', 'edit' => 'Ubah', 'delete' => 'Hapus']],
                                ]
                            ],
                            'kontak' => [
                                'label' => 'Kontak',
                                'submodules' => [
                                    'list' => ['label' => 'Semua Kontak', 'actions' => ['view' => 'Lihat', 'add' => 'Tambah', 'edit' => 'Ubah', 'delete' => 'Hapus']],
                                    'customer' => ['label' => 'Pelanggan', 'actions' => ['view' => 'Lihat', 'add' => 'Tambah', 'edit' => 'Ubah', 'delete' => 'Hapus']],
                                    'vendor' => ['label' => 'Vendor', 'actions' => ['view' => 'Lihat', 'add' => 'Tambah', 'edit' => 'Ubah', 'delete' => 'Hapus']],
                                    'employee' => ['label' => 'Karyawan', 'actions' => ['view' => 'Lihat', 'add' => 'Tambah', 'edit' => 'Ubah', 'delete' => 'Hapus']],
                                    'debt' => ['label' => 'Hutang', 'actions' => ['view' => 'Lihat', 'add' => 'Tambah', 'edit' => 'Ubah', 'delete' => 'Hapus']],
                                    'receivable' => ['label' => 'Piutang', 'actions' => ['view' => 'Lihat', 'add' => 'Tambah', 'edit' => 'Ubah', 'delete' => 'Hapus']],
                                ]
                            ],
                            'pengaturan' => [
                                'label' => 'Pengaturan',
                                'submodules' => [
                                    'user' => ['label' => 'Pengguna', 'actions' => ['view' => 'Lihat', 'add' => 'Tambah', 'edit' => 'Ubah', 'delete' => 'Hapus']],
                                    'role' => ['label' => 'Peran', 'actions' => ['view' => 'Lihat', 'add' => 'Tambah', 'edit' => 'Ubah', 'delete' => 'Hapus']],
                                    'general_settings' => ['label' => 'Pengaturan Umum', 'actions' => ['view' => 'Lihat', 'edit' => 'Ubah']],
                                ]
                            ],
                            'biaya' => [
                                'label' => 'Biaya',
                                'submodules' => [
                                    'list' => ['label' => 'Daftar Biaya', 'actions' => ['view' => 'Lihat', 'add' => 'Tambah', 'edit' => 'Ubah', 'delete' => 'Hapus']],
                                    'approval' => ['label' => 'Persetujuan', 'actions' => ['view' => 'Lihat', 'approve' => 'Setujui', 'reject' => 'Tolak']],
                                    'schedule' => ['label' => 'Penjadwalan', 'actions' => ['view' => 'Lihat', 'add' => 'Tambah', 'edit' => 'Ubah', 'delete' => 'Hapus']],
                                ]
                            ],
                            'kas_bank' => [
                                'label' => 'Kas & Bank',
                                'submodules' => [
                                    'kas_bank' => ['label' => 'Daftar Kas & Bank', 'actions' => ['view' => 'Lihat', 'add' => 'Tambah', 'edit' => 'Ubah', 'delete' => 'Hapus', 'connect' => 'Bank Connect']],
                                ]
                            ],
                            'anggaran' => [
                                'label' => 'Anggaran',
                                'submodules' => [
                                    'management' => ['label' => 'Manajemen Anggaran', 'actions' => ['view' => 'Lihat', 'add' => 'Tambah', 'edit' => 'Ubah', 'delete' => 'Hapus']],
                                    'report' => ['label' => 'Laporan Anggaran', 'actions' => ['view' => 'Lihat']],
                                ]
                            ],
                            'pos' => [
                                'label' => 'POS',
                                'submodules' => [
                                    'web_pos' => ['label' => 'Web POS', 'actions' => ['view' => 'Lihat', 'add' => 'Tambah', 'edit' => 'Ubah', 'delete' => 'Hapus']],
                                ]
                            ],
                        ];

                        $schema = [];

                        foreach ($structure as $moduleKey => $moduleData) {
                            $moduleItems = [];

                            // Hub Permission (Main Toggle)
                            $moduleItems[] = CheckboxList::make('hub_' . $moduleKey)
                                ->hiddenLabel()
                                ->options(function () use ($moduleKey) {
                                $perm = \App\Models\Permission::where('name', "view_hub_{$moduleKey}")->first();
                                return $perm ? [$perm->id => 'Aktifkan Menu ' . ucwords($moduleKey)] : [];
                            })
                                ->afterStateHydrated(function ($component, $record) use ($moduleKey) {
                                if (!$record)
                                    return;
                                $ids = $record->permissions()
                                    ->where('name', "view_hub_{$moduleKey}")
                                    ->pluck('permissions.id')
                                    ->toArray();
                                $component->state($ids);
                            })
                                ->dehydrated(false);

                            // Submodules
                            if (isset($moduleData['submodules'])) {
                                foreach ($moduleData['submodules'] as $subKey => $subData) {
                                    $moduleItems[] = Section::make($subData['label'])
                                        ->schema([
                                            // Activation Toggle (Separate view permission)
                                            CheckboxList::make('sub_' . $moduleKey . '_' . $subKey . '_active')
                                                ->hiddenLabel()
                                                ->options(function () use ($moduleKey, $subKey) {
                                                    $perm = \App\Models\Permission::where('name', "{$moduleKey}.{$subKey}.view")->first();
                                                    return $perm ? [$perm->id => 'Aktif'] : [];
                                                })
                                                ->afterStateHydrated(function ($component, $record) use ($moduleKey, $subKey) {
                                                    if (!$record)
                                                        return;
                                                    $ids = $record->permissions()
                                                        ->where('name', "{$moduleKey}.{$subKey}.view")
                                                        ->pluck('permissions.id')
                                                        ->toArray();
                                                    $component->state($ids);
                                                })
                                                ->dehydrated(false),

                                            // Other Actions
                                            CheckboxList::make('sub_' . $moduleKey . '_' . $subKey)
                                                ->hiddenLabel()
                                                ->options(function () use ($moduleKey, $subKey, $subData) {
                                                    $options = [];
                                                    foreach ($subData['actions'] as $actionKey => $actionLabel) {
                                                        if ($actionKey === 'view')
                                                            continue; // Skip view as it's now in the 'Aktif' toggle
                                                        $perm = \App\Models\Permission::where('name', "{$moduleKey}.{$subKey}.{$actionKey}")->first();
                                                        if ($perm) {
                                                            $options[$perm->id] = $actionLabel;
                                                        }
                                                    }
                                                    return $options;
                                                })
                                                ->afterStateHydrated(function ($component, $record) use ($moduleKey, $subKey, $subData) {
                                                    if (!$record)
                                                        return;
                                                    $names = [];
                                                    foreach ($subData['actions'] as $actionKey => $label) {
                                                        if ($actionKey === 'view')
                                                            continue;
                                                        $names[] = "{$moduleKey}.{$subKey}.{$actionKey}";
                                                    }
                                                    $ids = $record->permissions()
                                                        ->whereIn('name', $names)
                                                        ->pluck('permissions.id')
                                                        ->toArray();
                                                    $component->state($ids);
                                                })
                                                ->dehydrated(false)
                                                ->columns(3)
                                                ->bulkToggleable(),
                                        ])
                                        ->collapsible()
                                        ->compact();
                                }
                            }

                            // Globals
                            if (isset($moduleData['globals'])) {
                                $moduleItems[] = Section::make('Fitur Tambahan')
                                    ->schema([
                                        CheckboxList::make('globals_' . $moduleKey)
                                            ->label('')
                                            ->options(function () use ($moduleKey, $moduleData) {
                                                $options = [];
                                                foreach ($moduleData['globals'] as $globalKey => $label) {
                                                    $perm = \App\Models\Permission::where('name', "{$moduleKey}.global.{$globalKey}")->first();
                                                    if ($perm) {
                                                        $options[$perm->id] = $label;
                                                    }
                                                }
                                                return $options;
                                            })
                                            ->afterStateHydrated(function ($component, $record) use ($moduleKey, $moduleData) {
                                                if (!$record)
                                                    return;
                                                $names = [];
                                                foreach ($moduleData['globals'] as $globalKey => $label) {
                                                    $names[] = "{$moduleKey}.global.{$globalKey}";
                                                }
                                                $ids = $record->permissions()
                                                    ->whereIn('name', $names)
                                                    ->pluck('permissions.id')
                                                    ->toArray();
                                                $component->state($ids);
                                            })
                                            ->dehydrated(false)
                                            ->columns(2)
                                            ->bulkToggleable(),
                                    ])
                                    ->collapsible()
                                    ->compact();
                            }

                            $schema[] = Section::make($moduleData['label'])
                                ->schema($moduleItems)
                                ->collapsible()
                                ->collapsed();
                        }

                        // Extras
                        $schema[] = Section::make('Lainnya')
                            ->schema([
                                CheckboxList::make('permissions_other')
                                    ->hiddenLabel()
                                    ->options(function () {
                            return \App\Models\Permission::whereIn('name', ['view_dashboard', 'manage_settings'])
                                ->pluck('name', 'id')
                                ->map(fn($name) => ucwords(str_replace('_', ' ', $name)));
                        })
                                    ->afterStateHydrated(function ($component, $record) {
                            if (!$record)
                                return;
                            $ids = $record->permissions()
                                ->whereIn('name', ['view_dashboard', 'manage_settings'])
                                ->pluck('permissions.id')
                                ->toArray();
                            $component->state($ids);
                        })
                                    ->dehydrated(false)
                                    ->columns(2),
                            ])
                            ->collapsible()
                            ->collapsed();

                        return $schema;
                    }),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Peran')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('users_count')
                    ->counts('users')
                    ->label('Jumlah Pengguna'),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ])
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
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
