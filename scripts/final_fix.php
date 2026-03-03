<?php
/**
 * Definitive fix script for ALL mangled Filament action namespaces.
 * 
 * In Filament v5, the correct classes are ALL under \Filament\Actions\:
 *   - \Filament\Actions\ActionGroup
 *   - \Filament\Actions\BulkActionGroup
 *   - \Filament\Actions\ViewAction
 *   - \Filament\Actions\EditAction
 *   - \Filament\Actions\DeleteAction
 *   - \Filament\Actions\DeleteBulkAction
 * 
 * There is NO \Filament\Tables\Actions\ActionGroup or similar.
 */

$dirs = [
    'app/Filament/Resources/*.php',
    'app/Filament/Resources/*/*/*.php',
    'app/Filament/Resources/*/*/Tables/*.php',
    'app/Filament/Resources/*/*/*/*.php',
];

$files = [];
foreach ($dirs as $pattern) {
    $files = array_merge($files, glob($pattern));
}
$files = array_unique($files);

$fixed = 0;

foreach ($files as $file) {
    if (!is_file($file))
        continue;
    $content = file_get_contents($file);
    $original = $content;

    // Fix all mangled BulkActionGroup patterns:
    // \Filament\Tables\Actions\\Filament\Actions\BulkActionGroup  (double backslash variant)
    // \Filament\Tables\Actions\Filament\Actions\BulkActionGroup
    // Filament\Tables\Actions\BulkActionGroup  (doesn't exist)
    // Tables\Actions\BulkActionGroup  (doesn't exist)
    // Tables\Actions\Bulk\Filament\Actions\ActionGroup
    // Bulk\Filament\Actions\ActionGroup
    // \Filament\Tables\Actions\Bulk\Filament\Actions\ActionGroup
    // BulkTables\Actions\ActionGroup

    // Strategy: Use regex to catch any reference that should be BulkActionGroup
    $content = preg_replace(
        '/\\\\?Filament\\\\Tables\\\\Actions\\\\\\\\?Filament\\\\Actions\\\\BulkActionGroup/',
        '\\Filament\\Actions\\BulkActionGroup',
        $content
    );
    $content = preg_replace(
        '/\\\\?Filament\\\\Tables\\\\Actions\\\\Bulk\\\\?Filament\\\\Actions\\\\ActionGroup/',
        '\\Filament\\Actions\\BulkActionGroup',
        $content
    );
    $content = preg_replace(
        '/Bulk\\\\?Filament\\\\Actions\\\\ActionGroup/',
        '\\Filament\\Actions\\BulkActionGroup',
        $content
    );
    $content = preg_replace(
        '/BulkTables\\\\Actions\\\\ActionGroup/',
        '\\Filament\\Actions\\BulkActionGroup',
        $content
    );
    $content = preg_replace(
        '/Tables\\\\Actions\\\\BulkActionGroup(?!::)/',
        '\\Filament\\Actions\\BulkActionGroup',
        $content
    );
    $content = preg_replace(
        '/Tables\\\\Actions\\\\Bulk\\\\Filament\\\\Actions\\\\ActionGroup/',
        '\\Filament\\Actions\\BulkActionGroup',
        $content
    );

    // Fix mangled ActionGroup patterns (non-bulk):
    // \Filament\Tables\Actions\ActionGroup -> doesn't exist, should be \Filament\Actions\ActionGroup
    // Tables\Actions\ActionGroup -> should be \Filament\Actions\ActionGroup
    // But be careful not to match BulkActionGroup
    $content = preg_replace(
        '/(?<!Bulk)Tables\\\\Actions\\\\ActionGroup/',
        '\\Filament\\Actions\\ActionGroup',
        $content
    );

    // Fix mangled EditAction, ViewAction, DeleteAction patterns:
    // Tables\Actions\EditAction -> \Filament\Actions\EditAction
    // Tables\Actions\ViewAction -> \Filament\Actions\ViewAction
    // Tables\Actions\DeleteAction -> \Filament\Actions\DeleteAction
    // Tables\Actions\DeleteBulkAction -> \Filament\Actions\DeleteBulkAction
    $content = preg_replace(
        '/(?<!\\\\)Tables\\\\Actions\\\\EditAction/',
        '\\Filament\\Actions\\EditAction',
        $content
    );
    $content = preg_replace(
        '/(?<!\\\\)Tables\\\\Actions\\\\ViewAction/',
        '\\Filament\\Actions\\ViewAction',
        $content
    );
    $content = preg_replace(
        '/(?<!\\\\)Tables\\\\Actions\\\\DeleteAction/',
        '\\Filament\\Actions\\DeleteAction',
        $content
    );
    $content = preg_replace(
        '/(?<!\\\\)Tables\\\\Actions\\\\DeleteBulkAction/',
        '\\Filament\\Actions\\DeleteBulkAction',
        $content
    );

    // Clean up double-prefix: \\Filament\\Filament -> \Filament
    $content = str_replace('\\Filament\\Filament\\', '\\Filament\\', $content);

    // Clean up triple backslashes: \\\Filament -> \Filament  
    $content = preg_replace('/\\\\{2,}Filament/', '\\Filament', $content);

    if ($content !== $original) {
        file_put_contents($file, $content);
        $fixed++;
        echo "Fixed: $file\n";
    }
}

echo "\nFixed $fixed files.\n";

// Now run syntax check on all files
echo "\nRunning syntax check...\n";
$errors = 0;
foreach ($files as $f) {
    if (!is_file($f))
        continue;
    $output = [];
    $ret = 0;
    exec('php -l ' . escapeshellarg($f) . ' 2>&1', $output, $ret);
    if ($ret !== 0) {
        $errors++;
        echo "  SYNTAX ERROR: $f\n";
        foreach ($output as $line) {
            echo "    $line\n";
        }
    }
}

if ($errors === 0) {
    echo "\nAll files passed syntax check!\n";
} else {
    echo "\nFound $errors files with syntax errors.\n";
}
