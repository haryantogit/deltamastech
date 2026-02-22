<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$ruswandi = \App\Models\Contact::where('name', 'Ruswandi')->first();

if ($ruswandi) {
    echo "Contact: " . $ruswandi->name . PHP_EOL;
    echo "Type: " . $ruswandi->type . PHP_EOL;
    echo "Payable Account: " . ($ruswandi->payableAccount ? $ruswandi->payableAccount->code : 'None') . PHP_EOL;
    echo "Receivable Account: " . ($ruswandi->receivableAccount ? $ruswandi->receivableAccount->code : 'None') . PHP_EOL;
    echo "Credit Limit: " . $ruswandi->credit_limit . PHP_EOL;
    echo "Receivable Limit: " . $ruswandi->receivable_limit . PHP_EOL;
} else {
    echo "Contact 'Ruswandi' not found." . PHP_EOL;
}

$nurhazin = \App\Models\Contact::where('name', 'Nurhazin')->first();
if ($nurhazin) {
    echo "Contact: " . $nurhazin->name . PHP_EOL;
    echo "Company: " . $nurhazin->company . PHP_EOL;
    echo "Type: " . $nurhazin->type . PHP_EOL;
}
