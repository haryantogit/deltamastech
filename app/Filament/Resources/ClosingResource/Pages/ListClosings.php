<?php

namespace App\Filament\Resources\ClosingResource\Pages;

use App\Filament\Resources\ClosingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClosings extends ListRecords
{
    protected static string $resource = ClosingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
