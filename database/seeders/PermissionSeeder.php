<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing permissions to avoid duplicates with old naming conventions
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('permission_role')->truncate();
        Permission::query()->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 1. Define Hierarchical Structure
        $structure = [
            'penjualan' => [
                'label' => 'Penjualan',
                'submodules' => [
                    'quote' => ['label' => 'Penawaran Penjualan', 'actions' => ['view', 'add', 'edit', 'delete', 'approve', 'reject']],
                    'order' => ['label' => 'Pesanan Penjualan', 'actions' => ['view', 'add', 'edit', 'delete']],
                    'delivery' => ['label' => 'Pengiriman Penjualan', 'actions' => ['view', 'add', 'edit', 'delete']],
                    'invoice' => ['label' => 'Tagihan Penjualan', 'actions' => ['view', 'add', 'edit', 'delete', 'payment', 'return', 'void']],
                    'return' => ['label' => 'Retur Penjualan', 'actions' => ['view', 'add', 'edit', 'delete']],
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
                    'quote' => ['label' => 'Penawaran Pembelian', 'actions' => ['view', 'add', 'edit', 'delete', 'approve', 'reject']],
                    'order' => ['label' => 'Pesanan Pembelian', 'actions' => ['view', 'add', 'edit', 'delete']],
                    'delivery' => ['label' => 'Penerimaan Pembelian', 'actions' => ['view', 'add', 'edit', 'delete']],
                    'invoice' => ['label' => 'Tagihan Pembelian', 'actions' => ['view', 'add', 'edit', 'delete', 'payment', 'return', 'void']],
                    'return' => ['label' => 'Retur Pembelian', 'actions' => ['view', 'add', 'edit', 'delete']],
                ]
            ],
            'produk' => [
                'label' => 'Produk',
                'submodules' => [
                    'list' => ['label' => 'Daftar Produk', 'actions' => ['view', 'add', 'edit', 'delete', 'deactivate']],
                ]
            ],
            'inventori' => [
                'label' => 'Inventori',
                'submodules' => [
                    'warehouse' => ['label' => 'Daftar Gudang', 'actions' => ['view', 'add', 'edit', 'delete']],
                    'transfer' => ['label' => 'Daftar Transfer', 'actions' => ['view', 'add', 'edit', 'delete']],
                    'adjustment' => ['label' => 'Daftar Penyesuaian', 'actions' => ['view', 'add', 'edit', 'delete']],
                    'movement' => ['label' => 'Riwayat Pergerakan Stok', 'actions' => ['view']],
                ]
            ],
            'produksi' => [
                'label' => 'Produksi',
                'submodules' => [
                    'order' => ['label' => 'Konversi Produk', 'actions' => ['view', 'add', 'edit', 'delete', 'confirm']],
                    'result' => ['label' => 'Laporan Produksi', 'actions' => ['view', 'add', 'edit', 'delete']],
                ]
            ],
            'laporan' => [
                'label' => 'Laporan',
                'submodules' => [
                    'financial' => ['label' => 'Laporan Keuangan', 'actions' => ['view']],
                    'accounting' => ['label' => 'Laporan Akuntansi', 'actions' => ['view']],
                    'sales' => ['label' => 'Laporan Penjualan', 'actions' => ['view']],
                    'purchase' => ['label' => 'Laporan Pembelian', 'actions' => ['view']],
                    'tax' => ['label' => 'Laporan Pajak', 'actions' => ['view']],
                    'expense' => ['label' => 'Laporan Biaya', 'actions' => ['view']],
                    'fixed_asset' => ['label' => 'Laporan Aset', 'actions' => ['view']],
                    'inventory' => ['label' => 'Laporan Inventori', 'actions' => ['view']],
                ]
            ],
            'akuntansi' => [
                'label' => 'Akuntansi',
                'submodules' => [
                    'journal' => ['label' => 'Jurnal Umum', 'actions' => ['view', 'add', 'edit', 'delete', 'post']],
                    'account' => ['label' => 'Daftar Akun', 'actions' => ['view', 'add', 'edit', 'delete']],
                ]
            ],
            'fixed_asset' => [
                'label' => 'Aset Tetap',
                'submodules' => [
                    'list' => ['label' => 'Daftar Aset Tetap', 'actions' => ['view', 'add', 'edit', 'delete']],
                ]
            ],
            'kontak' => [
                'label' => 'Kontak',
                'submodules' => [
                    'list' => ['label' => 'Semua Kontak', 'actions' => ['view', 'add', 'edit', 'delete']],
                    'customer' => ['label' => 'Pelanggan', 'actions' => ['view', 'add', 'edit', 'delete']],
                    'vendor' => ['label' => 'Vendor', 'actions' => ['view', 'add', 'edit', 'delete']],
                    'employee' => ['label' => 'Karyawan', 'actions' => ['view', 'add', 'edit', 'delete']],
                    'debt' => ['label' => 'Hutang', 'actions' => ['view', 'add', 'edit', 'delete']],
                    'receivable' => ['label' => 'Piutang', 'actions' => ['view', 'add', 'edit', 'delete']],
                ]
            ],
            'pengaturan' => [
                'label' => 'Pengaturan',
                'submodules' => [
                    'user' => ['label' => 'Pengguna', 'actions' => ['view', 'add', 'edit', 'delete']],
                    'role' => ['label' => 'Peran', 'actions' => ['view', 'add', 'edit', 'delete']],
                    'general_settings' => ['label' => 'Pengaturan Umum', 'actions' => ['view', 'edit']],
                ]
            ],
            'biaya' => [
                'label' => 'Biaya',
                'submodules' => [
                    'list' => ['label' => 'Daftar Biaya', 'actions' => ['view', 'add', 'edit', 'delete']],
                    'approval' => ['label' => 'Persetujuan', 'actions' => ['view', 'approve', 'reject']],
                    'schedule' => ['label' => 'Penjadwalan', 'actions' => ['view', 'add', 'edit', 'delete']],
                ]
            ],
            'kas_bank' => [
                'label' => 'Kas & Bank',
                'submodules' => [
                    'kas_bank' => ['label' => 'Daftar Kas & Bank', 'actions' => ['view', 'add', 'edit', 'delete', 'connect']],
                ]
            ],
            'anggaran' => [
                'label' => 'Anggaran',
                'submodules' => [
                    'management' => ['label' => 'Manajemen Anggaran', 'actions' => ['view', 'add', 'edit', 'delete']],
                    'report' => ['label' => 'Laporan Anggaran', 'actions' => ['view']],
                ]
            ],
            'pos' => [
                'label' => 'POS',
                'submodules' => [
                    'web_pos' => ['label' => 'Web POS', 'actions' => ['view', 'add', 'edit', 'delete']],
                ]
            ],
        ];

        $allPermissions = [];

        // 2. Generate and store permissions
        foreach ($structure as $moduleKey => $moduleData) {
            // Module View permission (to see the hub/sidebar menu)
            $moduleViewPermission = "view_hub_{$moduleKey}";
            Permission::firstOrCreate(['name' => $moduleViewPermission, 'guard_name' => 'web']);
            $allPermissions[] = $moduleViewPermission;

            // Submodules and Actions
            if (isset($moduleData['submodules'])) {
                foreach ($moduleData['submodules'] as $subKey => $subData) {
                    foreach ($subData['actions'] as $action) {
                        $permissionName = "{$moduleKey}.{$subKey}.{$action}";
                        Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
                        $allPermissions[] = $permissionName;
                    }
                }
            }

            // Globals
            if (isset($moduleData['globals'])) {
                foreach ($moduleData['globals'] as $globalKey => $label) {
                    $permissionName = "{$moduleKey}.global.{$globalKey}";
                    Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
                    $allPermissions[] = $permissionName;
                }
            }
        }

        // Add extra fixed permissions
        $extras = ['view_dashboard', 'manage_settings'];
        foreach ($extras as $extra) {
            Permission::firstOrCreate(['name' => $extra, 'guard_name' => 'web']);
            $allPermissions[] = $extra;
        }

        // 3. Assign All Permissions to "Super Admin"
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $permissionIds = Permission::whereIn('name', $allPermissions)->pluck('id');
        $superAdminRole->permissions()->sync($permissionIds);

        // 4. Create a "Staff" role with limited permissions (New hierarchical format)
        $staffRole = Role::firstOrCreate(['name' => 'Staff', 'guard_name' => 'web']);
        $staffPermissions = Permission::whereIn('name', [
            'view_dashboard',
            'view_hub_penjualan',
            'penjualan.order.view',
            'penjualan.invoice.view',
            'penjualan.invoice.add',
            'view_hub_kontak',
            'kontak.list.view',
            'kontak.list.add',
        ])->pluck('id');
        $staffRole->permissions()->sync($staffPermissions);

        $this->command->info('Hierarchical permissions seeded and assigned.');
    }
}
