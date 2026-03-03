<?php
$files = array_merge(glob('app/Filament/Resources/*.php'), glob('app/Filament/Resources/*/*/Tables/*.php'), glob('app/Filament/Resources/*/*/*.php'));
$errors = 0;
foreach ($files as $file) {
    if (!is_file($file))
        continue;
    $output = [];
    $ret = 0;
    exec('php -l ' . escapeshellarg($file) . ' 2>&1', $output, $ret);
    if ($ret !== 0) {
        $errors++;
        echo "Syntax error in $file:\n";
        echo implode("\n", $output) . "\n\n";
    }
}
if ($errors === 0) {
    echo "All files passed syntax check.\n";
} else {
    echo "Found $errors files with syntax errors.\n";
    exit(1);
}
