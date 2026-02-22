<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use App\Filament\Resources\ExpenseResource;
use Filament\Resources\Pages\ViewRecord;

class ViewExpense extends ViewRecord
{
    protected static string $resource = ExpenseResource::class;

    protected string $view = 'filament.resources.expense-resource.pages.view-expense';

    public function getTitle(): string
    {
        return 'Biaya - ' . ($this->record->remaining_amount <= 0 ? 'Lunas' : 'Belum Lunas');
    }

    public function getHeading(): string
    {
        return 'Biaya - ' . ($this->record->remaining_amount <= 0 ? 'Lunas' : 'Belum Lunas');
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin/biaya-page') => 'Biaya',
            '#' => 'Detil',
        ];
    }
}
