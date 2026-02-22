<?php

namespace App\Filament\Resources\FixedAssetResource\Pages;

use App\Filament\Resources\FixedAssetResource;
use Filament\Resources\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Schemas\Schema;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Placeholder;
use App\Models\Product;

class DisposeFixedAsset extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = FixedAssetResource::class;

    protected string $view = 'filament.resources.fixed-asset-resource.pages.dispose-fixed-asset';

    public $record;
    public ?array $data = [];

    public function mount($record)
    {
        $this->record = Product::findOrFail($record);

        $this->form->fill([
            'disposal_date' => now()->format('Y-m-d'),
            'disposal_price' => 0,
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Grid::make(3)
                    ->schema([
                        DatePicker::make('disposal_date')
                            ->label('Tanggal Pelepasan')
                            ->required(),
                        Placeholder::make('purchase_date_display')
                            ->label('Tanggal Pembelian')
                            ->content(fn() => $this->record->purchase_date instanceof \Carbon\Carbon ? $this->record->purchase_date->format('d/m/Y') : ($this->record->purchase_date ?? '-')),
                        Placeholder::make('last_depreciation_display')
                            ->label('Penyusutan Terakhir')
                            ->content(fn() => $this->record->fixedAssetDepreciations()->latest('period')->first()?->period ?? '-'),
                    ]),
                TextInput::make('disposal_price')
                    ->label('Harga Penjualan/Pelepasan')
                    ->numeric()
                    ->prefix('Rp')
                    ->live()
                    ->required()
                    ->columnSpanFull(),
                Placeholder::make('purchase_price_display')
                    ->label('Harga Beli')
                    ->content(fn() => number_format((float) $this->record->purchase_price, 0, ',', '.')),
                TextInput::make('reference')
                    ->label('Referensi')
                    ->placeholder('Referensi')
                    ->columnSpanFull(),

                Select::make('received_account_id')
                    ->label('')
                    ->relationship('creditAccount', 'name', fn($query) => $query->whereIn('category', ['kas', 'bank']))
                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} - {$record->name}")
                    ->searchable(['code', 'name'])
                    ->required()
                    ->dehydrated()
                    ->hidden(),

                Select::make('loss_gain_account_id')
                    ->label('')
                    ->relationship('assetAccount', 'name', fn($query) => $query->where(fn($q) => $q->where('code', 'LIKE', '6-%')->orWhere('code', 'LIKE', '7-%')->orWhere('code', 'LIKE', '8-%')))
                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} - {$record->name}")
                    ->searchable(['code', 'name'])
                    ->required()
                    ->dehydrated()
                    ->hidden(),
            ])
            ->statePath('data');
    }

    public function submit()
    {
        $data = $this->form->getState();

        $purchasePrice = (float) $this->record->purchase_price;
        $accumDepr = (float) $this->record->accumulated_depreciation_value;
        $disposalPrice = (float) $data['disposal_price'];

        $bookValue = $purchasePrice - $accumDepr;
        $gainLoss = $disposalPrice - $bookValue;

        $this->record->update([
            'status' => 'disposed',
            'disposal_date' => $data['disposal_date'],
            'disposal_price' => $data['disposal_price'],
        ]);

        // Logic for Journal Entry should be added here later to make it fully functional
        // But for now, we follow the UI requirements.

        \Filament\Notifications\Notification::make()
            ->title('Berhasil')
            ->body("Aset {$this->record->name} berhasil dilepas/jual.")
            ->success()
            ->send();

        return redirect()->to(FixedAssetResource::getUrl('index'));
    }

    public function getTitle(): string
    {
        return "Lepas/Jual {$this->record->name} {$this->record->sku}";
    }
}
