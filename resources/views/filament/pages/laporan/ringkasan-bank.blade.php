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

    <style>
        /* Tab Buttons Styling */
        .custom-tab-button {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 0.4rem 1.25rem;
            border-radius: 6px;
            font-size: 0.8125rem;
            font-weight: 700;
            cursor: pointer;
            border: none;
        }

        .custom-tab-button.active {
            background-color: #3b82f6;
            color: white;
            box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.2), 0 2px 4px -1px rgba(59, 130, 246, 0.1);
        }

        .custom-tab-button.inactive {
            background-color: transparent;
            color: #64748b;
        }

        .custom-tab-button.inactive:hover {
            color: #3b82f6;
            background-color: rgba(59, 130, 246, 0.05);
        }

        .dark .custom-tab-button.inactive {
            color: #94a3b8;
        }

        .dark .custom-tab-button.inactive:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }
    </style>


    <x-filament::section class="-mt-4">
        <x-slot name="heading">
            <span style="color: #16a34a; font-weight: 700; font-size: 15px;">Ringkasan Kas &amp; Bank</span>
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
                <tr style="background: rgba(148, 163, 184, 0.15); border-bottom: 2px solid rgba(128,128,128,0.2);">
                    <th style="padding:12px 8px;font-size:12px;font-weight:600;text-align:left;"
                        class="text-gray-500 dark:text-gray-400"></th>
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