<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $warehouses = [
            ['name' => 'Unassigned', 'code' => 'WH-NA'],
            ['name' => 'Gudang JT', 'code' => 'WH-JT'],
            ['name' => 'Gudang Sample', 'code' => 'WH-SMP'],
            ['name' => 'Gudang Retur', 'code' => 'WH-RET'],
            ['name' => 'Gudang Riject', 'code' => 'WH-REJ'],
        ];

        foreach ($warehouses as $warehouse) {
            Warehouse::firstOrCreate(
                ['name' => $warehouse['name']],
                $warehouse
            );
        }
    }
}
