<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Filament\Actions\Imports\Models\Import;
use Filament\Actions\Imports\Models\FailedImportRow;

$import = Import::latest()->first();
echo "Checking Import ID: " . $import->id . " Status: " . ($import->completed_at ? 'Completed' : 'Running') . "\n";

$failedRow = FailedImportRow::where('import_id', $import->id)->first();

if ($failedRow) {
    echo "Found Failed Row ID: " . $failedRow->id . "\n";
    echo "Validation Error: " . json_encode($failedRow->validation_error) . "\n";
    echo "Data: " . json_encode($failedRow->data, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "No failed rows found for this import.\n";
}
