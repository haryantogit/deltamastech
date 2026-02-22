<?php

$dir = __DIR__ . '/app/Filament/Widgets';
$files = glob($dir . '/*.php');

foreach ($files as $file) {
    if (basename($file) === 'AccountMovementWidget.php')
        continue; // Already handled

    $content = file_get_contents($file);
    if (strpos($content, 'protected static bool $isLazy = true;') === false) {
        $content = preg_replace(
            '/(class\s+\w+\s+extends\s+\w+[\s\n]*\{)/',
            "$1\n    protected static bool \$isLazy = true;\n",
            $content
        );
        file_put_contents($file, $content);
        echo "Optimized: " . basename($file) . "\n";
    }
}
