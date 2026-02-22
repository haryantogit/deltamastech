<?php

use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Updating Purchase Order statuses to 'approved' (Disetujui)...\n";

$count = PurchaseOrder::where('status', '!=', 'approved')->count();

if ($count === 0) {
    echo "No purchase orders to update. All are already approved.\n";
    exit;
}

echo "Found $count orders to update.\n";

PurchaseOrder::query()->update(['status' => 'approved']);

echo "Successfully updated statuses to 'approved'.\n";
