<x-filament-panels::page>
    @php
        $fmtSigned = function ($num) {
            if ($num == 0)
                return '0';
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
            if ($pct == 0)
                return '0%';
            $prefix = $pct < 0 ? '(' : '';
            $suffix = $pct < 0 ? ')' : '';
            return $prefix . number_format(abs($pct), 1, ',', '.') . '%' . $suffix;
        };

        $deltaColor = function ($val) {
            if ($val > 0)
                return 'color:#22c55e;';
            if ($val < 0)
                return 'color:#ef4444;';
            return 'color:#94a3b8;';
        };

        // Fixed-width inline SVG bar chart — all bars grow UP from a baseline
        $barChart = function (array $data) {
            $count = count($data);
            if ($count === 0)
                return '';
            $barW = 7;
            $gap = 3;
            $svgW = 66; // fixed width for all charts
            $svgH = 24;
            $baseline = $svgH - 1;
            $maxBarH = $svgH - 2;

            $absMax = 0;
            foreach ($data as $v) {
                $absMax = max($absMax, abs($v));
            }
            if ($absMax == 0)
                $absMax = 1;

            // Center the bars within the fixed width
            $totalBarsW = $count * ($barW + $gap) - $gap;
            $offsetX = max(0, round(($svgW - $totalBarsW) / 2));

            $svg = '<svg width="' . $svgW . '" height="' . $svgH . '" viewBox="0 0 ' . $svgW . ' ' . $svgH . '" style="display:block;margin:0 auto;">';
            foreach ($data as $i => $val) {
                $x = $offsetX + $i * ($barW + $gap);
                $h = round((abs($val) / $absMax) * $maxBarH, 1);
                if ($h < 2 && $val != 0)
                    $h = 2;
                if ($val == 0)
                    $h = 1;
                $y = $baseline - $h;
                $color = $val > 0 ? '#22c55e' : ($val < 0 ? '#ef4444' : '#cbd5e1');
                $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $barW . '" height="' . $h . '" rx="1.5" fill="' . $color . '"/>';
            }
            $svg .= '</svg>';
            return $svg;
        };

        // When comparing periods, index 0 is the "most recent" period from the array_reverse. 
        // Sparklines will always be generated. But deltas and %deltas are best shown if there are at least two periods.
        $hasCompare = count($periods) > 1;

        // Fixed table column widths
        $colgroupHtml = '<colgroup><col style="width:350px">';
        foreach ($periods as $p) {
            $colgroupHtml .= '<col style="width:130px">';
        }
        $colgroupHtml .= '<col style="width:80px">'; // Sparkline
        if ($hasCompare) {
            $colgroupHtml .= '<col style="width:120px"><col style="width:80px">'; // Delta & %Delta
        }
        $colgroupHtml .= '</colgroup>';

        // Column header style
        $thStyle = 'padding:10px 8px;font-size:12px;font-weight:600;text-align:right;white-space:nowrap;';
    @endphp

    {{-- Filters are hidden/moved to simplify layout --}}

    {{-- =================== PENDAPATAN =================== --}}
    <x-filament::section>
        <x-slot name="heading">
            <span style="color: #16a34a; font-weight: 700; font-size: 15px;">{{ $section['label'] ?? 'Pendapatan' }}</span>
        </x-slot>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; table-layout: fixed; min-width: {{ count($periods) * 130 + ($hasCompare ? 580 : 380) }}px;">
                {!! $colgroupHtml !!}
                {{-- Column Header --}}
                <thead>
                    <tr style="background: rgba(148, 163, 184, 0.15); border-bottom: 2px solid rgba(128,128,128,0.2);">
                        <th style="padding:10px 8px;font-size:11px;font-weight:700;text-align:left;" class="text-gray-400 dark:text-gray-500"></th>
                        @foreach ($periods as $period)
                            <th style="{{ $thStyle }}" class="text-gray-500 dark:text-gray-400">{{ $period['label'] }}</th>
                        @endforeach
                        <th style="{{ $thStyle }}text-align:center;" class="text-gray-500 dark:text-gray-400">
                            <x-heroicon-o-chart-bar style="width: 1rem; height: 1rem; margin: 0 auto; opacity: 0.5;" />
                        </th>
                        @if ($hasCompare)
                            <th style="{{ $thStyle }}" class="text-gray-500 dark:text-gray-400">Δ</th>
                            <th style="{{ $thStyle }}" class="text-gray-500 dark:text-gray-400">%Δ</th>
                        @endif
                    </tr>
                </thead>

                <tbody>
                    @foreach ($pendapatan['sections'] as $section)
                        @if (!in_array(trim($section['label']), ['Pendapatan', 'Biaya Pokok', 'Biaya Operasional', 'Pendapatan Lainnya', 'Beban Lainnya', 'HPP', 'Beban', 'Operational Expenses', 'Other Income', 'Other Expenses']))
                            <tr>
                                <td colspan="{{ count($periods) + ($hasCompare ? 4 : 2) }}" style="padding: 16px 8px 8px; font-size: 14px; font-weight: 600;"
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
                                <td style="padding: 8px; text-align: center;">{!! $barChart($row['sparkline']) !!}</td>
                                @if ($hasCompare)
                                    @php $delta = $row['balances'][0] - $row['balances'][1]; @endphp
                                    <td style="padding: 8px; text-align: right; font-size: 12px; font-weight: 500; font-variant-numeric: tabular-nums; {{ $deltaColor($delta) }}">
                                        {{ $fmtSigned($delta) }}</td>
                                    <td style="padding: 8px; text-align: right; font-size: 12px; font-weight: 500; font-variant-numeric: tabular-nums; {{ $deltaColor($delta) }}">
                                        {{ $pctFmt($row['balances'][0], $row['balances'][1]) }}</td>
                                @endif
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
                                    <td style="padding: 5px 8px; text-align: center;">{!! $barChart($child['sparkline']) !!}</td>
                                    @if ($hasCompare)
                                        @php $cDelta = $child['balances'][0] - $child['balances'][1]; @endphp
                                        <td style="padding: 5px 8px; text-align: right; font-size: 11px; font-variant-numeric: tabular-nums; {{ $deltaColor($cDelta) }}">
                                            {{ $fmtSigned($cDelta) }}</td>
                                        <td style="padding: 5px 8px; text-align: right; font-size: 11px; font-variant-numeric: tabular-nums; {{ $deltaColor($cDelta) }}">
                                            {{ $pctFmt($child['balances'][0], $child['balances'][1]) }}</td>
                                    @endif
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
                            <td style="padding: 10px 8px; text-align: center;">{!! $barChart($section['sparkline']) !!}</td>
                            @if ($hasCompare)
                                @php $secDelta = $section['totals'][0] - $section['totals'][1]; @endphp
                                <td style="padding: 10px 8px; text-align: right; font-size: 12px; font-weight: 700; font-variant-numeric: tabular-nums; {{ $deltaColor($secDelta) }}">
                                    {{ $fmtSigned($secDelta) }}</td>
                                <td style="padding: 10px 8px; text-align: right; font-size: 12px; font-weight: 700; font-variant-numeric: tabular-nums; {{ $deltaColor($secDelta) }}">
                                    {{ $pctFmt($section['totals'][0], $section['totals'][1]) }}</td>
                            @endif
                        </tr>
                    @endforeach

                    <tr><td colspan="{{ count($periods) + ($hasCompare ? 4 : 2) }}" style="padding: 4px;"></td></tr>

                    {{-- Pendapatan Lainnya --}}
                    @foreach ($pendapatanLain['sections'] as $section)
                        @if (!in_array(trim($section['label']), ['Pendapatan', 'Biaya Pokok', 'Biaya Operasional', 'Pendapatan Lainnya', 'Beban Lainnya', 'HPP', 'Beban', 'Operational Expenses', 'Other Income', 'Other Expenses']))
                            <tr>
                                <td colspan="{{ count($periods) + ($hasCompare ? 4 : 2) }}" style="padding: 16px 8px 8px; font-size: 14px; font-weight: 600;"
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
                                <td style="padding: 8px; text-align: center;">{!! $barChart($row['sparkline']) !!}</td>
                                @if ($hasCompare)
                                    @php $delta = $row['balances'][0] - $row['balances'][1]; @endphp
                                    <td style="padding: 8px; text-align: right; font-size: 12px; font-weight: 500; font-variant-numeric: tabular-nums; {{ $deltaColor($delta) }}">
                                        {{ $fmtSigned($delta) }}</td>
                                    <td style="padding: 8px; text-align: right; font-size: 12px; font-weight: 500; font-variant-numeric: tabular-nums; {{ $deltaColor($delta) }}">
                                        {{ $pctFmt($row['balances'][0], $row['balances'][1]) }}</td>
                                @endif
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
                                    <td style="padding: 5px 8px; text-align: center;">{!! $barChart($child['sparkline']) !!}</td>
                                    @if ($hasCompare)
                                        @php $cDelta = $child['balances'][0] - $child['balances'][1]; @endphp
                                        <td style="padding: 5px 8px; text-align: right; font-size: 11px; font-variant-numeric: tabular-nums; {{ $deltaColor($cDelta) }}">
                                            {{ $fmtSigned($cDelta) }}</td>
                                        <td style="padding: 5px 8px; text-align: right; font-size: 11px; font-variant-numeric: tabular-nums; {{ $deltaColor($cDelta) }}">
                                            {{ $pctFmt($child['balances'][0], $child['balances'][1]) }}</td>
                                    @endif
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
                            <td style="padding: 10px 8px; text-align: center;">{!! $barChart($section['sparkline']) !!}</td>
                            @if ($hasCompare)
                                @php $secDelta = $section['totals'][0] - $section['totals'][1]; @endphp
                                <td style="padding: 10px 8px; text-align: right; font-size: 12px; font-weight: 700; font-variant-numeric: tabular-nums; {{ $deltaColor($secDelta) }}">
                                    {{ $fmtSigned($secDelta) }}</td>
                                <td style="padding: 10px 8px; text-align: right; font-size: 12px; font-weight: 700; font-variant-numeric: tabular-nums; {{ $deltaColor($secDelta) }}">
                                    {{ $pctFmt($section['totals'][0], $section['totals'][1]) }}</td>
                            @endif
                        </tr>
                    @endforeach

                    <tr><td colspan="{{ count($periods) + ($hasCompare ? 4 : 2) }}" style="padding: 8px;"></td></tr>

                    {{-- Total Pendapatan Keseluruhan --}}
                    <tr style="background: rgba(34, 197, 94, 0.05); border-top: 2px solid rgba(34, 197, 94, 0.2);">
                        <td style="padding: 12px 8px; font-size: 14px; font-weight: 700;" class="text-green-700 dark:text-green-400">Total Pendapatan</td>
                        @foreach ($periods as $idx => $period)
                            <td style="padding: 12px 8px; text-align: right; font-size: 14px; font-weight: 700; font-variant-numeric: tabular-nums;"
                                class="text-green-700 dark:text-green-400">{{ $fmtSigned($totalPendapatanAll[$idx]) }}</td>
                        @endforeach
                        <td style="padding: 12px 8px;"></td>
                        @if ($hasCompare)
                            @php $dtPendapatan = $totalPendapatanAll[0] - $totalPendapatanAll[1]; @endphp
                            <td style="padding: 12px 8px; text-align: right; font-size: 13px; font-weight: 700; font-variant-numeric: tabular-nums; {{ $deltaColor($dtPendapatan) }}">
                                {{ $fmtSigned($dtPendapatan) }}</td>
                            <td style="padding: 12px 8px; text-align: right; font-size: 13px; font-weight: 700; font-variant-numeric: tabular-nums; {{ $deltaColor($dtPendapatan) }}">
                                {{ $pctFmt($totalPendapatanAll[0], $totalPendapatanAll[1]) }}</td>
                        @endif
                    </tr>
                </tbody>
            </table>
        </div>
    </x-filament::section>

    {{-- =================== BIAYA POKOK =================== --}}
    <x-filament::section>
        <x-slot name="heading">
            <span style="color: #64748b; font-weight: 700; font-size: 15px;">Biaya Pokok</span>
        </x-slot>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; table-layout: fixed; min-width: {{ count($periods) * 130 + ($hasCompare ? 580 : 380) }}px;">
                {!! $colgroupHtml !!}
                @foreach ($hpp['sections'] as $section)
                    @if (!in_array($section['label'], ['Pendapatan', 'Biaya Pokok', 'Biaya Operasional', 'Pendapatan Lainnya', 'Beban Lainnya']))
                        <tr>
                            <td colspan="{{ count($periods) + ($hasCompare ? 4 : 2) }}" style="padding: 16px 8px 8px; font-size: 14px; font-weight: 600;"
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
                            <td style="padding: 8px; text-align: center;">{!! $barChart($row['sparkline']) !!}</td>
                            @if ($hasCompare)
                                @php $delta = $row['balances'][0] - $row['balances'][1]; @endphp
                                <td style="padding: 8px; text-align: right; font-size: 12px; font-weight: 500; font-variant-numeric: tabular-nums; {{ $deltaColor($delta) }}">
                                    {{ $fmtSigned($delta) }}</td>
                                <td style="padding: 8px; text-align: right; font-size: 12px; font-weight: 500; font-variant-numeric: tabular-nums; {{ $deltaColor($delta) }}">
                                    {{ $pctFmt($row['balances'][0], $row['balances'][1]) }}</td>
                            @endif
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
                                <td style="padding: 5px 8px; text-align: center;">{!! $barChart($child['sparkline']) !!}</td>
                                @if ($hasCompare)
                                    @php $cDelta = $child['balances'][0] - $child['balances'][1]; @endphp
                                    <td style="padding: 5px 8px; text-align: right; font-size: 11px; font-variant-numeric: tabular-nums; {{ $deltaColor($cDelta) }}">
                                        {{ $fmtSigned($cDelta) }}</td>
                                    <td style="padding: 5px 8px; text-align: right; font-size: 11px; font-variant-numeric: tabular-nums; {{ $deltaColor($cDelta) }}">
                                        {{ $pctFmt($child['balances'][0], $child['balances'][1]) }}</td>
                                @endif
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
                        <td style="padding: 10px 8px; text-align: center;">{!! $barChart($section['sparkline']) !!}</td>
                        @if ($hasCompare)
                            @php $secDelta = $section['totals'][0] - $section['totals'][1]; @endphp
                            <td style="padding: 10px 8px; text-align: right; font-size: 12px; font-weight: 700; font-variant-numeric: tabular-nums; {{ $deltaColor($secDelta) }}">
                                {{ $fmtSigned($secDelta) }}</td>
                            <td style="padding: 10px 8px; text-align: right; font-size: 12px; font-weight: 700; font-variant-numeric: tabular-nums; {{ $deltaColor($secDelta) }}">
                                {{ $pctFmt($section['totals'][0], $section['totals'][1]) }}</td>
                        @endif
                    </tr>
                @endforeach

                <tr><td colspan="{{ count($periods) + ($hasCompare ? 4 : 2) }}" style="padding: 8px;"></td></tr>

                {{-- Laba Kotor --}}
                <tr style="background: rgba(59, 130, 246, 0.05); border-top: 2px solid rgba(59, 130, 246, 0.2);">
                    <td style="padding: 12px 8px; font-size: 14px; font-weight: 700;" class="text-blue-600 dark:text-blue-400">Total Laba Kotor</td>
                    @foreach ($periods as $idx => $period)
                        <td style="padding: 12px 8px; text-align: right; font-size: 14px; font-weight: 700; font-variant-numeric: tabular-nums;"
                            class="text-blue-600 dark:text-blue-400">{{ $fmtSigned($labaKotorTotals[$idx]) }}</td>
                    @endforeach
                    <td style="padding: 12px 8px;"></td>
                    @if ($hasCompare)
                        @php $dtKotor = $labaKotorTotals[0] - $labaKotorTotals[1]; @endphp
                        <td style="padding: 12px 8px; text-align: right; font-size: 13px; font-weight: 700; font-variant-numeric: tabular-nums; {{ $deltaColor($dtKotor) }}">
                            {{ $fmtSigned($dtKotor) }}</td>
                        <td style="padding: 12px 8px; text-align: right; font-size: 13px; font-weight: 700; font-variant-numeric: tabular-nums; {{ $deltaColor($dtKotor) }}">
                            {{ $pctFmt($labaKotorTotals[0], $labaKotorTotals[1]) }}</td>
                    @endif
                </tr>
            </table>
        </div>
    </x-filament::section>

    {{-- =================== BIAYA OPERASIONAL =================== --}}
    <x-filament::section class="-mt-4">
        <x-slot name="heading">
            <span style="color: #ef4444; font-weight: 700; font-size: 15px;">Biaya Operasional</span>
        </x-slot>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; table-layout: fixed; min-width: {{ count($periods) * 130 + ($hasCompare ? 580 : 380) }}px;">
                {!! $colgroupHtml !!}
                @foreach ($beban['sections'] as $section)
                    @if (!in_array($section['label'], ['Pendapatan', 'Biaya Pokok', 'Biaya Operasional', 'Pendapatan Lainnya', 'Beban Lainnya']))
                        <tr>
                            <td colspan="{{ count($periods) + ($hasCompare ? 4 : 2) }}" style="padding: 16px 8px 8px; font-size: 14px; font-weight: 600;"
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
                            <td style="padding: 8px; text-align: center;">{!! $barChart($row['sparkline']) !!}</td>
                            @if ($hasCompare)
                                @php $delta = $row['balances'][0] - $row['balances'][1]; @endphp
                                <td style="padding: 8px; text-align: right; font-size: 12px; font-weight: 500; font-variant-numeric: tabular-nums; {{ $deltaColor($delta) }}">
                                    {{ $fmtSigned($delta) }}</td>
                                <td style="padding: 8px; text-align: right; font-size: 12px; font-weight: 500; font-variant-numeric: tabular-nums; {{ $deltaColor($delta) }}">
                                    {{ $pctFmt($row['balances'][0], $row['balances'][1]) }}</td>
                            @endif
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
                                <td style="padding: 5px 8px; text-align: center;">{!! $barChart($child['sparkline']) !!}</td>
                                @if ($hasCompare)
                                    @php $cDelta = $child['balances'][0] - $child['balances'][1]; @endphp
                                    <td style="padding: 5px 8px; text-align: right; font-size: 11px; font-variant-numeric: tabular-nums; {{ $deltaColor($cDelta) }}">
                                        {{ $fmtSigned($cDelta) }}</td>
                                    <td style="padding: 5px 8px; text-align: right; font-size: 11px; font-variant-numeric: tabular-nums; {{ $deltaColor($cDelta) }}">
                                        {{ $pctFmt($child['balances'][0], $child['balances'][1]) }}</td>
                                @endif
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
                        <td style="padding: 10px 8px; text-align: center;">{!! $barChart($section['sparkline']) !!}</td>
                        @if ($hasCompare)
                            @php $secDelta = $section['totals'][0] - $section['totals'][1]; @endphp
                            <td style="padding: 10px 8px; text-align: right; font-size: 12px; font-weight: 700; font-variant-numeric: tabular-nums; {{ $deltaColor($secDelta) }}">
                                {{ $fmtSigned($secDelta) }}</td>
                            <td style="padding: 10px 8px; text-align: right; font-size: 12px; font-weight: 700; font-variant-numeric: tabular-nums; {{ $deltaColor($secDelta) }}">
                                {{ $pctFmt($section['totals'][0], $section['totals'][1]) }}</td>
                        @endif
                    </tr>
                @endforeach

                {{-- Beban Lainnya --}}
                @foreach ($bebanLain['sections'] as $section)
                    @if (!in_array($section['label'], ['Pendapatan', 'Biaya Pokok', 'Biaya Operasional', 'Pendapatan Lainnya', 'Beban Lainnya']))
                        <tr>
                            <td colspan="{{ count($periods) + ($hasCompare ? 4 : 2) }}" style="padding: 16px 8px 8px; font-size: 14px; font-weight: 600;"
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
                            <td style="padding: 8px; text-align: center;">{!! $barChart($row['sparkline']) !!}</td>
                            @if ($hasCompare)
                                @php $delta = $row['balances'][0] - $row['balances'][1]; @endphp
                                <td style="padding: 8px; text-align: right; font-size: 12px; font-weight: 500; font-variant-numeric: tabular-nums; {{ $deltaColor($delta) }}">
                                    {{ $fmtSigned($delta) }}</td>
                                <td style="padding: 8px; text-align: right; font-size: 12px; font-weight: 500; font-variant-numeric: tabular-nums; {{ $deltaColor($delta) }}">
                                    {{ $pctFmt($row['balances'][0], $row['balances'][1]) }}</td>
                            @endif
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
                                <td style="padding: 5px 8px; text-align: center;">{!! $barChart($child['sparkline']) !!}</td>
                                @if ($hasCompare)
                                    @php $cDelta = $child['balances'][0] - $child['balances'][1]; @endphp
                                    <td style="padding: 5px 8px; text-align: right; font-size: 11px; font-variant-numeric: tabular-nums; {{ $deltaColor($cDelta) }}">
                                        {{ $fmtSigned($cDelta) }}</td>
                                    <td style="padding: 5px 8px; text-align: right; font-size: 11px; font-variant-numeric: tabular-nums; {{ $deltaColor($cDelta) }}">
                                        {{ $pctFmt($child['balances'][0], $child['balances'][1]) }}</td>
                                @endif
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
                        <td style="padding: 10px 8px; text-align: center;">{!! $barChart($section['sparkline']) !!}</td>
                        @if ($hasCompare)
                            @php $secDelta = $section['totals'][0] - $section['totals'][1]; @endphp
                            <td style="padding: 10px 8px; text-align: right; font-size: 12px; font-weight: 700; font-variant-numeric: tabular-nums; {{ $deltaColor($secDelta) }}">
                                {{ $fmtSigned($secDelta) }}</td>
                            <td style="padding: 10px 8px; text-align: right; font-size: 12px; font-weight: 700; font-variant-numeric: tabular-nums; {{ $deltaColor($secDelta) }}">
                                {{ $pctFmt($section['totals'][0], $section['totals'][1]) }}</td>
                        @endif
                    </tr>
                @endforeach

                <tr><td colspan="{{ count($periods) + ($hasCompare ? 4 : 2) }}" style="padding: 8px;"></td></tr>

                {{-- Total Beban Keseluruhan --}}
                <tr style="background: rgba(239, 68, 68, 0.05); border-top: 2px solid rgba(239, 68, 68, 0.2);">
                    <td style="padding: 12px 8px; font-size: 14px; font-weight: 700;" class="text-red-600 dark:text-red-400">Total Beban</td>
                    @foreach ($periods as $idx => $period)
                        <td style="padding: 12px 8px; text-align: right; font-size: 14px; font-weight: 700; font-variant-numeric: tabular-nums;"
                            class="text-red-600 dark:text-red-400">{{ $fmtSigned($totalBebanAll[$idx]) }}</td>
                    @endforeach
                    <td style="padding: 12px 8px;"></td>
                    @if ($hasCompare)
                        @php $dtBeban = $totalBebanAll[0] - $totalBebanAll[1]; @endphp
                        <td style="padding: 12px 8px; text-align: right; font-size: 13px; font-weight: 700; font-variant-numeric: tabular-nums; {{ $deltaColor($dtBeban) }}">
                            {{ $fmtSigned($dtBeban) }}</td>
                        <td style="padding: 12px 8px; text-align: right; font-size: 13px; font-weight: 700; font-variant-numeric: tabular-nums; {{ $deltaColor($dtBeban) }}">
                            {{ $pctFmt($totalBebanAll[0], $totalBebanAll[1]) }}</td>
                    @endif
                </tr>

                <tr><td colspan="{{ count($periods) + ($hasCompare ? 4 : 2) }}" style="padding: 16px;"></td></tr>

                {{-- NET INCOME --}}
                <tr style="background: rgba(16, 185, 129, 0.1); border-top: 2px solid rgba(16, 185, 129, 0.3);">
                    <td style="padding: 14px 8px; font-size: 15px; font-weight: 800;" class="text-emerald-700 dark:text-emerald-400">Laba Bersih</td>
                    @foreach ($periods as $idx => $period)
                        <td style="padding: 14px 8px; text-align: right; font-size: 15px; font-weight: 800; font-variant-numeric: tabular-nums;"
                            class="text-emerald-700 dark:text-emerald-400">{{ $fmtSigned($labaBersihTotals[$idx]) }}</td>
                    @endforeach
                    <td style="padding: 14px 8px;"></td>
                    @if ($hasCompare)
                        @php $dtNet = $labaBersihTotals[0] - $labaBersihTotals[1]; @endphp
                        <td style="padding: 14px 8px; text-align: right; font-size: 14px; font-weight: 800; font-variant-numeric: tabular-nums; {{ $deltaColor($dtNet) }}">
                            {{ $fmtSigned($dtNet) }}</td>
                        <td style="padding: 14px 8px; text-align: right; font-size: 14px; font-weight: 800; font-variant-numeric: tabular-nums; {{ $deltaColor($dtNet) }}">
                            {{ $pctFmt($labaBersihTotals[0], $labaBersihTotals[1]) }}</td>
                    @endif
                </tr>

            </table>
        </div>
    </x-filament::section>

</x-filament-panels::page>
