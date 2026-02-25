<x-filament-panels::page>
    @php
        $fmtSigned = function ($num) {
            if ($num == 0)
                return '0';
            $prefix = $num < 0 ? '(' : '';
            $suffix = $num < 0 ? ')' : '';
            return $prefix . number_format(abs($num), 0, ',', '.') . $suffix;
        };

        $today = now()->format('d/m/Y');
    @endphp

    {{-- =================== ARUS KAS TABLE =================== --}}
    <x-filament::section>
        <x-slot name="heading">
            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                <span style="color: #16a34a; font-weight: 700; font-size: 15px;">Arus Kas</span>
                <span style="font-size: 13px; color: #94a3b8;">{{ $today }}</span>
            </div>
        </x-slot>

        <table style="width: 100%; border-collapse: collapse;">
            @foreach($sections as $sectionName => $items)
                {{-- Section Header --}}
                <tr>
                    <td colspan="2" style="padding: 16px 8px 8px; font-size: 14px; font-weight: 700;"
                        class="text-gray-800 dark:text-gray-200">{{ $sectionName }}</td>
                </tr>

                {{-- Section Rows --}}
                @foreach($items as $label => $value)
                    <tr style="border-bottom: 1px solid rgba(128,128,128,0.1);">
                        <td style="padding: 8px 8px 8px 24px; font-size: 13px;" class="text-gray-600 dark:text-gray-400">
                            {{ $label }}</td>
                        <td style="padding: 8px; text-align: right; font-size: 13px; font-weight: 500; font-variant-numeric: tabular-nums;"
                            class="{{ $value < 0 ? 'text-red-500' : ($value > 0 ? 'text-gray-700 dark:text-gray-300' : 'text-gray-400 dark:text-gray-500') }}">
                            {{ $fmtSigned($value) }}</td>
                    </tr>
                @endforeach

                {{-- Section Total --}}
                @php
                    $totalKey = 'Arus kas bersih dari ' . strtolower($sectionName);
                    $totalValue = $totals[$totalKey] ?? 0;
                @endphp
                <tr style="border-top: 2px solid rgba(128,128,128,0.2);">
                    <td style="padding: 10px 8px; font-size: 13px; font-weight: 700;"
                        class="text-gray-900 dark:text-gray-100">{{ ucfirst($totalKey) }}</td>
                    <td style="padding: 10px 8px; text-align: right; font-size: 13px; font-weight: 700; font-variant-numeric: tabular-nums;"
                        class="text-gray-900 dark:text-gray-100">{{ $fmtSigned($totalValue) }}</td>
                </tr>

                <tr>
                    <td colspan="2" style="padding: 4px;"></td>
                </tr>
            @endforeach

            {{-- GRAND TOTAL: Arus Kas Bersih --}}
            @php $netCash = $totals['Arus kas bersih'] ?? 0; @endphp
            <tr style="border-top: 3px solid rgba(22,163,106,0.3); background: rgba(22,163,106,0.08);">
                <td style="padding: 16px 8px; font-size: 15px; font-weight: 900;" class="text-gray-900 dark:text-white">
                    Arus kas bersih</td>
                <td style="padding: 16px 8px; text-align: right; font-size: 15px; font-weight: 900; font-variant-numeric: tabular-nums;"
                    class="text-gray-900 dark:text-white">{{ $fmtSigned($netCash) }}</td>
            </tr>
        </table>
    </x-filament::section>

    <div style="height: 24px;"></div>

    {{-- =================== KAS DAN SETARA KAS =================== --}}
    <x-filament::section>
        <x-slot name="heading">
            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                <span style="color: #3b82f6; font-weight: 700; font-size: 15px;">Kas dan Setara Kas</span>
                <span style="font-size: 13px; color: #94a3b8;">{{ $today }}</span>
            </div>
        </x-slot>

        <table style="width: 100%; border-collapse: collapse;">
            @foreach($cash_equivalents as $label => $value)
                @if($loop->last)
                    {{-- Last row = Perubahan kas (bold total) --}}
                    <tr style="border-top: 2px solid rgba(128,128,128,0.2);">
                        <td style="padding: 10px 8px; font-size: 13px; font-weight: 700;"
                            class="text-gray-900 dark:text-gray-100">{{ $label }}</td>
                        <td style="padding: 10px 8px; text-align: right; font-size: 13px; font-weight: 700; font-variant-numeric: tabular-nums;"
                            class="text-gray-900 dark:text-gray-100">{{ $fmtSigned($value) }}</td>
                    </tr>
                @else
                    <tr style="border-bottom: 1px solid rgba(128,128,128,0.1);">
                        <td style="padding: 8px 8px 8px 24px; font-size: 13px;" class="text-gray-600 dark:text-gray-400">
                            {{ $label }}</td>
                        <td style="padding: 8px; text-align: right; font-size: 13px; font-weight: 500; font-variant-numeric: tabular-nums;"
                            class="{{ $value < 0 ? 'text-red-500' : ($value > 0 ? 'text-gray-700 dark:text-gray-300' : 'text-gray-400 dark:text-gray-500') }}">
                            {{ $fmtSigned($value) }}</td>
                    </tr>
                @endif
            @endforeach
        </table>
    </x-filament::section>
</x-filament-panels::page>