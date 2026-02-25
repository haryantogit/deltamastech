<?php

namespace App\Filament\Resources\ContactResource\Pages;

use App\Filament\Resources\ContactResource;
use App\Filament\Resources\ContactResource\Widgets\ContactStatsWidget;
use App\Filament\Resources\ContactResource\Widgets\ContactCashFlowChart;
use App\Filament\Resources\HutangResource;
use App\Filament\Resources\PiutangResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\RepeatableEntry;

class ViewContact extends ViewRecord
{
    protected static string $resource = ContactResource::class;

    public function getTitle(): \Illuminate\Contracts\Support\Htmlable|string
    {
        return 'Lihat Kontak';
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin/kontak-page') => 'Kontak',
            ContactResource::getUrl('index') => 'Daftar Kontak',
        ];
    }

    public function getMaxContentWidth(): \Filament\Support\Enums\Width|string|null
    {
        return \Filament\Support\Enums\Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('kembali')
                ->label('Kembali')
                ->color('gray')
                ->icon('heroicon-o-arrow-left')
                ->url(fn() => ContactResource::getUrl('index')),
            Actions\Action::make('hutang')
                ->label('Hutang')
                ->icon('heroicon-o-document-minus')
                ->color('danger')
                ->url(fn() => HutangResource::getUrl('create', ['supplier_id' => $this->record->id])),
            Actions\Action::make('piutang')
                ->label('Piutang')
                ->icon('heroicon-o-document-plus')
                ->color('success')
                ->url(fn() => PiutangResource::getUrl('create', ['contact_id' => $this->record->id])),
            Actions\Action::make('print')
                ->label('Print')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->action(fn() => null),
            Actions\EditAction::make()
                ->label('Ubah'),
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    protected function getHeaderWidgets(): array
    {
        $widgets = [
            \App\Filament\Resources\ContactResource\Widgets\ContactInfoWidget::class,
            \App\Filament\Resources\ContactResource\Widgets\ContactStatsWidget::class,
        ];

        $widgets[] = \App\Filament\Resources\ContactResource\Widgets\ContactCashFlowChart::class;
        $widgets[] = \App\Filament\Resources\ContactResource\Widgets\ContactMoneyFlowChart::class;

        $widgets[] = \App\Filament\Resources\ContactResource\Widgets\ContactBottomStatsWidget::class;

        return $widgets;
    }
}
