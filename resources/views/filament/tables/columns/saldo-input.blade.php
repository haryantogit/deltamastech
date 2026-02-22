<div class="px-2 py-1">
    <x-filament::input.wrapper>
        <x-filament::input type="text" wire:model.live="balances.{{ $getRecord()->id }}.{{ $getName() }}"
            class="text-right font-mono" x-mask:dynamic="$money($input)" placeholder="0" />
    </x-filament::input.wrapper>
</div>