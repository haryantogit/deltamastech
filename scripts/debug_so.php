<?php
$so = \App\Models\SalesOrder::first();
print_r($so->toArray());

// Check if any SO has notes
$soWithNotes = \App\Models\SalesOrder::whereNotNull('notes')->where('notes', '!=', '')->first();
if ($soWithNotes) {
    echo "Found SO with notes: " . $soWithNotes->notes . PHP_EOL;
} else {
    echo "No SO with notes found." . PHP_EOL;
}
