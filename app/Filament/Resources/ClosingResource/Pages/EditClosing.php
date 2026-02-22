<?php

namespace App\Filament\Resources\ClosingResource\Pages;

use App\Filament\Resources\ClosingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditClosing extends EditRecord
{
    protected static string $resource = ClosingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
