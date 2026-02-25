<x-filament-panels::page>
    @php
        $fmt = function ($num, $decimals = 0) {
            if ($num == 0)
                return '0';
            $prefix = $num < 0 ? '(' : '';
            $suffix = $num < 0 ? ')' : '';
            return $prefix . number_format(abs($num), $decimals, ',', '.') . $suffix;
        };

        $trend = function ($current, $previous) {
            if ($previous == 0) {
                $pct = $current != 0 ? 100 : 0;
            } else {
                $pct = round((($current - $previous) / abs($previous)) * 100, 1);
            }
            $icon = $pct >= 0 ? '↑' : '↓';
            $color = $pct >= 0 ? '#22c55e' : '#ef4444';
            return '<span style="font-size:12px;font-weight:600;color:' . $color . '">' . $icon . ' ' . number_format(abs($pct), 1, ',', '.') . '%</span> <span style="font-size:11px;color:#94a3b8;">vs bulan lalu</span>';
        };

        $valColor = function ($val) {
            if ($val > 0)
                return 'color:#22c55e;';
            if ($val < 0)
                return 'color:#ef4444;';
            return '';
        };

        // Stat card component
        $statCard = function ($title, $value, $current, $previous) use ($trend) {
            $trendHtml = $trend($current, $previous);
            return '
                    <div style="flex:1;min-width:200px;padding:20px;border-radius:12px;border:1px solid rgba(128,128,128,0.15);background:rgba(128,128,128,0.03);">
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px;" class="text-gray-400 dark:text-gray-500">' . $title . '</div>
                        <div style="font-size:24px;font-weight:800;margin-bottom:6px;" class="text-gray-900 dark:text-white">' . $value . '</div>
                        <div>' . $trendHtml . '</div>
                    </div>';
        };

        // Table row style
        $rowStyle = 'border-bottom: 1px solid rgba(128,128,128,0.1);';
        $tdName = 'padding:12px 8px;font-size:13px;';
        $tdVal = 'padding:12px 8px;text-align:right;font-size:13px;font-weight:600;font-variant-numeric:tabular-nums;';
    @endphp

    {{-- =================== NERACA RATIOS =================== --}}
    <x-filament::section>
        <x-slot name="heading">
            <span style="color: #3b82f6; font-weight: 700; font-size: 15px;">Neraca</span>
        </x-slot>

        <div style="display:flex;gap:16px;flex-wrap:wrap;">
            {!! $statCard('Quick Ratio', $fmt($quickRatio, 1), $quickRatio, $prevQuickRatio) !!}
            {!! $statCard('Current Ratio', $fmt($currentRatio, 1), $currentRatio, $prevCurrentRatio) !!}
            {!! $statCard('Debt Equity Ratio', $fmt($debtEquityRatio, 1), $debtEquityRatio, $prevDebtEquityRatio) !!}
            {!! $statCard('Equity Ratio', $fmt($equityRatio, 1), $equityRatio, $prevEquityRatio) !!}
        </div>

        <div style="margin-top:16px;display:flex;gap:16px;flex-wrap:wrap;">
            {!! $statCard('Pengembalian Investasi / ROI (P.A.)', $roi . '%', $roi, $prevRoi) !!}
        </div>
    </x-filament::section>

    <div style="height:24px;"></div>

    {{-- =================== PERUBAHAN MODAL =================== --}}
    <x-filament::section>
        <x-slot name="heading">
            <span style="color: #8b5cf6; font-weight: 700; font-size: 15px;">Perubahan Modal</span>
        </x-slot>

        <div style="display:flex;gap:16px;flex-wrap:wrap;">
            {!! $statCard('Perubahan Modal', $fmt($perubahanModal), $perubahanModal, $prevPerubahanModal) !!}
            {!! $statCard('Saldo Modal', $fmt($saldoModal), $saldoModal, $prevSaldoModal) !!}
            {!! $statCard('Penambahan Modal', $fmt($penambahanModal), $penambahanModal, $prevPenambahanModal) !!}
            {!! $statCard('Pengurangan Modal', $fmt($penguranganModal), $penguranganModal, $prevPenguranganModal) !!}
        </div>
    </x-filament::section>

    <div style="height:24px;"></div>

    {{-- =================== PENDAPATAN STATS =================== --}}
    <x-filament::section>
        <x-slot name="heading">
            <span style="color: #16a34a; font-weight: 700; font-size: 15px;">Pendapatan</span>
        </x-slot>

        <div style="display:flex;gap:16px;flex-wrap:wrap;">
            {!! $statCard('Jumlah Tagihan Diterbitkan', $jumlahInvoice, $jumlahInvoice, $prevJumlahInvoice) !!}
            {!! $statCard('Rata-rata Nilai Tagihan', $fmt($avgInvoice), $avgInvoice, $prevAvgInvoice) !!}
            {!! $statCard('Rata-rata Lama Konversi Piutang', $avgDSO . ' Hari', $avgDSO, 0) !!}
            {!! $statCard('Rata-rata Lama Konversi Hutang', $avgDPO . ' Hari', $avgDPO, 0) !!}
        </div>
    </x-filament::section>

    <div style="height:32px;"></div>

    {{-- =================== KAS TABLE =================== --}}
    <x-filament::section>
        <x-slot name="heading">
            <span style="color: #0ea5e9; font-weight: 700; font-size: 15px;">Kas</span>
        </x-slot>

        <table style="width:100%;border-collapse:collapse;">
            <tr style="{{ $rowStyle }}">
                <td style="{{ $tdName }}" class="text-gray-600 dark:text-gray-400">Kas masuk</td>
                <td style="{{ $tdVal }}" class="text-gray-900 dark:text-white">{{ $fmt($kasIn) }}</td>
            </tr>
            <tr style="{{ $rowStyle }}">
                <td style="{{ $tdName }}" class="text-gray-600 dark:text-gray-400">Kas keluar</td>
                <td style="{{ $tdVal }} {{ $valColor(-$kasOut) }}">{{ $fmt($kasOut) }}</td>
            </tr>
            <tr style="border-top:2px solid rgba(128,128,128,0.2);background:rgba(14,165,233,0.06);">
                <td style="padding:14px 8px;font-size:14px;font-weight:800;" class="text-gray-900 dark:text-white">Kas
                    bersih</td>
                <td
                    style="padding:14px 8px;text-align:right;font-size:14px;font-weight:800;font-variant-numeric:tabular-nums; {{ $valColor($kasTotal) }}">
                    {{ $fmt($kasTotal) }}
                </td>
            </tr>
        </table>
    </x-filament::section>

    <div style="height:24px;"></div>

    {{-- =================== PROFITABILITAS =================== --}}
    <x-filament::section>
        <x-slot name="heading">
            <span style="color: #d97706; font-weight: 700; font-size: 15px;">Profitabilitas</span>
        </x-slot>

        <table style="width:100%;border-collapse:collapse;">
            <tr style="{{ $rowStyle }}">
                <td style="{{ $tdName }}" class="text-gray-600 dark:text-gray-400">Pendapatan</td>
                <td style="{{ $tdVal }} {{ $valColor($pendapatan) }}">{{ $fmt($pendapatan) }}</td>
            </tr>
            <tr style="{{ $rowStyle }}">
                <td style="{{ $tdName }}" class="text-gray-600 dark:text-gray-400">Biaya penjualan</td>
                <td style="{{ $tdVal }}">{{ $fmt($hpp) }}</td>
            </tr>
            <tr style="{{ $rowStyle }}">
                <td style="{{ $tdName }};font-weight:700;" class="text-gray-800 dark:text-gray-200">Laba kotor</td>
                <td style="{{ $tdVal }};font-weight:700; {{ $valColor($labaKotor) }}">{{ $fmt($labaKotor) }}</td>
            </tr>
            <tr style="{{ $rowStyle }}">
                <td style="{{ $tdName }}" class="text-gray-600 dark:text-gray-400">Biaya</td>
                <td style="{{ $tdVal }}">{{ $fmt($biaya) }}</td>
            </tr>
            <tr style="border-top:2px solid rgba(217,119,6,0.3);background:rgba(217,119,6,0.06);">
                <td style="padding:14px 8px;font-size:14px;font-weight:800;" class="text-gray-900 dark:text-white">Laba
                    bersih</td>
                <td
                    style="padding:14px 8px;text-align:right;font-size:14px;font-weight:800;font-variant-numeric:tabular-nums; {{ $valColor($labaBersih) }}">
                    {{ $fmt($labaBersih) }}
                </td>
            </tr>
        </table>
    </x-filament::section>

    <div style="height:24px;"></div>

    {{-- =================== NERACA SUMMARY =================== --}}
    <x-filament::section>
        <x-slot name="heading">
            <span style="color: #3b82f6; font-weight: 700; font-size: 15px;">Neraca</span>
        </x-slot>

        <table style="width:100%;border-collapse:collapse;">
            <tr style="{{ $rowStyle }}">
                <td style="{{ $tdName }}" class="text-gray-600 dark:text-gray-400">Aset</td>
                <td style="{{ $tdVal }} {{ $valColor($totalAset) }}">{{ $fmt($totalAset) }}</td>
            </tr>
            <tr style="{{ $rowStyle }}">
                <td style="{{ $tdName }}" class="text-gray-600 dark:text-gray-400">Liabilitas</td>
                <td style="{{ $tdVal }} {{ $valColor($totalLiabilitas) }}">{{ $fmt($totalLiabilitas) }}</td>
            </tr>
            <tr style="border-top:2px solid rgba(59,130,246,0.3);background:rgba(59,130,246,0.06);">
                <td style="padding:14px 8px;font-size:14px;font-weight:800;" class="text-gray-900 dark:text-white">Modal
                    pemilik</td>
                <td
                    style="padding:14px 8px;text-align:right;font-size:14px;font-weight:800;font-variant-numeric:tabular-nums; {{ $valColor($ekuitas) }}">
                    {{ $fmt($ekuitas) }}
                </td>
            </tr>
        </table>
    </x-filament::section>

    <div style="height:24px;"></div>

    {{-- =================== PENDAPATAN DETAIL =================== --}}
    <x-filament::section>
        <x-slot name="heading">
            <span style="color: #16a34a; font-weight: 700; font-size: 15px;">Pendapatan</span>
        </x-slot>

        <table style="width:100%;border-collapse:collapse;">
            <tr style="{{ $rowStyle }}">
                <td style="{{ $tdName }}" class="text-gray-600 dark:text-gray-400">Jumlah tagihan di-invoicekan</td>
                <td style="{{ $tdVal }}" class="text-gray-900 dark:text-white">{{ $jumlahInvoice }}</td>
            </tr>
            <tr style="{{ $rowStyle }}">
                <td style="{{ $tdName }}" class="text-gray-600 dark:text-gray-400">Rata-rata satu tagihan</td>
                <td style="{{ $tdVal }} {{ $valColor($avgInvoice) }}">{{ $fmt($avgInvoice) }}</td>
            </tr>
        </table>
    </x-filament::section>

    <div style="height:24px;"></div>

    {{-- =================== PERFORMA =================== --}}
    <x-filament::section>
        <x-slot name="heading">
            <span style="color: #dc2626; font-weight: 700; font-size: 15px;">Performa</span>
        </x-slot>

        <table style="width:100%;border-collapse:collapse;">
            <tr style="{{ $rowStyle }}">
                <td style="{{ $tdName }}" class="text-gray-600 dark:text-gray-400">Margin laba kotor</td>
                <td style="{{ $tdVal }}" class="text-gray-900 dark:text-white">{{ $marginLabaKotor }}%</td>
            </tr>
            <tr style="{{ $rowStyle }}">
                <td style="{{ $tdName }}" class="text-gray-600 dark:text-gray-400">Margin laba bersih</td>
                <td style="{{ $tdVal }}" class="text-gray-900 dark:text-white">{{ $marginLabaBersih }}%</td>
            </tr>
            <tr style="{{ $rowStyle }}">
                <td style="{{ $tdName }}" class="text-gray-600 dark:text-gray-400">Pengembalian investasi / ROI (p.a.)
                </td>
                <td style="{{ $tdVal }}" class="text-gray-900 dark:text-white">{{ $roi }}%</td>
            </tr>
        </table>
    </x-filament::section>

    <div style="height:24px;"></div>

    {{-- =================== POSISI =================== --}}
    <x-filament::section>
        <x-slot name="heading">
            <span style="color: #0284c7; font-weight: 700; font-size: 15px;">Posisi</span>
        </x-slot>

        <table style="width:100%;border-collapse:collapse;">
            <tr style="{{ $rowStyle }}">
                <td style="{{ $tdName }}" class="text-gray-600 dark:text-gray-400">Rasio aset terhadap kewajiban
                    (lancar)</td>
                <td style="{{ $tdVal }}" class="text-gray-900 dark:text-white">{{ $fmt($rasioAsetKewajiban, 2) }}</td>
            </tr>
            <tr style="{{ $rowStyle }}">
                <td style="{{ $tdName }}" class="text-gray-600 dark:text-gray-400">Rasio hutang terhadap ekuitas</td>
                <td style="{{ $tdVal }}" class="text-gray-900 dark:text-white">{{ $fmt($rasioHutangEkuitas, 2) }}</td>
            </tr>
            <tr style="{{ $rowStyle }}">
                <td style="{{ $tdName }}" class="text-gray-600 dark:text-gray-400">Rasio hutang terhadap aset</td>
                <td style="{{ $tdVal }}" class="text-gray-900 dark:text-white">{{ $fmt($rasioHutangAset, 2) }}</td>
            </tr>
            <tr style="{{ $rowStyle }}">
                <td style="{{ $tdName }}" class="text-gray-600 dark:text-gray-400">Rasio aset terhadap liabilitas</td>
                <td style="{{ $tdVal }}" class="text-gray-900 dark:text-white">{{ $fmt($rasioAsetLiabilitas, 2) }}</td>
            </tr>
        </table>
    </x-filament::section>
</x-filament-panels::page>