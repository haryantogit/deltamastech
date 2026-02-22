<?php

namespace App\Filament\Widgets;

use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Widgets\Widget;

class DashboardFilter extends Widget implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.widgets.dashboard-filter';

    protected static ?int $sort = -1;

    protected int|string|array $columnSpan = 'full';

    public array $filters = [];

    public function mount(): void
    {
        $this->filters = [
            'startDate' => now()->startOfYear()->toDateString(),
            'endDate' => now()->endOfYear()->toDateString(),
        ];

        $this->form->fill($this->filters);
    }

    public function updatedFilters(): void
    {
        $this->dispatch('filtersUpdated', filters: $this->filters);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                \Filament\Forms\Components\DatePicker::make('startDate')
                    ->label('Tanggal Mulai')
                    ->live()
                    ->afterStateUpdated(fn() => $this->updatedFilters()),
                \Filament\Forms\Components\DatePicker::make('endDate')
                    ->label('Tanggal Selesai')
                    ->live()
                    ->afterStateUpdated(fn() => $this->updatedFilters()),
            ])
            ->columns(2)
            ->statePath('filters');
    }
}
