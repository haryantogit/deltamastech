<?php

namespace App\Filament\Pages\Pos;

use App\Models\Outlet;
use App\Models\PosOrder;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;

class PosOrderPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected string $view = 'filament.pages.pos.pos-order-page';

    protected static ?string $title = 'Pesanan POS';

    protected static string|null $navigationLabel = 'Pesanan';

    protected static bool $shouldRegisterNavigation = false;

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin/pos-page') => 'POS',
            'Pesanan',
        ];
    }

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            Widgets\PosOrderStatsOverview::class,
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(PosOrder::query()->with(['outlet', 'user'])->latest('transaction_date'))
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label('Nomor')
                    ->searchable()
                    ->sortable()
                    ->color('primary')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('transaction_date')
                    ->label('Tanggal Transaksi')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Nama Pelanggan')
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('outlet.name')
                    ->label('Outlet')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Kasir')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'pending' => 'Belum Diproses',
                        'completed' => 'Selesai',
                        'void' => 'Void',
                        default => $state,
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'completed' => 'success',
                        'void' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('total')
                    ->label('Tagihan')
                    ->money('IDR')
                    ->sortable()
                    ->alignEnd()
                    ->weight('bold'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('outlet_id')
                    ->label('Outlet')
                    ->options(Outlet::pluck('name', 'id'))
                    ->placeholder('Semua Outlet'),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Belum Diproses',
                        'completed' => 'Selesai',
                        'void' => 'Void',
                    ])
                    ->placeholder('Semua Status'),
            ])
            ->defaultSort('transaction_date', 'desc')
            ->striped()
            ->paginated([15, 25, 50])
            ->emptyStateHeading('Data Kosong')
            ->emptyStateDescription('Belum ada pesanan POS.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
    }
}
