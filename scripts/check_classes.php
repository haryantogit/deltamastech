<?php
require __DIR__ . '/vendor/autoload.php';

echo "Filament\Actions\Action: " . (class_exists('Filament\Actions\Action') ? 'exists' : 'missing') . PHP_EOL;
echo "Filament\Tables\Actions\Action: " . (class_exists('Filament\Tables\Actions\Action') ? 'exists' : 'missing') . PHP_EOL;
echo "Filament\Tables\Actions\ViewAction: " . (class_exists('Filament\Tables\Actions\ViewAction') ? 'exists' : 'missing') . PHP_EOL;
echo "Filament\Tables\Actions\EditAction: " . (class_exists('Filament\Tables\Actions\EditAction') ? 'exists' : 'missing') . PHP_EOL;
