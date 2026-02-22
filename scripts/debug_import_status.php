<?php
$import = \Filament\Actions\Imports\Models\Import::latest()->first();
if ($import) {
    echo "ID: " . $import->id . PHP_EOL;
    echo "Importer: " . $import->importer . PHP_EOL;
    echo "Successful: " . $import->successful_rows . PHP_EOL;
    $failed = $import->getFailedRowsCount();
    echo "Failed: " . $failed . PHP_EOL;
    echo "Column Map: " . json_encode($import->column_map) . PHP_EOL;
} else {
    echo "No imports found." . PHP_EOL;
}
