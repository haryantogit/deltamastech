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

        // Match ActionGroup::make not prefixed by Tables\Actions\ or Filament\Tables\Actions\
        $newContent = preg_replace('/(?<!Tables\\\\)(?<!Actions\\\\)ActionGroup::make/i', 'Tables\Actions\ActionGroup::make', $content);
        $newContent = preg_replace('/(?<!Tables\\\\)(?<!Actions\\\\)BulkActionGroup::make/i', 'Tables\Actions\BulkActionGroup::make', $newContent);

        $newContent = preg_replace('/(?<!Tables\\\\)(?<!Actions\\\\)ViewAction::make/i', 'Tables\Actions\ViewAction::make', $newContent);
        $newContent = preg_replace('/(?<!Tables\\\\)(?<!Actions\\\\)EditAction::make/i', 'Tables\Actions\EditAction::make', $newContent);
        $newContent = preg_replace('/(?<!Tables\\\\)(?<!Actions\\\\)DeleteAction::make/i', 'Tables\Actions\DeleteAction::make', $newContent);
        $newContent = preg_replace('/(?<!Tables\\\\)(?<!Actions\\\\)DeleteBulkAction::make/i', 'Tables\Actions\DeleteBulkAction::make', $newContent);

        // Also fix any \Filament\Actions\ prefix turning into \Filament\Tables\Actions\
        $newContent = str_replace('\Filament\Actions\ActionGroup::make', '\Filament\Tables\Actions\ActionGroup::make', $newContent);
        $newContent = str_replace('\Filament\Actions\BulkActionGroup::make', '\Filament\Tables\Actions\BulkActionGroup::make', $newContent);

        // Revert any unintended replacements in use statements
        $newContent = str_replace('use Tables\Actions\Filament\Actions', 'use Filament\Actions', $newContent);

        if ($newContent !== $content) {
            file_put_contents($file, $newContent);
            echo "Fixed $file\n";
        }
    }
}
echo "Done.\n";
