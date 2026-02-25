<?php

namespace App\Filament\Pages\Pos;

use App\Models\FavoriteProduct;
use App\Models\Product;
use App\Models\Outlet;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;

class FavoriteProductPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-star';

    protected string $view = 'filament.pages.pos.favorite-product-page';

    protected static string|null $navigationLabel = 'Produk Favorit';
    protected static ?string $title = 'Produk Favorit';

    protected static bool $shouldRegisterNavigation = false;

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin/pos-page') => 'POS',
            'Produk Favorit',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('addFavorite')
                ->label('Tambah Produk Favorit')
                ->slideOver()
                ->form([
                    Select::make('product_id')
                        ->label('Pilih Produk')
                        ->options(Product::pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                    Select::make('outlet_id')
                        ->label('Pilih Outlet (Opsional)')
                        ->options(Outlet::pluck('name', 'id'))
                        ->placeholder('Semua Outlet (Global)')
                        ->searchable(),
                ])
                ->action(function (array $data) {
                    FavoriteProduct::create([
                        'product_id' => $data['product_id'],
                        'outlet_id' => $data['outlet_id'] ?? null,
                    ]);

                    Notification::make()
                        ->title('Berhasil ditambahkan ke favorit')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function getViewData(): array
    {
        return [
            'favorites' => FavoriteProduct::with(['product', 'outlet'])->get(),
        ];
    }

    public function deleteFavorite($id)
    {
        FavoriteProduct::find($id)?->delete();

        Notification::make()
            ->title('Produk dihapus dari favorit')
            ->success()
            ->send();
    }
}
