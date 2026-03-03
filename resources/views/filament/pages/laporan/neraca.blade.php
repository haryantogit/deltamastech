<x-filament-panels::page>
    @php
        $fmtSigned = function ($num) {
            if ($num == 0)
                return '0';
            $prefix = $num < 0 ? '(' : '';
            $suffix = $num < 0 ? ')' : '';
            return $prefix . number_format(abs($num), 0, ',', '.') . $suffix;
        };

        $thStyle = 'padding:10px 8px;font-size:11px;font-weight:700;text-align:right;';
        $colgroupHtml = '<colgroup><col style="width:350px">';
        foreach ($periods as $p) {
            $colgroupHtml .= '<col style="width:130px">';
        }
        $colgroupHtml .= '</colgroup>';
    @endphp

    {{-- Filters are hidden/moved to simplify layout --}}

    {{-- =================== ASET TABLE =================== --}}
    <x-filament::section class="-mt-4">
        <x-slot name="heading">
            <span style="color: #3b82f6; font-weight: 700; font-size: 15px;">Aset</span>
        </x-slot>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; table-layout: fixed; min-width: {{ count($periods) * 130 + 350 }}px;">
                {!! $colgroupHtml !!}
                <!-- Header Periode -->
                <thead>
                    <tr style="background: rgba(148, 163, 184, 0.15); border-bottom: 2px solid rgba(128,128,128,0.2);">
                        <th style="padding:10px 8px;font-size:11px;font-weight:700;text-align:left;" class="text-gray-400 dark:text-gray-500"></th>
                        @foreach ($periods as $period)
                            <th style="{{ $thStyle }}" class="text-gray-500 dark:text-gray-400">
                                {{ $period['label'] }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ([$kasBank, $piutang, $persediaan, $aktivaLancarLain] as $sectionData)
                        @foreach ($sectionData['sections'] as $section)
                        @if (!in_array(trim($section['label']), ['Kas & Bank', 'Piutang Tambahan', 'Piutang', 'Persediaan', 'Aktiva Lancar Lain', 'Aktiva Tetap', 'Kewajiban Jangka Pendek', 'Kewajiban Jangka Panjang', 'Ekuitas', 'Liabilitas', 'Liability', 'Equity', 'Assets', 'Asset', 'Aset', 'Kewajiban']))
                            <tr>
                                <td colspan="{{ count($periods) + 1 }}" style="padding: 16px 8px 8px; font-size: 14px; font-weight: 600;"
                                    class="text-gray-800 dark:text-gray-200">{{ $section['label'] }}</td>
                            </tr>
                        @endif
                            @foreach ($section['rows'] as $row)
                                <tr style="border-bottom: 1px solid rgba(128,128,128,0.1);">
                                    <td style="padding: 8px 8px 8px 24px; font-size: 13px;" class="text-gray-600 dark:text-gray-400">
                                        {{ $row['code'] }} {{ $row['name'] }}</td>
                                    @foreach ($periods as $idx => $period)
                                        <td style="padding: 8px; text-align: right; font-size: 13px; font-weight: 500; font-variant-numeric: tabular-nums;"
                                            class="{{ $row['balances'][$idx] < 0 ? 'text-red-500' : 'text-gray-700 dark:text-gray-300' }}">
                                            {{ $fmtSigned($row['balances'][$idx]) }}</td>
                                    @endforeach
                                </tr>
                                @foreach ($row['children'] as $child)
                                    <tr style="border-bottom: 1px solid rgba(128,128,128,0.05);" class="bg-gray-50/50 dark:bg-white/[0.02]">
                                        <td style="padding: 5px 8px 5px 48px; font-size: 12px;" class="text-gray-400 dark:text-gray-500">
                                            {{ $child['code'] }} {{ $child['name'] }}</td>
                                        @foreach ($periods as $idx => $period)
                                            <td style="padding: 5px 8px; text-align: right; font-size: 12px; font-variant-numeric: tabular-nums;"
                                                class="{{ $child['balances'][$idx] < 0 ? 'text-red-400' : 'text-gray-400 dark:text-gray-500' }}">
                                                {{ $fmtSigned($child['balances'][$idx]) }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            @endforeach
                            <tr style="border-top: 2px solid rgba(128,128,128,0.2);">
                                <td style="padding: 10px 8px; font-size: 13px; font-weight: 700;"
                                    class="text-gray-900 dark:text-gray-100">Total {{ $section['label'] }}</td>
                                @foreach ($periods as $idx => $period)
                                    <td style="padding: 10px 8px; text-align: right; font-size: 13px; font-weight: 700; font-variant-numeric: tabular-nums;"
                                        class="text-gray-900 dark:text-gray-100">{{ $fmtSigned($section['totals'][$idx]) }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach

                    {{-- Total Aset Lancar --}}
                    <tr style="border-top: 3px solid rgba(59,130,246,0.3); background: rgba(59,130,246,0.08);">
                        <td style="padding: 12px 8px; font-size: 14px; font-weight: 800;" class="text-gray-900 dark:text-white">
                            Total Aset Lancar</td>
                        @foreach ($periods as $idx => $period)
                            <td style="padding: 12px 8px; text-align: right; font-size: 14px; font-weight: 800; font-variant-numeric: tabular-nums;"
                                class="text-gray-900 dark:text-white">{{ $fmtSigned($totalAsetLancar[$idx]) }}</td>
                        @endforeach
                    </tr>

                    <tr>
                        <td colspan="{{ count($periods) + 1 }}" style="padding: 4px;"></td>
                    </tr>

                    {{-- Aset Tetap --}}
                    @foreach ($asetTetap['sections'] as $section)
                        @if (!in_array(trim($section['label']), ['Kas & Bank', 'Piutang Tambahan', 'Piutang', 'Persediaan', 'Aktiva Lancar Lain', 'Aktiva Tetap', 'Kewajiban Jangka Pendek', 'Kewajiban Jangka Panjang', 'Ekuitas', 'Liabilitas', 'Liability', 'Equity', 'Assets', 'Asset', 'Aset', 'Kewajiban']))
                            <tr>
                                <td colspan="{{ count($periods) + 1 }}" style="padding: 16px 8px 8px; font-size: 14px; font-weight: 600;"
                                    class="text-gray-800 dark:text-gray-200">{{ $section['label'] }}</td>
                            </tr>
                        @endif
                        @foreach ($section['rows'] as $row)
                            <tr style="border-bottom: 1px solid rgba(128,128,128,0.1);">
                                <td style="padding: 8px 8px 8px 24px; font-size: 13px;" class="text-gray-600 dark:text-gray-400">
                                    {{ $row['code'] }} {{ $row['name'] }}</td>
                                @foreach ($periods as $idx => $period)
                                    <td style="padding: 8px; text-align: right; font-size: 13px; font-weight: 500; font-variant-numeric: tabular-nums;"
                                        class="{{ $row['balances'][$idx] < 0 ? 'text-red-500' : 'text-gray-700 dark:text-gray-300' }}">
                                        {{ $fmtSigned($row['balances'][$idx]) }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                        <tr style="border-top: 2px solid rgba(128,128,128,0.2);">
                            <td style="padding: 10px 8px; font-size: 13px; font-weight: 700;"
                                class="text-gray-900 dark:text-gray-100">Total {{ $section['label'] }}</td>
                            @foreach ($periods as $idx => $period)
                                <td style="padding: 10px 8px; text-align: right; font-size: 13px; font-weight: 700; font-variant-numeric: tabular-nums;"
                                    class="text-gray-900 dark:text-gray-100">{{ $fmtSigned($section['totals'][$idx]) }}</td>
                            @endforeach
                        </tr>
                    @endforeach

                    {{-- Depresiasi --}}
                    @foreach ($depresiasi['sections'] as $section)
                        @if (!in_array(trim($section['label']), ['Kas & Bank', 'Piutang Tambahan', 'Piutang', 'Persediaan', 'Aktiva Lancar Lain', 'Aktiva Tetap', 'Kewajiban Jangka Pendek', 'Kewajiban Jangka Panjang', 'Ekuitas', 'Liabilitas', 'Liability', 'Equity', 'Assets', 'Asset', 'Aset', 'Kewajiban']))
                            <tr>
                                <td colspan="{{ count($periods) + 1 }}" style="padding: 16px 8px 8px; font-size: 14px; font-weight: 600;"
                                    class="text-gray-800 dark:text-gray-200">{{ $section['label'] }}</td>
                            </tr>
                        @endif
                        @foreach ($section['rows'] as $row)
                            <tr style="border-bottom: 1px solid rgba(128,128,128,0.1);">
                                <td style="padding: 8px 8px 8px 24px; font-size: 13px;" class="text-gray-600 dark:text-gray-400">
                                    {{ $row['code'] }} {{ $row['name'] }}</td>
                                @foreach ($periods as $idx => $period)
                                    <td style="padding: 8px; text-align: right; font-size: 13px; font-weight: 500; font-variant-numeric: tabular-nums;"
                                        class="{{ $row['balances'][$idx] < 0 ? 'text-red-500' : 'text-gray-700 dark:text-gray-300' }}">
                                        {{ $fmtSigned($row['balances'][$idx]) }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                        <tr style="border-top: 2px solid rgba(128,128,128,0.2);">
                            <td style="padding: 10px 8px; font-size: 13px; font-weight: 700;"
                                class="text-gray-900 dark:text-gray-100">Total {{ $section['label'] }}</td>
                            @foreach ($periods as $idx => $period)
                                <td style="padding: 10px 8px; text-align: right; font-size: 13px; font-weight: 700; font-variant-numeric: tabular-nums;"
                                    class="text-gray-900 dark:text-gray-100">{{ $fmtSigned($section['totals'][$idx]) }}</td>
                            @endforeach
                        </tr>
                    @endforeach

                    {{-- Lainnya --}}
                    <tr>
                        <td colspan="{{ count($periods) + 1 }}" style="padding: 16px 8px 8px; font-size: 14px; font-weight: 600;"
                            class="text-gray-800 dark:text-gray-200">Lainnya</td>
                    </tr>
                    <tr style="border-top: 2px solid rgba(128,128,128,0.2);">
                        <td style="padding: 10px 8px; font-size: 13px; font-weight: 700;"
                            class="text-gray-900 dark:text-gray-100">Total Lainnya</td>
                        @foreach ($periods as $idx => $period)
                            <td style="padding: 10px 8px; text-align: right; font-size: 13px; font-weight: 700;"
                                class="text-gray-900 dark:text-gray-100">-</td>
                        @endforeach
                    </tr>

                    {{-- GRAND TOTAL ASET --}}
                    <tr style="border-top: 3px solid #3b82f6; background: rgba(59,130,246,0.08);">
                        <td style="padding: 16px 8px; font-size: 15px; font-weight: 900;" class="text-gray-900 dark:text-white">
                            Total Aset</td>
                        @foreach ($periods as $idx => $period)
                            <td style="padding: 16px 8px; text-align: right; font-size: 15px; font-weight: 900; font-variant-numeric: tabular-nums;"
                                class="text-gray-900 dark:text-white">{{ $fmtSigned($totalAset[$idx]) }}</td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        </div>
    </x-filament::section>

    <div style="height: 24px;"></div>

    {{-- =================== LIABILITAS & MODAL =================== --}}
    <x-filament::section>
        <x-slot name="heading">
            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                <span style="color: #8b5cf6; font-weight: 700; font-size: 15px;">Liabilitas dan Modal</span>
            </div>
        </x-slot>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; table-layout: fixed; min-width: {{ count($periods) * 130 + 350 }}px;">
                {!! $colgroupHtml !!}
                <thead>
                    <tr style="background: rgba(148, 163, 184, 0.15); border-bottom: 2px solid rgba(128,128,128,0.2);">
                        <th style="padding:10px 8px;font-size:11px;font-weight:700;text-align:left;" class="text-gray-400 dark:text-gray-500"></th>
                        @foreach ($periods as $period)
                            <th style="{{ $thStyle }}" class="text-gray-500 dark:text-gray-400">
                                {{ $period['label'] }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="{{ count($periods) + 1 }}" style="padding: 8px; font-size: 14px; font-weight: 700;"
                            class="text-gray-800 dark:text-gray-200">Liabilitas Jangka Pendek</td>
                    </tr>

                    @foreach ([$hutang, $kewajibanLancar] as $sectionData)
                        @foreach ($sectionData['sections'] as $section)
                            @if (!in_array(trim($section['label']), ['Kas & Bank', 'Piutang Tambahan', 'Piutang', 'Persediaan', 'Aktiva Lancar Lain', 'Aktiva Tetap', 'Kewajiban Jangka Pendek', 'Kewajiban Jangka Panjang', 'Ekuitas', 'Liabilitas', 'Liability', 'Equity', 'Assets', 'Asset', 'Aset', 'Kewajiban']))
                            <tr>
                                <td colspan="{{ count($periods) + 1 }}" style="padding: 12px 8px 6px 16px; font-size: 13px; font-weight: 600;"
                                    class="text-gray-700 dark:text-gray-300">{{ $section['label'] }}</td>
                            </tr>
                            @endif
                            @foreach ($section['rows'] as $row)
                                <tr style="border-bottom: 1px solid rgba(128,128,128,0.1);">
                                    <td style="padding: 8px 8px 8px 24px; font-size: 13px;" class="text-gray-600 dark:text-gray-400">
                                        {{ $row['code'] }} {{ $row['name'] }}</td>
                                    @foreach ($periods as $idx => $period)
                                        <td style="padding: 8px; text-align: right; font-size: 13px; font-weight: 500; font-variant-numeric: tabular-nums;"
                                            class="{{ $row['balances'][$idx] < 0 ? 'text-red-500' : 'text-gray-700 dark:text-gray-300' }}">
                                            {{ $fmtSigned($row['balances'][$idx]) }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                            <tr style="border-top: 2px solid rgba(128,128,128,0.2);">
                                <td style="padding: 10px 8px; font-size: 13px; font-weight: 700;"
                                    class="text-gray-900 dark:text-gray-100">Total {{ $section['label'] }}</td>
                                @foreach ($periods as $idx => $period)
                                    <td style="padding: 10px 8px; text-align: right; font-size: 13px; font-weight: 700; font-variant-numeric: tabular-nums;"
                                        class="text-gray-900 dark:text-gray-100">{{ $fmtSigned($section['totals'][$idx]) }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach

                    <tr style="border-top: 3px solid rgba(139,92,246,0.3); background: rgba(139,92,246,0.08);">
                        <td style="padding: 12px 8px; font-size: 14px; font-weight: 800;" class="text-gray-900 dark:text-white">
                            Total Liabilitas Jangka Pendek</td>
                        @foreach ($periods as $idx => $period)
                            <td style="padding: 12px 8px; text-align: right; font-size: 14px; font-weight: 800; font-variant-numeric: tabular-nums;"
                                class="text-gray-900 dark:text-white">{{ $fmtSigned($totalLiabilitasPendek[$idx]) }}</td>
                        @endforeach
                    </tr>

                    <tr>
                        <td colspan="{{ count($periods) + 1 }}" style="padding: 4px;"></td>
                    </tr>

                    <tr>
                        <td colspan="{{ count($periods) + 1 }}" style="padding: 16px 8px 8px; font-size: 14px; font-weight: 700;"
                            class="text-gray-800 dark:text-gray-200">Liabilitas Jangka Panjang</td>
                    </tr>
                    <tr style="border-top: 2px solid rgba(128,128,128,0.2);">
                        <td style="padding: 10px 8px; font-size: 13px; font-weight: 700;"
                            class="text-gray-900 dark:text-gray-100">Total Liabilitas Jangka Panjang</td>
                        @foreach ($periods as $idx => $period)
                            <td style="padding: 10px 8px; text-align: right; font-size: 13px; font-weight: 700;"
                                class="text-gray-900 dark:text-gray-100">-</td>
                        @endforeach
                    </tr>

                    <tr>
                        <td colspan="{{ count($periods) + 1 }}" style="padding: 4px;"></td>
                    </tr>

                    <tr>
                        <td colspan="{{ count($periods) + 1 }}" style="padding: 16px 8px 8px; font-size: 14px; font-weight: 700;"
                            class="text-gray-800 dark:text-gray-200">Perubahan Modal</td>
                    </tr>
                    @foreach ($ekuitas['sections'] as $section)
                        @if (!in_array(trim($section['label']), ['Kas & Bank', 'Piutang Tambahan', 'Piutang', 'Persediaan', 'Aktiva Lancar Lain', 'Aktiva Tetap', 'Kewajiban Jangka Pendek', 'Kewajiban Jangka Panjang', 'Ekuitas', 'Liabilitas', 'Liability', 'Equity', 'Assets', 'Asset', 'Aset', 'Kewajiban']))
                        <tr>
                            <td colspan="{{ count($periods) + 1 }}" style="padding: 12px 8px 6px 16px; font-size: 13px; font-weight: 600;"
                                class="text-gray-700 dark:text-gray-300">{{ $section['label'] }}</td>
                        </tr>
                        @endif
                        @foreach ($section['rows'] as $row)
                            <tr style="border-bottom: 1px solid rgba(128,128,128,0.1);">
                                <td style="padding: 8px 8px 8px 24px; font-size: 13px;" class="text-gray-600 dark:text-gray-400">
                                    {{ $row['code'] }} {{ $row['name'] }}</td>
                                @foreach ($periods as $idx => $period)
                                    <td style="padding: 8px; text-align: right; font-size: 13px; font-weight: 500; font-variant-numeric: tabular-nums;"
                                        class="{{ $row['balances'][$idx] < 0 ? 'text-red-500' : 'text-gray-700 dark:text-gray-300' }}">
                                        {{ $fmtSigned($row['balances'][$idx]) }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach
                    <tr style="border-top: 2px solid rgba(128,128,128,0.2);">
                        <td style="padding: 10px 8px; font-size: 13px; font-weight: 700;"
                            class="text-gray-900 dark:text-gray-100">Total Perubahan Modal</td>
                        @foreach ($periods as $idx => $period)
                            <td style="padding: 10px 8px; text-align: right; font-size: 13px; font-weight: 700; font-variant-numeric: tabular-nums;"
                                class="text-gray-900 dark:text-gray-100">{{ $fmtSigned($totalModal[$idx]) }}</td>
                        @endforeach
                    </tr>

                    {{-- GRAND TOTAL --}}
                    <tr style="border-top: 3px solid #8b5cf6; background: rgba(139,92,246,0.08);">
                        <td style="padding: 16px 8px; font-size: 15px; font-weight: 900;" class="text-gray-900 dark:text-white">
                            Total Liabilitas dan Modal</td>
                        @foreach ($periods as $idx => $period)
                            <td style="padding: 16px 8px; text-align: right; font-size: 15px; font-weight: 900; font-variant-numeric: tabular-nums;"
                                class="text-gray-900 dark:text-white">{{ $fmtSigned($totalLiabilitasModal[$idx]) }}</td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>