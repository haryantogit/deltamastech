<x-filament-panels::page>
    @php
        $fmt = function ($num) {
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

    <x-filament::section>
        <x-slot name="heading">
            <div style="display:flex;justify-content:space-between;align-items:center;width:100%;">
                <span style="color:#3b82f6;font-weight:700;font-size:15px;">Ringkasan Bank</span>
            </div>
        </x-slot>

        <table style="width:100%;border-collapse:collapse;table-layout:fixed;">
            <colgroup>
                <col style="width:auto">
                <col style="width:160px">
                <col style="width:170px">
                <col style="width:170px">
                <col style="width:160px">
            </colgroup>

            {{-- Header --}}
            <thead>
                <tr style="border-bottom:2px solid rgba(128,128,128,0.15);">
                    <th style="padding:12px 8px;font-size:12px;font-weight:600;text-align:left;"
                        class="text-gray-500 dark:text-gray-400">Akun Bank</th>
                    <th style="padding:12px 8px;font-size:12px;font-weight:600;text-align:right;"
                        class="text-gray-500 dark:text-gray-400">Saldo Awal</th>
                    <th style="padding:12px 8px;font-size:12px;font-weight:600;text-align:right;"
                        class="text-gray-500 dark:text-gray-400">Uang Diterima</th>
                    <th style="padding:12px 8px;font-size:12px;font-weight:600;text-align:right;"
                        class="text-gray-500 dark:text-gray-400">Uang Dibelanjakan</th>
                    <th style="padding:12px 8px;font-size:12px;font-weight:600;text-align:right;"
                        class="text-gray-500 dark:text-gray-400">Saldo Akhir</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($rows as $row)
                    <tr style="border-bottom:1px solid rgba(128,128,128,0.1);"
                        class="hover:bg-gray-50/50 dark:hover:bg-white/[0.03]">
                        <td style="padding:12px 8px;font-size:13px;font-weight:600;">
                            <a href="#" wire:click.prevent="mountAction('viewTransaksi', { account_id: {{ $row['id'] }} })"
                                style="color:#3b82f6;text-decoration:none;" class="hover:underline">
                                {{ $row['name'] }}
                            </a>
                        </td>
                        <td style="padding:12px 8px;text-align:right;font-size:13px;font-variant-numeric:tabular-nums;"
                            class="text-gray-700 dark:text-gray-300">
                            {{ $fmt($row['saldoAwal']) }}
                        </td>
                        <td
                            style="padding:12px 8px;text-align:right;font-size:13px;font-variant-numeric:tabular-nums; {{ $row['masuk'] > 0 ? 'color:#22c55e;' : '' }}">
                            {{ $fmt($row['masuk']) }}
                        </td>
                        <td
                            style="padding:12px 8px;text-align:right;font-size:13px;font-variant-numeric:tabular-nums; {{ $row['keluar'] > 0 ? 'color:#ef4444;' : '' }}">
                            {{ $fmt($row['keluar']) }}
                        </td>
                        <td
                            style="padding:12px 8px;text-align:right;font-size:13px;font-weight:600;font-variant-numeric:tabular-nums; {{ $valColor($row['saldoAkhir']) }}">
                            {{ $fmt($row['saldoAkhir']) }}
                        </td>
                    </tr>
                @endforeach

                @if (count($rows) === 0)
                    <tr>
                        <td colspan="5" style="padding:24px;text-align:center;font-size:13px;"
                            class="text-gray-400 dark:text-gray-500">
                            Tidak ada akun Kas & Bank
                        </td>
                    </tr>
                @endif

                {{-- Total Row --}}
                <tr style="border-top:3px solid rgba(59,130,246,0.3);background:rgba(59,130,246,0.06);">
                    <td style="padding:14px 8px;font-size:14px;font-weight:800;" class="text-gray-900 dark:text-white">
                        Total</td>
                    <td style="padding:14px 8px;text-align:right;font-size:14px;font-weight:800;font-variant-numeric:tabular-nums;"
                        class="text-gray-900 dark:text-white">
                        {{ $fmt($totalSaldoAwal) }}
                    </td>
                    <td
                        style="padding:14px 8px;text-align:right;font-size:14px;font-weight:800;font-variant-numeric:tabular-nums; {{ $totalMasuk > 0 ? 'color:#22c55e;' : '' }}">
                        {{ $fmt($totalMasuk) }}
                    </td>
                    <td
                        style="padding:14px 8px;text-align:right;font-size:14px;font-weight:800;font-variant-numeric:tabular-nums; {{ $totalKeluar > 0 ? 'color:#ef4444;' : '' }}">
                        {{ $fmt($totalKeluar) }}
                    </td>
                    <td
                        style="padding:14px 8px;text-align:right;font-size:14px;font-weight:800;font-variant-numeric:tabular-nums; {{ $valColor($totalSaldoAkhir) }}">
                        {{ $fmt($totalSaldoAkhir) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </x-filament::section>
</x-filament-panels::page>