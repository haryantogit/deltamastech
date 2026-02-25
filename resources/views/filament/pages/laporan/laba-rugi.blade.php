<x-filament-panels::page>
    @php
        $fmtSigned = function ($num) {
            if ($num == 0) return '0';
            $prefix = $num < 0 ? '(' : '';
            $suffix = $num < 0 ? ')' : '';
            return $prefix . number_format(abs($num), 0, ',', '.') . $suffix;
        };

        $pctFmt = function ($current, $prev) {
            if ($prev == 0) {
                $pct = $current != 0 ? 100 : 0;
            } else {
                $pct = round((($current - $prev) / abs($prev)) * 100, 1);
            }
            if ($pct == 0) return '0%';
            $prefix = $pct < 0 ? '(' : '';
            $suffix = $pct < 0 ? ')' : '';
            return $prefix . number_format(abs($pct), 1, ',', '.') . '%' . $suffix;
        };

        $deltaColor = function ($val) {
            if ($val > 0) return 'color:#22c55e;';
            if ($val < 0) return 'color:#ef4444;';
            return 'color:#94a3b8;';
        };

        // Fixed-width inline SVG bar chart — all bars grow UP from a baseline
        $barChart = function (array $data) {
            $count = count($data);
            if ($count === 0) return '';
            $barW = 7;
            $gap = 3;
            $svgW = 66; // fixed width for all charts
            $svgH = 24;
            $baseline = $svgH - 1;
            $maxBarH = $svgH - 2;

            $absMax = 0;
            foreach ($data as $v) { $absMax = max($absMax, abs($v)); }
            if ($absMax == 0) $absMax = 1;

            // Center the bars within the fixed width
            $totalBarsW = $count * ($barW + $gap) - $gap;
            $offsetX = max(0, round(($svgW - $totalBarsW) / 2));

            $svg = '<svg width="'.$svgW.'" height="'.$svgH.'" viewBox="0 0 '.$svgW.' '.$svgH.'" style="display:block;margin:0 auto;">';
            foreach ($data as $i => $val) {
                $x = $offsetX + $i * ($barW + $gap);
                $h = round((abs($val) / $absMax) * $maxBarH, 1);
                if ($h < 2 && $val != 0) $h = 2;
                if ($val == 0) $h = 1;
                $y = $baseline - $h;
                $color = $val > 0 ? '#22c55e' : ($val < 0 ? '#ef4444' : '#cbd5e1');
                $svg .= '<rect x="'.$x.'" y="'.$y.'" width="'.$barW.'" height="'.$h.'" rx="1.5" fill="'.$color.'"/>';
            }
            $svg .= '</svg>';
            return $svg;
        };

        // Fixed table column widths
        $colgroup = '<colgroup><col style="width:auto"><col style="width:130px"><col style="width:80px"><col style="width:120px"><col style="width:80px"></colgroup>';

        // Column header style
        $thStyle = 'padding:10px 8px;font-size:12px;font-weight:600;text-align:right;white-space:nowrap;';
    @endphp

    {{-- =================== PENDAPATAN =================== --}}
    <x-filament::section>
        <x-slot name="heading">
            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                <span style="color: #16a34a; font-weight: 700; font-size: 15px;">Pendapatan</span>
                <span style="font-size: 13px; color: #94a3b8;">{{ $today }}</span>
            </div>
        </x-slot>

        <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
            {!! $colgroup !!}
            {{-- Column Header --}}
            <thead>
                <tr style="border-bottom: 2px solid rgba(128,128,128,0.15);">
                    <th style="padding:10px 8px;font-size:12px;font-weight:600;text-align:left;" class="text-gray-500 dark:text-gray-400"></th>
                    <th style="{{ $thStyle }}" class="text-gray-500 dark:text-gray-400">{{ $today }}</th>
                    <th style="{{ $thStyle }}text-align:center;" class="text-gray-500 dark:text-gray-400"></th>
                    <th style="{{ $thStyle }}" class="text-gray-500 dark:text-gray-400">Δ</th>
                    <th style="{{ $thStyle }}" class="text-gray-500 dark:text-gray-400">%Δ</th>
                </tr>
            </thead>

            @foreach ($pendapatan['sections'] as $section)
                <tr>
                    <td colspan="5" style="padding: 16px 8px 8px; font-size: 14px; font-weight: 600;"
                        class="text-gray-800 dark:text-gray-200">{{ $section['label'] }}</td>
                </tr>
                @foreach ($section['rows'] as $row)
                    @php $delta = $row['balance'] - $row['prev']; @endphp
                    <tr style="border-bottom: 1px solid rgba(128,128,128,0.1);">
                        <td style="padding: 8px 8px 8px 24px; font-size: 13px;" class="text-gray-600 dark:text-gray-400">
                            {{ $row['code'] }} {{ $row['name'] }}</td>
                        <td style="padding: 8px; text-align: right; font-size: 13px; font-weight: 500; font-variant-numeric: tabular-nums;"
                            class="{{ $row['balance'] < 0 ? 'text-red-500' : 'text-gray-700 dark:text-gray-300' }}">
                            {{ $fmtSigned($row['balance']) }}</td>
                        <td style="padding: 8px; text-align: center;">{!! $barChart($row['sparkline']) !!}</td>
                        <td style="padding: 8px; text-align: right; font-size: 12px; font-weight: 500; font-variant-numeric: tabular-nums; {{ $deltaColor($delta) }}">
                            {{ $fmtSigned($delta) }}</td>
                        <td style="padding: 8px; text-align: right; font-size: 12px; font-weight: 500; font-variant-numeric: tabular-nums; {{ $deltaColor($delta) }}">
                            {{ $pctFmt($row['balance'], $row['prev']) }}</td>
                    </tr>
                    @foreach ($row['children'] as $child)
                        @php $cDelta = $child['balance'] - $child['prev']; @endphp
                        <tr style="border-bottom: 1px solid rgba(128,128,128,0.05);" class="bg-gray-50/50 dark:bg-white/[0.02]">
                            <td style="padding: 5px 8px 5px 48px; font-size: 12px;" class="text-gray-400 dark:text-gray-500">
                                {{ $child['code'] }} {{ $child['name'] }}</td>
                            <td style="padding: 5px 8px; text-align: right; font-size: 12px; font-variant-numeric: tabular-nums;"
                                class="{{ $child['balance'] < 0 ? 'text-red-400' : 'text-gray-400 dark:text-gray-500' }}">
                                {{ $fmtSigned($child['balance']) }}</td>
                            <td style="padding: 5px 8px; text-align: center;">{!! $barChart($child['sparkline']) !!}</td>
                            <td style="padding: 5px 8px; text-align: right; font-size: 11px; font-variant-numeric: tabular-nums; {{ $deltaColor($cDelta) }}">
                                {{ $fmtSigned($cDelta) }}</td>
                            <td style="padding: 5px 8px; text-align: right; font-size: 11px; font-variant-numeric: tabular-nums; {{ $deltaColor($cDelta) }}">
                                {{ $pctFmt($child['balance'], $child['prev']) }}</td>
                        </tr>
                    @endforeach
                @endforeach
                @php $secDelta = $section['total'] - $section['prevTotal']; @endphp
                <tr style="border-top: 2px solid rgba(128,128,128,0.2);">
                    <td style="padding: 10px 8px; font-size: 13px; font-weight: 700;"
                        class="text-gray-900 dark:text-gray-100">Total {{ $section['label'] }}</td>
                    <td style="padding: 10px 8px; text-align: right; font-size: 13px; font-weight: 700; font-variant-numeric: tabular-nums;"
                        class="text-gray-900 dark:text-gray-100">{{ $fmtSigned($section['total']) }}</td>
                    <td style="padding: 10px 8px; text-align: center;">{!! $barChart($section['sparkline']) !!}</td>
                    <td style="padding: 10px 8px; text-align: right; font-size: 12px; font-weight: 700; font-variant-numeric: tabular-nums; {{ $deltaColor($secDelta) }}">
                        {{ $fmtSigned($secDelta) }}</td>
                    <td style="padding: 10px 8px; text-align: right; font-size: 12px; font-weight: 700; font-variant-numeric: tabular-nums; {{ $deltaColor($secDelta) }}">
                        {{ $pctFmt($section['total'], $section['prevTotal']) }}</td>
                </tr>
            @endforeach

            <tr><td colspan="5" style="padding: 4px;"></td></tr>

            {{-- Pendapatan Lainnya --}}
            @foreach ($pendapatanLain['sections'] as $section)
                <tr>
                    <td colspan="5" style="padding: 16px 8px 8px; font-size: 14px; font-weight: 600;"
                        class="text-gray-800 dark:text-gray-200">{{ $section['label'] }}</td>
                </tr>
                @foreach ($section['rows'] as $row)
                    @php $delta = $row['balance'] - $row['prev']; @endphp
                    <tr style="border-bottom: 1px solid rgba(128,128,128,0.1);">
                        <td style="padding: 8px 8px 8px 24px; font-size: 13px;" class="text-gray-600 dark:text-gray-400">
                            {{ $row['code'] }} {{ $row['name'] }}</td>
                        <td style="padding: 8px; text-align: right; font-size: 13px; font-weight: 500; font-variant-numeric: tabular-nums;"
                            class="{{ $row['balance'] < 0 ? 'text-red-500' : 'text-gray-700 dark:text-gray-300' }}">
                            {{ $fmtSigned($row['balance']) }}</td>
                        <td style="padding: 8px; text-align: center;">{!! $barChart($row['sparkline']) !!}</td>
                        <td style="padding: 8px; text-align: right; font-size: 12px; font-weight: 500; font-variant-numeric: tabular-nums; {{ $deltaColor($delta) }}">
                            {{ $fmtSigned($delta) }}</td>
                        <td style="padding: 8px; text-align: right; font-size: 12px; font-weight: 500; font-variant-numeric: tabular-nums; {{ $deltaColor($delta) }}">
                            {{ $pctFmt($row['balance'], $row['prev']) }}</td>
                    </tr>
                @endforeach
            @endforeach

            {{-- TOTAL PENDAPATAN --}}
            @php $totalPendDelta = $totalPendapatan - $prevTotalPendapatan; @endphp
            <tr style="border-top: 3px solid rgba(22,163,106,0.3); background: rgba(22,163,106,0.08);">
                <td style="padding: 14px 8px; font-size: 14px; font-weight: 800;" class="text-gray-900 dark:text-white">
                    Total Pendapatan</td>
                <td style="padding: 14px 8px; text-align: right; font-size: 14px; font-weight: 800; font-variant-numeric: tabular-nums;"
                    class="text-gray-900 dark:text-white">{{ $fmtSigned($totalPendapatan) }}</td>
                <td style="padding: 14px 8px;"></td>
                <td style="padding: 14px 8px; text-align: right; font-size: 13px; font-weight: 800; font-variant-numeric: tabular-nums; {{ $deltaColor($totalPendDelta) }}">
                    {{ $fmtSigned($totalPendDelta) }}</td>
                <td style="padding: 14px 8px; text-align: right; font-size: 13px; font-weight: 800; font-variant-numeric: tabular-nums; {{ $deltaColor($totalPendDelta) }}">
                    ({{ number_format(abs($pctPendapatan), 1, ',', '.') }}%)</td>
            </tr>
        </table>
    </x-filament::section>

    <div style="height: 24px;"></div>

    {{-- =================== BEBAN POKOK PENJUALAN =================== --}}
    <x-filament::section>
        <x-slot name="heading">
            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                <span style="color: #d97706; font-weight: 700; font-size: 15px;">Beban Pokok Penjualan</span>
                <span style="font-size: 13px; color: #94a3b8;">{{ $today }}</span>
            </div>
        </x-slot>

        <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
            {!! $colgroup !!}
            @foreach ($hpp['sections'] as $section)
                @foreach ($section['rows'] as $row)
                    @php $delta = $row['balance'] - $row['prev']; @endphp
                    <tr style="border-bottom: 1px solid rgba(128,128,128,0.1);">
                        <td style="padding: 8px 8px 8px 24px; font-size: 13px;" class="text-gray-600 dark:text-gray-400">
                            {{ $row['code'] }} {{ $row['name'] }}</td>
                        <td style="padding: 8px; text-align: right; font-size: 13px; font-weight: 500; font-variant-numeric: tabular-nums;"
                            class="{{ $row['balance'] < 0 ? 'text-red-500' : 'text-gray-700 dark:text-gray-300' }}">
                            {{ $fmtSigned($row['balance']) }}</td>
                        <td style="padding: 8px; text-align: center;">{!! $barChart($row['sparkline']) !!}</td>
                        <td style="padding: 8px; text-align: right; font-size: 12px; font-weight: 500; font-variant-numeric: tabular-nums; {{ $deltaColor($delta) }}">
                            {{ $fmtSigned($delta) }}</td>
                        <td style="padding: 8px; text-align: right; font-size: 12px; font-weight: 500; font-variant-numeric: tabular-nums; {{ $deltaColor($delta) }}">
                            {{ $pctFmt($row['balance'], $row['prev']) }}</td>
                    </tr>
                    @foreach ($row['children'] as $child)
                        @php $cDelta = $child['balance'] - $child['prev']; @endphp
                        <tr style="border-bottom: 1px solid rgba(128,128,128,0.05);" class="bg-gray-50/50 dark:bg-white/[0.02]">
                            <td style="padding: 5px 8px 5px 48px; font-size: 12px;" class="text-gray-400 dark:text-gray-500">
                                {{ $child['code'] }} {{ $child['name'] }}</td>
                            <td style="padding: 5px 8px; text-align: right; font-size: 12px; font-variant-numeric: tabular-nums;"
                                class="{{ $child['balance'] < 0 ? 'text-red-400' : 'text-gray-400 dark:text-gray-500' }}">
                                {{ $fmtSigned($child['balance']) }}</td>
                            <td style="padding: 5px 8px; text-align: center;">{!! $barChart($child['sparkline']) !!}</td>
                            <td style="padding: 5px 8px; text-align: right; font-size: 11px; font-variant-numeric: tabular-nums; {{ $deltaColor($cDelta) }}">
                                {{ $fmtSigned($cDelta) }}</td>
                            <td style="padding: 5px 8px; text-align: right; font-size: 11px; font-variant-numeric: tabular-nums; {{ $deltaColor($cDelta) }}">
                                {{ $pctFmt($child['balance'], $child['prev']) }}</td>
                        </tr>
                    @endforeach
                @endforeach
            @endforeach

            @php $hppDelta = $totalHPP - $hpp['prevTotal']; @endphp
            <tr style="border-top: 3px solid rgba(217,119,6,0.3); background: rgba(217,119,6,0.08);">
                <td style="padding: 14px 8px; font-size: 14px; font-weight: 800;" class="text-gray-900 dark:text-white">
                    Total Beban Pokok Penjualan</td>
                <td style="padding: 14px 8px; text-align: right; font-size: 14px; font-weight: 800; font-variant-numeric: tabular-nums;"
                    class="text-gray-900 dark:text-white">{{ $fmtSigned($totalHPP) }}</td>
                <td style="padding: 14px 8px;"></td>
                <td style="padding: 14px 8px; text-align: right; font-size: 13px; font-weight: 800; font-variant-numeric: tabular-nums; {{ $deltaColor($hppDelta) }}">
                    {{ $fmtSigned($hppDelta) }}</td>
                <td style="padding: 14px 8px; text-align: right; font-size: 13px; font-weight: 800; font-variant-numeric: tabular-nums; {{ $deltaColor($hppDelta) }}">
                    {{ $pctFmt($totalHPP, $hpp['prevTotal']) }}</td>
            </tr>
        </table>
    </x-filament::section>

    <div style="height: 12px;"></div>

    {{-- =================== LABA KOTOR =================== --}}
    <x-filament::section>
        <x-slot name="heading">
            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                <span style="color: #3b82f6; font-weight: 700; font-size: 15px;">Laba Kotor</span>
            </div>
        </x-slot>

        <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
            {!! $colgroup !!}
            @php $lkDelta = $labaKotor - $prevLabaKotor; @endphp
            <tr style="background: rgba(59,130,246,0.08);">
                <td style="padding: 14px 8px; font-size: 15px; font-weight: 900;" class="text-gray-900 dark:text-white">
                    Laba Kotor</td>
                <td style="padding: 14px 8px; text-align: right; font-size: 15px; font-weight: 900; font-variant-numeric: tabular-nums;"
                    class="{{ $labaKotor < 0 ? 'text-red-600' : 'text-gray-900 dark:text-white' }}">
                    {{ $fmtSigned($labaKotor) }}</td>
                <td style="padding: 14px 8px;"></td>
                <td style="padding: 14px 8px; text-align: right; font-size: 13px; font-weight: 800; font-variant-numeric: tabular-nums; {{ $deltaColor($lkDelta) }}">
                    {{ $fmtSigned($lkDelta) }}</td>
                <td style="padding: 14px 8px; text-align: right; font-size: 13px; font-weight: 800; font-variant-numeric: tabular-nums; {{ $deltaColor($lkDelta) }}">
                    ({{ number_format(abs($pctLabaKotor), 1, ',', '.') }}%)</td>
            </tr>
        </table>
    </x-filament::section>

    <div style="height: 24px;"></div>

    {{-- =================== BIAYA OPERASIONAL =================== --}}
    <x-filament::section>
        <x-slot name="heading">
            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                <span style="color: #dc2626; font-weight: 700; font-size: 15px;">Biaya Operasional</span>
                <span style="font-size: 13px; color: #94a3b8;">{{ $today }}</span>
            </div>
        </x-slot>

        <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
            {!! $colgroup !!}
            @foreach ($beban['sections'] as $section)
                <tr>
                    <td colspan="5" style="padding: 16px 8px 8px; font-size: 14px; font-weight: 600;"
                        class="text-gray-800 dark:text-gray-200">{{ $section['label'] }}</td>
                </tr>
                @foreach ($section['rows'] as $row)
                    @php $delta = $row['balance'] - $row['prev']; @endphp
                    <tr style="border-bottom: 1px solid rgba(128,128,128,0.1);">
                        <td style="padding: 8px 8px 8px 24px; font-size: 13px;" class="text-gray-600 dark:text-gray-400">
                            {{ $row['code'] }} {{ $row['name'] }}</td>
                        <td style="padding: 8px; text-align: right; font-size: 13px; font-weight: 500; font-variant-numeric: tabular-nums;"
                            class="{{ $row['balance'] < 0 ? 'text-red-500' : 'text-gray-700 dark:text-gray-300' }}">
                            {{ $fmtSigned($row['balance']) }}</td>
                        <td style="padding: 8px; text-align: center;">{!! $barChart($row['sparkline']) !!}</td>
                        <td style="padding: 8px; text-align: right; font-size: 12px; font-weight: 500; font-variant-numeric: tabular-nums; {{ $deltaColor($delta) }}">
                            {{ $fmtSigned($delta) }}</td>
                        <td style="padding: 8px; text-align: right; font-size: 12px; font-weight: 500; font-variant-numeric: tabular-nums; {{ $deltaColor($delta) }}">
                            {{ $pctFmt($row['balance'], $row['prev']) }}</td>
                    </tr>
                    @foreach ($row['children'] as $child)
                        @php $cDelta = $child['balance'] - $child['prev']; @endphp
                        <tr style="border-bottom: 1px solid rgba(128,128,128,0.05);" class="bg-gray-50/50 dark:bg-white/[0.02]">
                            <td style="padding: 5px 8px 5px 48px; font-size: 12px;" class="text-gray-400 dark:text-gray-500">
                                {{ $child['code'] }} {{ $child['name'] }}</td>
                            <td style="padding: 5px 8px; text-align: right; font-size: 12px; font-variant-numeric: tabular-nums;"
                                class="{{ $child['balance'] < 0 ? 'text-red-400' : 'text-gray-400 dark:text-gray-500' }}">
                                {{ $fmtSigned($child['balance']) }}</td>
                            <td style="padding: 5px 8px; text-align: center;">{!! $barChart($child['sparkline']) !!}</td>
                            <td style="padding: 5px 8px; text-align: right; font-size: 11px; font-variant-numeric: tabular-nums; {{ $deltaColor($cDelta) }}">
                                {{ $fmtSigned($cDelta) }}</td>
                            <td style="padding: 5px 8px; text-align: right; font-size: 11px; font-variant-numeric: tabular-nums; {{ $deltaColor($cDelta) }}">
                                {{ $pctFmt($child['balance'], $child['prev']) }}</td>
                        </tr>
                    @endforeach
                @endforeach
                @php $secDelta = $section['total'] - $section['prevTotal']; @endphp
                <tr style="border-top: 2px solid rgba(128,128,128,0.2);">
                    <td style="padding: 10px 8px; font-size: 13px; font-weight: 700;"
                        class="text-gray-900 dark:text-gray-100">Total {{ $section['label'] }}</td>
                    <td style="padding: 10px 8px; text-align: right; font-size: 13px; font-weight: 700; font-variant-numeric: tabular-nums;"
                        class="text-gray-900 dark:text-gray-100">{{ $fmtSigned($section['total']) }}</td>
                    <td style="padding: 10px 8px; text-align: center;">{!! $barChart($section['sparkline']) !!}</td>
                    <td style="padding: 10px 8px; text-align: right; font-size: 12px; font-weight: 700; font-variant-numeric: tabular-nums; {{ $deltaColor($secDelta) }}">
                        {{ $fmtSigned($secDelta) }}</td>
                    <td style="padding: 10px 8px; text-align: right; font-size: 12px; font-weight: 700; font-variant-numeric: tabular-nums; {{ $deltaColor($secDelta) }}">
                        {{ $pctFmt($section['total'], $section['prevTotal']) }}</td>
                </tr>
            @endforeach

            <tr><td colspan="5" style="padding: 4px;"></td></tr>

            {{-- Beban Lainnya --}}
            @foreach ($bebanLain['sections'] as $section)
                <tr>
                    <td colspan="5" style="padding: 16px 8px 8px; font-size: 14px; font-weight: 600;"
                        class="text-gray-800 dark:text-gray-200">{{ $section['label'] }}</td>
                </tr>
                @foreach ($section['rows'] as $row)
                    @php $delta = $row['balance'] - $row['prev']; @endphp
                    <tr style="border-bottom: 1px solid rgba(128,128,128,0.1);">
                        <td style="padding: 8px 8px 8px 24px; font-size: 13px;" class="text-gray-600 dark:text-gray-400">
                            {{ $row['code'] }} {{ $row['name'] }}</td>
                        <td style="padding: 8px; text-align: right; font-size: 13px; font-weight: 500; font-variant-numeric: tabular-nums;"
                            class="{{ $row['balance'] < 0 ? 'text-red-500' : 'text-gray-700 dark:text-gray-300' }}">
                            {{ $fmtSigned($row['balance']) }}</td>
                        <td style="padding: 8px; text-align: center;">{!! $barChart($row['sparkline']) !!}</td>
                        <td style="padding: 8px; text-align: right; font-size: 12px; font-weight: 500; font-variant-numeric: tabular-nums; {{ $deltaColor($delta) }}">
                            {{ $fmtSigned($delta) }}</td>
                        <td style="padding: 8px; text-align: right; font-size: 12px; font-weight: 500; font-variant-numeric: tabular-nums; {{ $deltaColor($delta) }}">
                            {{ $pctFmt($row['balance'], $row['prev']) }}</td>
                    </tr>
                @endforeach
            @endforeach

            {{-- TOTAL BIAYA OPERASIONAL --}}
            @php $bebanDelta = $totalBeban - $prevTotalBeban; @endphp
            <tr style="border-top: 3px solid rgba(220,38,38,0.3); background: rgba(220,38,38,0.08);">
                <td style="padding: 14px 8px; font-size: 14px; font-weight: 800;" class="text-gray-900 dark:text-white">
                    Total Biaya Operasional</td>
                <td style="padding: 14px 8px; text-align: right; font-size: 14px; font-weight: 800; font-variant-numeric: tabular-nums;"
                    class="text-gray-900 dark:text-white">{{ $fmtSigned($totalBeban) }}</td>
                <td style="padding: 14px 8px;"></td>
                <td style="padding: 14px 8px; text-align: right; font-size: 13px; font-weight: 800; font-variant-numeric: tabular-nums; {{ $deltaColor($bebanDelta) }}">
                    {{ $fmtSigned($bebanDelta) }}</td>
                <td style="padding: 14px 8px; text-align: right; font-size: 13px; font-weight: 800; font-variant-numeric: tabular-nums; {{ $deltaColor($bebanDelta) }}">
                    {{ $pctFmt($totalBeban, $prevTotalBeban) }}</td>
            </tr>
        </table>
    </x-filament::section>

    <div style="height: 24px;"></div>

    {{-- =================== LABA BERSIH =================== --}}
    <x-filament::section>
        <x-slot name="heading">
            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                <span style="color: #8b5cf6; font-weight: 700; font-size: 15px;">Laba Bersih</span>
            </div>
        </x-slot>

        <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
            {!! $colgroup !!}
            @php $lbDelta = $labaBersih - $prevLabaBersih; @endphp
            <tr style="border-top: 3px solid #8b5cf6; background: rgba(139,92,246,0.08);">
                <td style="padding: 16px 8px; font-size: 16px; font-weight: 900;" class="text-gray-900 dark:text-white">
                    Laba Bersih</td>
                <td style="padding: 16px 8px; text-align: right; font-size: 16px; font-weight: 900; font-variant-numeric: tabular-nums;"
                    class="{{ $labaBersih < 0 ? 'text-red-600' : 'text-gray-900 dark:text-white' }}">
                    {{ $fmtSigned($labaBersih) }}</td>
                <td style="padding: 16px 8px;"></td>
                <td style="padding: 16px 8px; text-align: right; font-size: 14px; font-weight: 900; font-variant-numeric: tabular-nums; {{ $deltaColor($lbDelta) }}">
                    {{ $fmtSigned($lbDelta) }}</td>
                <td style="padding: 16px 8px; text-align: right; font-size: 14px; font-weight: 900; font-variant-numeric: tabular-nums; {{ $deltaColor($lbDelta) }}">
                    ({{ number_format(abs($pctLabaBersih), 1, ',', '.') }}%)</td>
            </tr>
        </table>
    </x-filament::section>
</x-filament-panels::page>
