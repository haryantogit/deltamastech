<?php

namespace App\Filament\Resources\StockAdjustments\Schemas;

use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Schemas\Schema;

class StockAdjustmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->schema([
                // Left Column: Metadata & Notes (Span 4)
                \Filament\Schemas\Components\Grid::make(1)
                    ->columnSpan(['default' => 12, 'md' => 4])
                    ->schema([
                        \Filament\Schemas\Components\Section::make('Informasi Penyesuaian')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                \Filament\Forms\Components\TextInput::make('number')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->default(fn() => \App\Models\NumberingSetting::getNextNumber('stock_adjustment') ?? 'SA/' . date('Ymd') . '/' . str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT))
                                    ->label('Nomor')
                                    ->extraAttributes(['class' => 'font-mono']),

                                \Filament\Forms\Components\DatePicker::make('date')
                                    ->required()
                                    ->default(now())
                                    ->label('Tanggal'),

                                \Filament\Forms\Components\Select::make('warehouse_id')
                                    ->relationship('warehouse', 'name')
                                    ->required()
                                    ->label('Gudang')
                                    ->prefixIcon('heroicon-o-home-modern')
                                    ->prefixIconColor('primary'),

                                \Filament\Forms\Components\Select::make('reason')
                                    ->options([
                                        'Saldo Awal' => 'Saldo Awal',
                                        'Barang Rusak' => 'Barang Rusak',
                                        'Barang Hilang' => 'Barang Hilang',
                                        'Koreksi Stok' => 'Koreksi Stok',
                                        'Lainnya' => 'Lainnya',
                                    ])
                                    ->required()
                                    ->label('Alasan')
                                    ->prefixIcon('heroicon-o-chat-bubble-bottom-center-text')
                                    ->prefixIconColor('warning'),
                            ]),

                        \Filament\Schemas\Components\Section::make('Tambahan')
                            ->icon('heroicon-o-pencil-square')
                            ->schema([
                                \Filament\Forms\Components\Textarea::make('notes')
                                    ->label('Catatan')
                                    ->rows(3)
                                    ->placeholder('Opsional: Tambahkan catatan penyesuaian...'),
                            ]),
                    ]),

                // Right Column: Items (Span 8)
                \Filament\Schemas\Components\Section::make('Daftar Item Penyesuaian')
                    ->icon('heroicon-o-adjustments-vertical')
                    ->columnSpan(['default' => 12, 'md' => 8])
                    ->schema([
                        \Filament\Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->schema([
                                \Filament\Schemas\Components\Grid::make(12)
                                    ->schema([
                                        \Filament\Forms\Components\Select::make('product_id')
                                            ->relationship('product', 'name', modifyQueryUsing: fn($query) => $query->active())
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->label('Produk')
                                            ->columnSpan(8)
                                            ->live()
                                            ->afterStateUpdated(fn($state, Set $set) => $set('product_id', $state)),
                                        \Filament\Forms\Components\TextInput::make('quantity')
                                            ->required()
                                            ->numeric()
                                            ->step('any')
                                            ->label('Kuantitas')
                                            ->suffix(fn($get) => \App\Models\Product::find($get('product_id'))?->unit_name ?? 'pcs')
                                            ->default(0)
                                            ->live(onBlur: true)
                                            ->columnSpan(4)
                                            ->suffixAction(function (Get $get, $livewire) {
                                                $productId = $get('product_id');
                                                $warehouseId = $get('../../warehouse_id') ?? $livewire->data['warehouse_id'] ?? null;
                                                if ($productId && $warehouseId) {
                                                    $stock = \App\Models\Stock::where('product_id', $productId)
                                                        ->where('warehouse_id', $warehouseId)
                                                        ->value('quantity') ?? 0;
                                                    return \Filament\Actions\Action::make('stock')
                                                        ->label((string) $stock)
                                                        ->color($stock > 0 ? 'success' : 'danger')
                                                        ->badge()
                                                        ->disabled();
                                                }
                                                return null;
                                            }),
                                    ]),
                            ])
                            ->columnSpanFull()
                            ->addActionLabel('Tambah Item')
                            ->collapsible()
                            ->itemLabel(fn(array $state): ?string => (\App\Models\Product::find($state['product_id'])?->name ?? 'Item') . ($state['quantity'] ? " ({$state['quantity']})" : '')),
                    ])
            ]);
    }
}
