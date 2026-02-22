<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    public function mount(): void
    {
        parent::mount();

        // Pre-fill type from URL query parameter without wiping other defaults
        $type = request()->query('type');
        if ($type && in_array($type, ['standard', 'variant', 'manufacturing', 'bundle'])) {
            $this->data['type'] = $type;
        }
    }

    public function getMaxContentWidth(): string|null
    {
        return 'full';
    }

    private ?int $initial_warehouse_id = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->initial_warehouse_id = $data['initial_warehouse_id'] ?? null;

        // Remove from data so it doesn't try to save to products table
        unset($data['initial_warehouse_id']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->getRecord();
        $stock = (float) $record->stock;

        if ($stock > 0 && ($this->initial_warehouse_id)) {
            \App\Models\Stock::create([
                'product_id' => $record->id,
                'warehouse_id' => $this->initial_warehouse_id,
                'quantity' => $stock,
            ]);
        }
    }
}
