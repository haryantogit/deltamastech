<x-filament-panels::page>
    @php
        $fmtSigned = function ($num) {
            if ($num == 0)
                return '0';
            $prefix = $num < 0 ? '(' : '';
            $suffix = $num < 0 ? ')' : '';
            return $prefix . number_format(abs($num), 0, ',', '.') . $suffix;
        };

        $valColor = function ($val) {
            if ($val > 0)
                return 'color:#22c55e;';
            if ($val < 0)
                return 'color:#ef4444;';
            return '';
        };
    @endphp

    {{-- =================== PERUBAHAN MODAL =================== --}}
    <x-filament::section>
        <x-slot name="heading">
            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                <span style="color: #8b5cf6; font-weight: 700; font-size: 15px;">Perubahan Modal</span>
                <span style="font-size: 13px; color: #94a3b8;">{{ $today }}</span>
            </div>
        </x-slot>

        <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
            <colgroup>
                <col style="width: auto">
                <col style="width: 140px">
                <col style="width: 130px">
                <col style="width: 130px">
                <col style="width: 140px">
            </colgroup>

            {{-- Column Headers --}}
            <thead>
                <tr style="border-bottom: 2px solid rgba(128,128,128,0.15);">
                    <th style="padding:12px 8px;font-size:12px;font-weight:600;text-align:left;"
                        class="text-gray-500 dark:text-gray-400">Akun</th>
                    <th style="padding:12px 8px;font-size:12px;font-weight:600;text-align:right;"
                        class="text-gray-500 dark:text-gray-400">Awal</th>
                    <th style="padding:12px 8px;font-size:12px;font-weight:600;text-align:right;"
                        class="text-gray-500 dark:text-gray-400">Debit</th>
                    <th style="padding:12px 8px;font-size:12px;font-weight:600;text-align:right;"
                        class="text-gray-500 dark:text-gray-400">Credit</th>
                    <th style="padding:12px 8px;font-size:12px;font-weight:600;text-align:right;"
                        class="text-gray-500 dark:text-gray-400">Akhir</th>
                </tr>
            </thead>

            {{-- Period Label --}}
            <tbody>
                <tr>
                    <td colspan="5" style="padding: 14px 8px 8px; font-size: 14px; font-weight: 600;"
                        class="text-gray-800 dark:text-gray-200">
                        Periode: {{ $periodLabel }}
                    </td>
                </tr>

                {{-- Account Rows --}}
                @foreach ($rows as $row)
                    <tr style="border-bottom: 1px solid rgba(128,128,128,0.1);">
                        <td style="padding: 10px 8px 10px 24px; font-size: 13px;" class="text-gray-600 dark:text-gray-400">
                            {{ $row['code'] }} {{ $row['name'] }}
                        </td>
                        <td style="padding: 10px 8px; text-align: right; font-size: 13px; font-weight: 500; font-variant-numeric: tabular-nums;"
                            class="text-gray-700 dark:text-gray-300">
                            {{ $fmtSigned($row['awal']) }}
                        </td>
                        <td style="padding: 10px 8px; text-align: right; font-size: 13px; font-weight: 500; font-variant-numeric: tabular-nums;"
                            class="text-gray-700 dark:text-gray-300">
                            {{ $fmtSigned($row['debit']) }}
                        </td>
                        <td
                            style="padding: 10px 8px; text-align: right; font-size: 13px; font-weight: 500; font-variant-numeric: tabular-nums; {{ $valColor($row['credit']) }}">
                            {{ $fmtSigned($row['credit']) }}
                        </td>
                        <td style="padding: 10px 8px; text-align: right; font-size: 13px; font-weight: 600; font-variant-numeric: tabular-nums;"
                            class="{{ $row['akhir'] < 0 ? 'text-red-500' : 'text-gray-900 dark:text-gray-100' }}">
                            {{ $fmtSigned($row['akhir']) }}
                        </td>
                    </tr>
                @endforeach

                {{-- Total Row --}}
                <tr style="border-top: 3px solid rgba(139,92,246,0.3); background: rgba(139,92,246,0.06);">
                    <td style="padding: 14px 8px; font-size: 14px; font-weight: 800;"
                        class="text-gray-900 dark:text-white">
                        Total</td>
                    <td style="padding: 14px 8px; text-align: right; font-size: 14px; font-weight: 800; font-variant-numeric: tabular-nums;"
                        class="text-gray-900 dark:text-white">
                        {{ $fmtSigned($totalAwal) }}
                    </td>
                    <td style="padding: 14px 8px; text-align: right; font-size: 14px; font-weight: 800; font-variant-numeric: tabular-nums;"
                        class="text-gray-900 dark:text-white">
                        {{ $fmtSigned($totalDebit) }}
                    </td>
                    <td style="padding: 14px 8px; text-align: right; font-size: 14px; font-weight: 800; font-variant-numeric: tabular-nums;"
                        class="text-gray-900 dark:text-white">
                        {{ $fmtSigned($totalCredit) }}
                    </td>
                    <td style="padding: 14px 8px; text-align: right; font-size: 14px; font-weight: 800; font-variant-numeric: tabular-nums;"
                        class="text-gray-900 dark:text-white">
                        {{ $fmtSigned($totalAkhir) }}
                    </td>
                </tr>

                {{-- Pergerakan Row --}}
                <tr style="border-top: 2px solid rgba(128,128,128,0.15);">
                    <td colspan="3" style="padding: 14px 8px; font-size: 14px; font-weight: 700;"
                        class="text-gray-900 dark:text-gray-100">
                        Pergerakan</td>
                    <td colspan="2"
                        style="padding: 14px 8px; text-align: right; font-size: 15px; font-weight: 900; font-variant-numeric: tabular-nums; {{ $valColor($pergerakan) }}">
                        {{ $fmtSigned($pergerakan) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </x-filament::section>
</x-filament-panels::page>