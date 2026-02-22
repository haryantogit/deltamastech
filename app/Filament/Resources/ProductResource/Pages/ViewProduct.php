<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    // infolist moved to Resource
    public function getTitle(): \Illuminate\Contracts\Support\Htmlable|string
    {
        return 'Lihat Produk';
    }


    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('back')
                ->label('Kembali')
                ->color('gray')
                ->url(fn() => ProductResource::getUrl('index')),
            \Filament\Actions\Action::make('print')
                ->label('Print Info Produk')
                ->icon('heroicon-o-printer')
                ->action(fn() => null)
                ->extraAttributes(['onclick' => 'window.print(); return false;']),
            EditAction::make()
                ->label('Ubah'),
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 3;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\ProductResource\Widgets\ProductStatsOverview::class,
            \App\Filament\Resources\ProductResource\Widgets\ProductInfoWidget::class,
        ];
    }

}
