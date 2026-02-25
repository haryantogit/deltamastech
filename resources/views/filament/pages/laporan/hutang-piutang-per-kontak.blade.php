<x-filament-panels::page>
    @php
        $fmt = function ($num) {
            if ($num == 0) return '0';
            $prefix = $num < 0 ? '(' : '';
            $suffix = $num < 0 ? ')' : '';
            return $prefix . number_format(abs($num), 0, ',', '.') . $suffix;
        };

        $valColor = function ($val) {
            if ($val > 0) return 'color:#22c55e;';
            if ($val < 0) return 'color:#ef4444;';
            return '';
        };

        $totalTransaksi = count($rows);
    @endphp

    <x-filament::section>
        <x-slot name="heading">
            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                <span style="color: #3b82f6; font-weight: 700; font-size: 15px;">Hutang Piutang per Kontak</span>
                <span style="font-size: 13px; color: #94a3b8;">{{ $today }}</span>
            </div>
        </x-slot>

        <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
            <colgroup>
                <col style="width: 36px">
                <col style="width: auto">
                <col style="width: 160px">
                <col style="width: 160px">
                <col style="width: 170px">
            </colgroup>

            {{-- Header --}}
            <thead>
                <tr style="border-bottom: 2px solid rgba(128,128,128,0.15);">
                    <th style="padding:12px 4px;"></th>
                    <th style="padding:12px 8px;font-size:12px;font-weight:600;text-align:left;" class="text-gray-500 dark:text-gray-400">Pelanggan</th>
                    <th style="padding:12px 8px;font-size:12px;font-weight:600;text-align:right;" class="text-gray-500 dark:text-gray-400">Hutang ↕</th>
                    <th style="padding:12px 8px;font-size:12px;font-weight:600;text-align:right;" class="text-gray-500 dark:text-gray-400">Piutang ↕</th>
                    <th style="padding:12px 8px;font-size:12px;font-weight:600;text-align:right;" class="text-gray-500 dark:text-gray-400">Net ↕</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($rows as $idx => $row)
                    {{-- Contact Row --}}
                    <tr style="border-bottom: 1px solid rgba(128,128,128,0.1); cursor: pointer;"
                        onclick="
                            var el = document.getElementById('detail-{{ $idx }}');
                            var btn = document.getElementById('btn-{{ $idx }}');
                            if (el.style.display === 'none') {
                                el.style.display = 'table-row';
                                btn.textContent = '−';
                            } else {
                                el.style.display = 'none';
                                btn.textContent = '+';
                            }
                        "
                        class="hover:bg-gray-50/50 dark:hover:bg-white/[0.03]">
                        <td style="padding:10px 4px;text-align:center;">
                            <span id="btn-{{ $idx }}"
                                  style="display:inline-flex;align-items:center;justify-content:center;width:24px;height:24px;border-radius:6px;border:1px solid rgba(128,128,128,0.2);font-size:16px;font-weight:600;line-height:1;user-select:none;"
                                  class="text-gray-400 dark:text-gray-500">+</span>
                        </td>
                        <td style="padding:10px 8px;font-size:13px;font-weight:600;color:#3b82f6;">
                            {{ $row['contact'] }}</td>
                        <td style="padding:10px 8px;text-align:right;font-size:13px;font-weight:600;font-variant-numeric:tabular-nums; {{ $row['hutang'] > 0 ? 'color:#ef4444;' : '' }}">
                            {{ $fmt($row['hutang']) }}</td>
                        <td style="padding:10px 8px;text-align:right;font-size:13px;font-weight:600;font-variant-numeric:tabular-nums; {{ $row['piutang'] > 0 ? 'color:#22c55e;' : '' }}">
                            {{ $fmt($row['piutang']) }}</td>
                        <td style="padding:10px 8px;text-align:right;font-size:13px;font-weight:700;font-variant-numeric:tabular-nums; {{ $valColor($row['net']) }}">
                            {{ $fmt($row['net']) }}</td>
                    </tr>

                    {{-- Expandable Detail Row --}}
                    <tr id="detail-{{ $idx }}" style="display:none;">
                        <td colspan="5" style="padding:0;">
                            <div style="border-bottom:2px solid rgba(128,128,128,0.12);" class="bg-gray-50/50 dark:bg-white/[0.02]">
                                <table style="width:100%;border-collapse:collapse;">
                                    <thead>
                                        <tr style="border-bottom:1px solid rgba(128,128,128,0.1);">
                                            <th style="padding:8px 8px 8px 48px;font-size:11px;font-weight:600;text-align:left;width:120px;" class="text-gray-400 dark:text-gray-500">Tanggal</th>
                                            <th style="padding:8px;font-size:11px;font-weight:600;text-align:left;width:140px;" class="text-gray-400 dark:text-gray-500">Nomor</th>
                                            <th style="padding:8px;font-size:11px;font-weight:600;text-align:left;" class="text-gray-400 dark:text-gray-500">Deskripsi</th>
                                            <th style="padding:8px;font-size:11px;font-weight:600;text-align:right;width:140px;" class="text-gray-400 dark:text-gray-500">Hutang</th>
                                            <th style="padding:8px;font-size:11px;font-weight:600;text-align:right;width:140px;" class="text-gray-400 dark:text-gray-500">Piutang</th>
                                            <th style="padding:8px;font-size:11px;font-weight:600;text-align:right;width:150px;" class="text-gray-400 dark:text-gray-500">Net</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($row['details'] as $detail)
                                            <tr style="border-bottom:1px solid rgba(128,128,128,0.06);">
                                                <td style="padding:7px 8px 7px 48px;font-size:12px;" class="text-gray-500 dark:text-gray-400">{{ $detail['tanggal'] }}</td>
                                                <td style="padding:7px 8px;font-size:12px;color:#3b82f6;font-weight:500;">{{ $detail['nomor'] }}</td>
                                                <td style="padding:7px 8px;font-size:12px;" class="text-gray-500 dark:text-gray-400">{{ $detail['deskripsi'] }}</td>
                                                <td style="padding:7px 8px;text-align:right;font-size:12px;font-variant-numeric:tabular-nums; {{ $detail['hutang'] > 0 ? 'color:#ef4444;' : '' }}">
                                                    {{ $fmt($detail['hutang']) }}</td>
                                                <td style="padding:7px 8px;text-align:right;font-size:12px;font-variant-numeric:tabular-nums; {{ $detail['piutang'] > 0 ? 'color:#22c55e;' : '' }}">
                                                    {{ $fmt($detail['piutang']) }}</td>
                                                <td style="padding:7px 8px;text-align:right;font-size:12px;font-weight:500;font-variant-numeric:tabular-nums; {{ $valColor($detail['net']) }}">
                                                    {{ $fmt($detail['net']) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                @endforeach

                @if (count($rows) === 0)
                    <tr>
                        <td colspan="5" style="padding:24px;text-align:center;font-size:13px;" class="text-gray-400 dark:text-gray-500">
                            Tidak ada data hutang piutang
                        </td>
                    </tr>
                @endif

                {{-- Total Hutang/Piutang --}}
                <tr style="border-top: 2px solid rgba(128,128,128,0.2);">
                    <td style="padding:12px 4px;"></td>
                    <td style="padding:12px 8px;font-size:13px;font-weight:700;" class="text-gray-900 dark:text-gray-100">
                        Total Hutang/Piutang</td>
                    <td style="padding:12px 8px;text-align:right;font-size:13px;font-weight:700;font-variant-numeric:tabular-nums; {{ $totalHutang > 0 ? 'color:#ef4444;' : '' }}">
                        {{ $fmt($totalHutang) }}</td>
                    <td style="padding:12px 8px;text-align:right;font-size:13px;font-weight:700;font-variant-numeric:tabular-nums; {{ $totalPiutang > 0 ? 'color:#22c55e;' : '' }}">
                        {{ $fmt($totalPiutang) }}</td>
                    <td style="padding:12px 8px;text-align:right;font-size:13px;font-weight:700;font-variant-numeric:tabular-nums; {{ $valColor($netTotal) }}">
                        {{ $fmt($netTotal) }}</td>
                </tr>

                {{-- Total Transaksi --}}
                <tr style="border-top: 1px solid rgba(128,128,128,0.15); background: rgba(59,130,246,0.04);">
                    <td style="padding:12px 4px;"></td>
                    <td style="padding:12px 8px;font-size:13px;font-weight:700;" class="text-gray-900 dark:text-gray-100">
                        Total Transaksi</td>
                    <td style="padding:12px 8px;text-align:right;font-size:13px;font-weight:700;font-variant-numeric:tabular-nums; {{ $totalHutang > 0 ? 'color:#ef4444;' : '' }}">
                        {{ $fmt($totalHutang) }}</td>
                    <td style="padding:12px 8px;text-align:right;font-size:13px;font-weight:700;font-variant-numeric:tabular-nums; {{ $totalPiutang > 0 ? 'color:#22c55e;' : '' }}">
                        {{ $fmt($totalPiutang) }}</td>
                    <td style="padding:12px 8px;text-align:right;font-size:13px;font-weight:700;font-variant-numeric:tabular-nums; {{ $valColor($netTotal) }}">
                        {{ $fmt($netTotal) }}</td>
                </tr>
            </tbody>
        </table>

    </x-filament::section>
</x-filament-panels::page>