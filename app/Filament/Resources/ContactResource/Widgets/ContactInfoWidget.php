<?php

namespace App\Filament\Resources\ContactResource\Widgets;

use Filament\Widgets\Widget as BaseWidget;
use Illuminate\Database\Eloquent\Model;

class ContactInfoWidget extends BaseWidget
{
    public ?Model $record = null;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.resources.contact-resource.widgets.contact-info-widget';
}
