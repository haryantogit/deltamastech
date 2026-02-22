<?php

use App\Models\PaymentTerm;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$terms = [
    ['name' => 'Cash on Delivery', 'days' => 0],
    ['name' => 'Net 3', 'days' => 3],
    ['name' => 'Net 4', 'days' => 4],
    ['name' => 'Net 7', 'days' => 7],
    ['name' => 'Net 15', 'days' => 15],
    ['name' => 'Net 30', 'days' => 30],
    ['name' => 'Net 60', 'days' => 60],
];

echo "Seeding Payment Terms...\n";

foreach ($terms as $term) {
    PaymentTerm::firstOrCreate(
        ['name' => $term['name']],
        ['days' => $term['days']]
    );
    echo "Seeded: {$term['name']} ({$term['days']} days)\n";
}

echo "Done.\n";
