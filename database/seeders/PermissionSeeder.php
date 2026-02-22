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
        // 1. Define Features to generate permissions for
        $features = [
            'product',      // view_any_product, create_product, update_product, delete_product
            'sales',        // e.g. invoices, orders
            'purchase',
            'inventory',    // e.g. stock adjustments
            'contact',
            'user',
            'role',
            'report',
        ];

        $actions = ['view_any', 'create', 'update', 'delete'];

        $allPermissions = [];

        // 2. Generate Permissions
        foreach ($features as $feature) {
            foreach ($actions as $action) {
                $permissionName = "{$action}_{$feature}";
                Permission::firstOrCreate(['name' => $permissionName]);
                $allPermissions[] = $permissionName;
            }
        }

        // Add specific extra permissions if needed
        $extras = ['view_dashboard', 'manage_settings'];
        foreach ($extras as $extra) {
            Permission::firstOrCreate(['name' => $extra]);
            $allPermissions[] = $extra;
        }

        // 3. Assign All Permissions to "Super Admin" (create if not exists)
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);

        // Sync permissions
        $permissionIds = Permission::whereIn('name', $allPermissions)->pluck('id');
        $superAdminRole->permissions()->sync($permissionIds);

        // 4. Create a "Staff" role with limited permissions (Example)
        $staffRole = Role::firstOrCreate(['name' => 'Staff']);
        $staffPermissions = Permission::whereIn('name', [
            'view_any_product',
            'view_any_sales',
            'create_sales',
            'view_any_contact',
            'create_contact',
            'view_dashboard'
        ])->pluck('id');
        $staffRole->permissions()->syncWithoutDetaching($staffPermissions);

        $this->command->info('Permissions seeded and assigned to Super Admin & Staff roles.');
    }
}
