<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                <span>{{ $this->getTitle() }}</span>
                <x-filament::button tag="a"
                    href="{{ \App\Filament\Resources\FixedAssetResource::getUrl('view', ['record' => $record]) }}"
                    color="warning" size="sm">
                    Kembali
                </x-filament::button>
            </div>
        </x-slot>

        {{-- Remove headerEnd slot as it's not working --}}

        <form wire:submit="submit" class="space-y-6">
            {{-- Render all form fields (account selects are hidden in schema) --}}
            {{ $this->form }}

            {{-- Journal Entry Section --}}
            <div style="margin-top: 3rem;">
                <h3 style="font-size: 1.125rem; font-weight: 700; color: #111827; margin-bottom: 1.5rem;">
                    Entri Journal
                </h3>

                @php
                    $price = (float) ($this->data['disposal_price'] ?? 0);
                    $bookValue = (float) $record->purchase_price - (float) $record->accumulated_depreciation_value;
                    $diff = $price - $bookValue;
                @endphp

                <div
                    style="margin-top: 1.5rem; border: 1px solid #e5e7eb; border-radius: 0.75rem; overflow: hidden; background-color: white; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);">
                    <table style="width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 0.875rem;">
                        <thead style="background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                            <tr>
                                <th
                                    style="text-align: left; padding: 1rem 1.25rem; font-weight: 700; color: #374151; width: 25%;">
                                    Nama</th>
                                <th
                                    style="text-align: left; padding: 1rem 1.25rem; font-weight: 700; color: #374151; width: 45%;">
                                    Akun</th>
                                <th
                                    style="text-align: right; padding: 1rem 1.25rem; font-weight: 700; color: #374151; width: 15%;">
                                    Debit</th>
                                <th
                                    style="text-align: right; padding: 1rem 1.25rem; font-weight: 700; color: #374151; width: 15%;">
                                    Kredit</th>
                            </tr>
                        </thead>
                        <tbody style="color: #4b5563;">
                            {{-- Row 1: Asset Account (Credit) --}}
                            <tr style="border-bottom: 1px solid #f3f4f6;">
                                <td style="padding: 1.25rem; font-weight: 600; color: #111827;">Biaya Akuisisi</td>
                                <td style="padding: 1.25rem;">
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <span
                                            style="display: inline-flex; align-items: center; padding: 0.25rem 0.5rem; border-radius: 0.375rem; font-size: 0.75rem; font-weight: 700; background-color: #fff7ed; color: #c2410c; border: 1px solid #fdba74;">
                                            {{ $record->assetAccount?->code }}
                                        </span>
                                        <span
                                            style="color: #111827; font-weight: 500;">{{ $record->assetAccount?->name }}</span>
                                    </div>
                                </td>
                                <td style="padding: 1.25rem; text-align: right; color: #d1d5db;">-</td>
                                <td
                                    style="padding: 1.25rem; text-align: right; font-weight: 800; color: #111827; font-variant-numeric: tabular-nums; font-size: 1rem;">
                                    {{ number_format($record->purchase_price, 0, ',', '.') }}
                                </td>
                            </tr>

                            {{-- Row 2: Accumulated Depreciation (Debit) --}}
                            <tr style="border-bottom: 1px solid #f3f4f6;">
                                <td style="padding: 1.25rem; font-weight: 600; color: #111827;">Akumulasi Penyusutan
                                </td>
                                <td style="padding: 1.25rem;">
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <span
                                            style="display: inline-flex; align-items: center; padding: 0.25rem 0.5rem; border-radius: 0.375rem; font-size: 0.75rem; font-weight: 700; background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;">
                                            {{ $record->accumulatedDepreciationAccount?->code }}
                                        </span>
                                        <span
                                            style="color: #111827; font-weight: 500;">{{ $record->accumulatedDepreciationAccount?->name }}</span>
                                    </div>
                                </td>
                                <td
                                    style="padding: 1.25rem; text-align: right; font-weight: 800; color: #111827; font-variant-numeric: tabular-nums; font-size: 1rem;">
                                    {{ number_format($record->accumulated_depreciation_value, 0, ',', '.') }}
                                </td>
                                <td style="padding: 1.25rem; text-align: right; color: #d1d5db;">-</td>
                            </tr>

                            {{-- Row 3: Receiving Account (Debit) --}}
                            @if($price > 0)
                                <tr style="border-bottom: 1px solid #f3f4f6;">
                                    <td style="padding: 1.25rem; font-weight: 600; color: #111827;">Penerimaan (Kas & Bank)
                                    </td>
                                    <td style="padding: 1rem 1.25rem;">
                                        <div style="max-width: 380px;">
                                            <x-filament::input.wrapper size="sm">
                                                <x-filament::input.select wire:model.live="data.received_account_id">
                                                    <option value="">Pilih akun penerimaan</option>
                                                    @foreach(\App\Models\Account::whereIn('category', ['kas', 'bank'])->get() as $account)
                                                        <option value="{{ $account->id }}">{{ $account->code }} -
                                                            {{ $account->name }}
                                                        </option>
                                                    @endforeach
                                                </x-filament::input.select>
                                            </x-filament::input.wrapper>
                                        </div>
                                    </td>
                                    <td
                                        style="padding: 1.25rem; text-align: right; font-weight: 800; color: #111827; font-variant-numeric: tabular-nums; font-size: 1rem;">
                                        {{ number_format($price, 0, ',', '.') }}
                                    </td>
                                    <td style="padding: 1.25rem; text-align: right; color: #d1d5db;">-</td>
                                </tr>
                            @endif

                            {{-- Row 4: Loss/Gain --}}
                            <tr>
                                <td style="padding: 1.25rem; font-weight: 600; color: #111827;">
                                    {{ $diff < 0 ? 'Kerugian' : 'Keuntungan' }}
                                    Pelepasan
                                </td>
                                <td style="padding: 1rem 1.25rem;">
                                    <div style="max-width: 380px;">
                                        <x-filament::input.wrapper size="sm">
                                            <x-filament::input.select wire:model.live="data.loss_gain_account_id">
                                                <option value="">Pilih akun
                                                    {{ $diff < 0 ? 'biaya/rugi' : 'pendapatan/laba' }}
                                                </option>
                                                @php
                                                    $query = \App\Models\Account::query();
                                                    if ($diff < 0) {
                                                        $query->where('code', 'LIKE', '6-%')->orWhere('code', 'LIKE', '7-%')->orWhere('code', 'LIKE', '8-%');
                                                    } else {
                                                        $query->where('code', 'LIKE', '4-%')->orWhere('code', 'LIKE', '7-%');
                                                    }
                                                @endphp
                                                @foreach($query->get() as $account)
                                                    <option value="{{ $account->id }}">{{ $account->code }} -
                                                        {{ $account->name }}
                                                    </option>
                                                @endforeach
                                            </x-filament::input.select>
                                        </x-filament::input.wrapper>
                                    </div>
                                </td>
                                <td
                                    style="padding: 1.25rem; text-align: right; font-weight: 800; color: {{ $diff < 0 ? '#dc2626' : '#111827' }}; font-variant-numeric: tabular-nums; font-size: 1rem;">
                                    {{ $diff < 0 ? number_format(abs($diff), 0, ',', '.') : '-' }}
                                </td>
                                <td
                                    style="padding: 1.25rem; text-align: right; font-weight: 800; color: {{ $diff > 0 ? '#16a34a' : '#111827' }}; font-variant-numeric: tabular-nums; font-size: 1rem;">
                                    {{ $diff > 0 ? number_format(abs($diff), 0, ',', '.') : '-' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; width: 100%; margin-top: 1.5rem;">
                <x-filament::button type="submit" size="lg">
                    Lepas/Jual
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>
</x-filament-panels::page>