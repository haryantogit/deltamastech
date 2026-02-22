<?php

namespace App\Filament\Imports;

use App\Models\Contact;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class ContactImporter extends Importer
{
    protected static ?string $model = Contact::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->label('*Nama')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('type')
                ->label('*Tipe Kontak')
                ->requiredMapping()
                ->rules(['required'])
                ->castStateUsing(function (string $state): ?string {
                    if ($state === 'Pelanggan') {
                        return 'customer';
                    }
                    if ($state === 'Vendor') {
                        return 'vendor';
                    }
                    return $state;
                }),
            ImportColumn::make('company')
                ->label('Perusahaan'),
            ImportColumn::make('email')
                ->label('Email'),
            ImportColumn::make('phone')
                ->label('Nomor Telepon'),
            ImportColumn::make('address')
                ->label('Alamat Penagihan'),
            ImportColumn::make('city')
                ->label('Kota'),
            ImportColumn::make('tax_id')
                ->label('NPWP'),
        ];
    }

    public function resolveRecord(): ?Contact
    {
        // This example will simply return a new model instance.
        // If you want to match existing records, use specific logic here.
        return new Contact();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your contact import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->failed_rows) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
