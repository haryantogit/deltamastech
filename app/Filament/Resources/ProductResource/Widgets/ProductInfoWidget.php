<?php

namespace App\Filament\Resources\ProductResource\Widgets;

use Filament\Widgets\Widget as BaseWidget;
use Illuminate\Database\Eloquent\Model;

class ProductInfoWidget extends BaseWidget
{
    public ?Model $record = null;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.resources.product-resource.widgets.product-info-widget';
}
