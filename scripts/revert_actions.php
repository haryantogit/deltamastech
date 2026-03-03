<?php

$dir1 = glob('app/Filament/Resources/*.php');
$dir2 = glob('app/Filament/Resources/*/*/Tables/*.php');
$dir3 = glob('app/Filament/Resources/*/*/*.php');

$files = array_merge($dir1, $dir2, $dir3);

foreach ($files as $file) {
    if (!is_file($file))
        continue;
    $content = file_get_contents($file);

    // Check if the file has a table() method or is a Table component to avoid false positives
    if (strpos($content, '->actions([') !== false || strpos($content, '->bulkActions([') !== false) {

        // Revert my bad Replacements from Tables\Actions\ to \Filament\Actions\
        $newContent = str_replace('Tables\Actions\ActionGroup', '\Filament\Actions\ActionGroup', $content);
        $newContent = str_replace('Tables\Actions\BulkActionGroup', '\Filament\Actions\BulkActionGroup', $newContent);
        $newContent = str_replace('Tables\Actions\ViewAction', '\Filament\Actions\ViewAction', $newContent);
        $newContent = str_replace('Tables\Actions\EditAction', '\Filament\Actions\EditAction', $newContent);
        $newContent = str_replace('Tables\Actions\DeleteAction', '\Filament\Actions\DeleteAction', $newContent);
        $newContent = str_replace('Tables\Actions\DeleteBulkAction', '\Filament\Actions\DeleteBulkAction', $newContent);
        $newContent = str_replace('\Filament\\\Filament\Actions', '\Filament\Actions', $newContent); // Fix double prefix

        // If ActionGroup lacks an icon, it becomes invisible in the table, which was the user's original issue!
        // So we add ->icon('heroicon-m-ellipsis-vertical') to ActionGroup::make([...]) instances that don't have it chain-called yet.
        $newContent = preg_replace('/(\\\\Filament\\\\Actions\\\\ActionGroup::make\(\[\s*[^\]]+\]\))(?!\s*->icon)/s', "$1\n                    ->icon('heroicon-m-ellipsis-vertical')", $newContent);

        if ($newContent !== $content) {
            file_put_contents($file, $newContent);
            echo "Reverted namespaces in $file\n";
        }
    }
}
echo "Done.\n";
