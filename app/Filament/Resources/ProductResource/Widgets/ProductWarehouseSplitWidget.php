<?php

namespace App\Filament\Resources\ProductResource\Widgets;

use Filament\Widgets\Widget as BaseWidget;
use Illuminate\Database\Eloquent\Model;

class ProductWarehouseSplitWidget extends BaseWidget
{
    protected string $view = 'filament.resources.product-resource.widgets.product-warehouse-split-widget';

    protected int|string|array $columnSpan = 1;

    public ?Model $record = null;
}
