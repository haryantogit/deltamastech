<?php
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$whitelist = [
    'users',
    'accounts',
    'roles',
    'permissions',
    'permission_role',
    'migrations',
    'sessions',
    'categories', // Keeping categories as they are usually master data
];

try {
    Schema::disableForeignKeyConstraints();

    $tables = DB::select('SHOW TABLES');
    $databaseName = DB::getDatabaseName();
    $tableKey = "Tables_in_{$databaseName}";

    foreach ($tables as $table) {
        $tableName = $table->$tableKey;

        if (!in_array($tableName, $whitelist)) {
            echo "Truncating table: {$tableName}..." . PHP_EOL;
            DB::table($tableName)->truncate();
        } else {
            echo "Skipping whitelisted table: {$tableName}" . PHP_EOL;
        }
    }

    Schema::enableForeignKeyConstraints();
    echo PHP_EOL . "Database cleanup completed successfully!" . PHP_EOL;
} catch (\Exception $e) {
    Schema::enableForeignKeyConstraints();
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
}
