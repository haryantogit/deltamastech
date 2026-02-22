@php
    $record = $getRecord();
@endphp

<div class="w-full">
    <livewire:app.filament.resources.fixed-asset-resource.widgets.fixed-asset-transactions-widget :record="$record" />
</div>