<?php

namespace App\Filament\Pages;

use App\Models\ProductionOrder;
use App\Models\Product;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;

class ProductionReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static string|\UnitEnum|null $navigationGroup = null;
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $navigationLabel = 'Laporan Produksi';
    protected static ?string $title = 'Laporan Produksi';
    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.production-report';

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            url('/admin/produksi-page') => 'Produksi',
            'Laporan Produksi',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('cetak')
                ->label('Cetak')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->action(fn() => $this->js('window.print()')),
            \Filament\Actions\Action::make('kembali')
                ->label('Kembali')
                ->color('gray')
                ->outlined()
                ->size('sm')
                ->icon('heroicon-o-arrow-left')
                ->url(url('/admin/produksi-page')),
        ];
    }
    public function table(Table $table): Table
    {
        return $table
            ->query(ProductionOrder::query()->where('status', 'Done'))
            ->columns([
                TextColumn::make('number')
                    ->label('Nomor Produksi')
                    ->searchable(),
                TextColumn::make('transaction_date')
                    ->label('Tanggal')
                    ->date(),
                TextColumn::make('product.name')
                    ->label('Produk Hasil'),
                TextColumn::make('quantity')
                    ->label('Jumlah')
                    ->numeric(),
                TextColumn::make('total_cost')
                    ->label('Total Biaya')
                    ->money('IDR'),
                TextColumn::make('cost_per_unit')
                    ->label('HPP Per Unit')
                    ->getStateUsing(fn($record) => $record->quantity > 0 ? $record->total_cost / $record->quantity : 0)
                    ->money('IDR'),
            ])
            ->defaultSort('transaction_date', 'desc');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Future production stats widgets can go here
        ];
    }
}
