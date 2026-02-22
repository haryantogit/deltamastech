<div
    style="display: flex; justify-content: flex-end; align-items: center; gap: 24px; padding-top: 16px; border-top: 1px solid #f3f4f6; margin-top: 8px;">
    <div style="text-align: right;">
        <span style="font-size: 13px; font-weight: 500; color: #6b7280;">Total yang akan dibayar</span>
        <div style="font-size: 20px; font-weight: 700; color: #111827; margin-top: 2px;">
            Rp {{ number_format((float) $amount, 0, ',', '.') }}
        </div>
    </div>
    <x-filament::button type="submit" size="lg" wire:loading.attr="disabled">
        <span wire:loading.remove>+ Tambah Pembayaran</span>
        <span wire:loading>Memproses...</span>
    </x-filament::button>
</div>