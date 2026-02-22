<?php

namespace App\Filament\Resources\ContactResource\Pages;

use App\Filament\Resources\ContactResource;
use Filament\Resources\Pages\CreateRecord;

class CreateContact extends CreateRecord
{
    protected static string $resource = ContactResource::class;
    public function getBreadcrumbs(): array
    {
        return [
            url('/admin/kontak-page') => 'Kontak',
            '#' => 'Buat Baru',
        ];
    }
}
