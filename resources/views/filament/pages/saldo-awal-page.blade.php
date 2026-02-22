<x-filament-panels::page>


    <!-- Native Table -->
    {{ $this->table }}

    <!-- Save Button (Fixed at bottom right or after table) -->
    <div class="flex justify-end mt-4">
        <x-filament::button wire:click="save" size="lg">
            Simpan Perubahan
        </x-filament::button>
    </div>
</x-filament-panels::page>