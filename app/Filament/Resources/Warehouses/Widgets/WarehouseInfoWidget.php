<?php

namespace App\Filament\Resources\Warehouses\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;

class WarehouseInfoWidget extends Widget
{
    protected string $view = 'filament.resources.warehouses.widgets.warehouse-info-widget';

    public ?Model $record = null;

    protected int|string|array $columnSpan = 'full';
}
