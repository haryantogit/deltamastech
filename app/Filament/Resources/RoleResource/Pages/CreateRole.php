<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function afterCreate(): void
    {
        $data = $this->form->getRawState();
        $record = $this->record;

        $allIds = [];
        $prefixes = ['permissions_', 'hub_', 'sub_', 'globals_'];
        foreach ($data as $key => $ids) {
            $isPermissionField = false;
            foreach ($prefixes as $prefix) {
                if (\Illuminate\Support\Str::startsWith($key, $prefix)) {
                    $isPermissionField = true;
                    break;
                }
            }
            if ($isPermissionField && is_array($ids)) {
                $allIds = array_merge($allIds, $ids);
            }
        }

        $record->permissions()->sync($allIds);
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\Pengaturan::getUrl() => 'Pengaturan',
            $this->getResource()::getUrl('index') => 'Peran',
            'Buat',
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
