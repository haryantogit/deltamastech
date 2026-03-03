<?php
$files = array_merge(glob('app/Filament/Resources/*.php'), glob('app/Filament/Resources/*/*/Tables/*.php'), glob('app/Filament/Resources/*/*/*.php'));
foreach ($files as $file) {
    if (!is_file($file))
        continue;
    $content = file_get_contents($file);
    // Replace Bulk\Filament\Actions\ActionGroup with \Filament\Actions\BulkActionGroup
    $newContent = str_replace('Bulk\Filament\Actions\ActionGroup', '\Filament\Actions\BulkActionGroup', $content);
    // Since we appended an icon to ActionGroup, some BulkActionGroup might have been affected and don't need it. 
    // We will leave the icon as it doesn't hurt BulkActionGroup to have an icon, or it might be ignored.

    // Also, there might be other malformed ones like \Filament\Tables\Actions\BulkActionGroup::make -> we changed it to \Filament\Actions\BulkActionGroup::make

    if ($newContent !== $content) {
        file_put_contents($file, $newContent);
        echo "Fixed Bulk/Filament/Actions in " . $file . "\n";
    }
}
echo "Done.\n";
