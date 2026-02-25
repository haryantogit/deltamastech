<x-filament-panels::page>
    <div style="margin-bottom: 1.5rem; color: var(--text-color, #6b7280); font-size: 0.875rem;">
        Tentukan nomor yang digunakan untuk membuat penomoran dokumen. Nomor dibawah akan otomatis ditambah setiap
        dokumen baru dibuat.
    </div>

    <style>
        .num-setting-grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 1rem;
        }

        @media (min-width: 768px) {
            .num-setting-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 1024px) {
            .num-setting-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        .num-setting-card {
            background-color: transparent;
            border: 1px solid rgba(156, 163, 175, 0.4);
            border-radius: 0.5rem;
            padding: 1rem;
            cursor: pointer;
            text-align: center;
            position: relative;
            transition: all 0.2s;
        }

        .dark .num-setting-card {
            border-color: rgba(55, 65, 81, 1);
        }

        .num-setting-card:hover {
            border-color: var(--primary-500, #f97316);
            box-shadow: 0 0 0 1px var(--primary-500, #f97316);
        }

        .num-setting-title {
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .num-setting-preview {
            font-size: 0.875rem;
            color: var(--text-color, #6b7280);
            opacity: 0.8;
            margin-top: 0.25rem;
        }

        .num-setting-icon {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            color: var(--primary-500, #f97316);
            opacity: 0;
            transition: opacity 0.2s;
            width: 1rem;
            height: 1rem;
        }

        .num-setting-card:hover .num-setting-icon {
            opacity: 1;
        }
    </style>

    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        @foreach($groupedSettings as $groupName => $settings)
            <x-filament::section>
                <x-slot name="heading">
                    {{ $groupName }}
                </x-slot>

                <div class="num-setting-grid">
                    @foreach($settings as $setting)
                        @php
                            $paddedNumber = str_pad($setting->current_number, $setting->pad_length ?? 5, '0', STR_PAD_LEFT);
                            $previewStr = str_replace('[NUMBER]', $paddedNumber, $setting->format);
                        @endphp

                        <div wire:click="mountAction('editFormat', {{ json_encode(['id' => $setting->id, 'name' => $setting->name, 'pad_length' => $setting->pad_length ?? 5]) }})"
                            class="num-setting-card">
                            <h4 class="num-setting-title fi-text-color-heading">
                                {{ $setting->name }}
                            </h4>
                            <p class="num-setting-preview fi-text-color-subheading">
                                {{ $previewStr }}
                            </p>

                            <x-filament::icon icon="heroicon-m-pencil-square" class="num-setting-icon" />
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @endforeach

        <x-filament::section>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <!-- Toggle 1: Nomor transaksi disamakan dengan transaksi asal -->
                <div style="display: flex; align-items: flex-start; gap: 1rem;">
                    <div style="padding-top: 0.125rem;">
                        <x-filament::input.checkbox wire:model.live="is_transaction_sync_enabled"
                            id="is_transaction_sync_enabled" type="checkbox" class="fi-checkbox" />
                    </div>
                    <div>
                        <label for="is_transaction_sync_enabled"
                            style="font-size: 0.875rem; font-weight: 600; cursor: pointer; color: var(--text-color, #111827);"
                            class="dark:text-white">
                            Nomor transaksi disamakan dengan transaksi asal
                        </label>
                        <p
                            style="font-size: 0.75rem; color: var(--text-color, #6b7280); opacity: 0.8; margin-top: 0.25rem;">
                            Jika aktif, maka saat membuat tagihan dari pemesanan, nomornya akan disamakan dengan
                            pemesanan. Begitu juga untuk transaksi lainnya.
                        </p>
                    </div>
                </div>

                <hr style="border-top: 1px solid rgba(156, 163, 175, 0.2); margin: 0.5rem 0;" />

                <!-- Toggle 2: Nomor transaksi dikunci -->
                <div style="display: flex; align-items: flex-start; gap: 1rem;">
                    <div style="padding-top: 0.125rem;">
                        <x-filament::input.checkbox wire:model.live="is_numbering_locked" id="is_numbering_locked"
                            type="checkbox" class="fi-checkbox" />
                    </div>
                    <div>
                        <label for="is_numbering_locked"
                            style="font-size: 0.875rem; font-weight: 600; cursor: pointer; color: var(--text-color, #111827);"
                            class="dark:text-white">
                            Nomor transaksi dikunci supaya tidak dapat diubah
                        </label>
                        <p
                            style="font-size: 0.75rem; color: var(--text-color, #6b7280); opacity: 0.8; margin-top: 0.25rem;">
                            Jika aktif, maka nomor transaksi akan mengikuti sistem Delta Mas Tech, tidak dapat diubah
                            oleh pengguna.
                        </p>
                    </div>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>