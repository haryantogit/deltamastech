<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "Attempting to create Account manually...\n";
    $account = new \App\Models\Account();
    $account->fill([
        'code' => 'TEST-001',
        'name' => 'Test Account',
        'category' => 'Expense',
        'current_balance' => 0,
        'description' => 'Test',
        'is_active' => true
    ]);
    $account->save();
    echo "Account created successfully with ID: " . $account->id . "\n";

    // Clean up
    $account->delete();
    echo "Account deleted.\n";
} catch (\Exception $e) {
    echo "Error creating account: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
