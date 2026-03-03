<x-filament-panels::page>
    @php
        $viewData = $this->getViewData();
        $rows = $viewData['rows'];
        $paginator = $viewData['paginator'];
        $totalHutang = $viewData['totalHutang'];
        $totalPiutang = $viewData['totalPiutang'];
        $netTotal = $viewData['netTotal'];

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
        .expand-btn {
            width: 28px;
            height: 28px;
            background: #eff6ff;
            border: 1px solid #dbeafe;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #3b82f6;
            font-weight: 700;
            font-size: 1.1rem;
            transition: all 0.2s ease;
            user-select: none;
            flex-shrink: 0;
        }

        .expand-btn:hover {
            background: #dbeafe;
            color: #2563eb;
            transform: translateY(-1px);
        }

        .dark .expand-btn {
            background: rgba(59, 130, 246, 0.1);
            border-color: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
        }

        .delivery-report-container {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        .dark .delivery-report-container {
            background: #111827;
            border-color: #374151;
        }

        .report-table th {
            padding: 0.875rem 1.25rem;
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            text-transform: capitalize;
            background: #f8fafc;
            border-bottom: 2px solid #f1f5f9;
        }

        .dark .report-table th {
            background: #1f2937;
            border-bottom-color: #374151;
            color: #94a3b8;
        }

        .report-table td {
            padding: 0.75rem 1.25rem;
            font-size: 0.8125rem;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
        }

        .dark .report-table td {
            border-bottom-color: #374151;
            color: #e2e8f0;
        }

        .nested-table-header th {
            padding: 0.5rem 1.25rem;
            font-size: 0.70rem;
            color: #94a3b8;
            font-weight: 600;
            background: #fcfdfe;
            border-bottom: 1px solid #f1f5f9;
        }

        .dark .nested-table-header th {
            background: rgba(255, 255, 255, 0.01);
            border-bottom-color: #1f2937;
        }

        .nested-table-row td {
            padding: 0.5rem 1.25rem;
            font-size: 0.75rem;
            border-bottom: 1px solid #f8fafc;
        }

        .dark .nested-table-row td {
            border-bottom-color: rgba(255, 255, 255, 0.05);
        }

        .grand-total-container {
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            border-top: 2px solid #e2e8f0;
            background: #f8fafc;
        }

        .dark .grand-total-container {
            border-top-color: #374151;
            background: #111827;
        }

        @media print {

            .expand-btn,
            .pagination-row {
                display: none !important;
            }
        }
    </style>





    <div class="delivery-report-container">
        {{-- Search row --}}
        <div style="padding: 1rem 1.25rem; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: flex-end;"
            class="dark:border-gray-800">
            <div style="position: relative; width: 280px;">
                <svg style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); width: 1rem; height: 1rem; color: #94a3b8;"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <input type="text" wire:model.live.debounce.500ms="search" placeholder="Cari"
                    style="width: 100%; padding: 0.5rem 0.75rem 0.5rem 2.25rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.8125rem; background: white; color: #1e293b; outline: none;"
                    class="dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100">
            </div>
        </div>

        <div style="overflow-x: auto;">
            <table class="report-table" style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                <colgroup>
                    <col style="width: auto">
                    <col style="width: 180px">
                    <col style="width: 180px">
                    <col style="width: 180px">
                </colgroup>

                {{-- Header --}}
                <thead>
                    <tr>
                        <th style="padding-left: 1.5rem; text-align:left;">Pelanggan</th>
                        <th style="text-align:right;">Hutang ↕</th>
                        <th style="text-align:right;">Piutang ↕</th>
                        <th style="text-align:right;">Net ↕</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($rows as $idx => $row)
                        {{-- Contact Row --}}
                        <tr style="cursor: pointer;" onclick="
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
                            <td style="font-weight:600;color:#3b82f6; display: flex; align-items: center; gap: 0.75rem;">
                                <div id="btn-{{ $idx }}" class="expand-btn">+</div>
                                {{ $row['contact'] }}
                            </td>
                            <td
                                style="text-align:right;font-weight:600;font-variant-numeric:tabular-nums; {{ $row['hutang'] > 0 ? 'color:#ef4444;' : '' }}">
                                {{ $fmt($row['hutang']) }}
                            </td>
                            <td
                                style="text-align:right;font-weight:600;font-variant-numeric:tabular-nums; {{ $row['piutang'] > 0 ? 'color:#22c55e;' : '' }}">
                                {{ $fmt($row['piutang']) }}
                            </td>
                            <td
                                style="text-align:right;font-weight:700;font-variant-numeric:tabular-nums; {{ $valColor($row['net']) }}">
                                {{ $fmt($row['net']) }}
                            </td>
                        </tr>

                        {{-- Expandable Detail Row --}}
                        <tr id="detail-{{ $idx }}" style="display:none;">
                            <td colspan="4" style="padding:0; border-bottom: 2px solid transparent;">
                                <div style="background: #fcfdfe;" class="dark:bg-white/[0.01]">
                                    <table style="width:100%;border-collapse:collapse;">
                                        <thead>
                                            <tr class="nested-table-header">
                                                <th style="padding-left: 60px; width:120px; text-align: left;">Tanggal
                                                </th>
                                                <th style="width:140px; text-align: left;">Nomor</th>
                                                <th style="text-align: left;">Deskripsi</th>
                                                <th style="width:140px; text-align: right;">Hutang</th>
                                                <th style="width:140px; text-align: right;">Piutang</th>
                                                <th style="width:150px; text-align: right;">Net</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($row['details'] as $detail)
                                                <tr class="nested-table-row">
                                                    <td style="padding-left: 60px;" class="text-gray-500 dark:text-gray-400">
                                                        {{ $detail['tanggal'] }}
                                                    </td>
                                                    <td style="color:#3b82f6;font-weight:500;">{{ $detail['nomor'] }}</td>
                                                    <td class="text-gray-500 dark:text-gray-400">{{ $detail['deskripsi'] }}
                                                    </td>
                                                    <td
                                                        style="text-align:right;font-variant-numeric:tabular-nums; {{ $detail['hutang'] > 0 ? 'color:#ef4444;' : '' }}">
                                                        {{ $fmt($detail['hutang']) }}
                                                    </td>
                                                    <td
                                                        style="text-align:right;font-variant-numeric:tabular-nums; {{ $detail['piutang'] > 0 ? 'color:#22c55e;' : '' }}">
                                                        {{ $fmt($detail['piutang']) }}
                                                    </td>
                                                    <td
                                                        style="text-align:right;font-weight:500;font-variant-numeric:tabular-nums; {{ $valColor($detail['net']) }}">
                                                        {{ $fmt($detail['net']) }}
                                                    </td>
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
                            <td colspan="4" style="padding:24px;text-align:center;font-size:13px;"
                                class="text-gray-400 dark:text-gray-500">
                                Tidak ada data hutang piutang
                            </td>
                        </tr>
                    @endif

                    {{-- Total Hutang/Piutang --}}
                    <tr style="border-top: 2px solid rgba(128,128,128,0.2);">
                        <td style="padding:16px 8px;font-size:13px; padding-left: 1.5rem;"
                            class="text-slate-900 dark:text-white">
                            <div style="font-weight: 700;">Total Hutang/Piutang (Semua)</div>
                            <div style="font-weight: 500; font-size: 11px; color: #64748b; margin-top: 4px;">Total
                                Kontak Filtered: {{ $paginator->total() }}</div>
                        </td>
                        <td
                            style="padding:16px 8px;text-align:right;font-size:13px;font-weight:700;font-variant-numeric:tabular-nums; {{ $totalHutang > 0 ? 'color:#ef4444;' : '' }}">
                            {{ $fmt($totalHutang) }}
                        </td>
                        <td
                            style="padding:16px 8px;text-align:right;font-size:13px;font-weight:700;font-variant-numeric:tabular-nums; {{ $totalPiutang > 0 ? 'color:#22c55e;' : '' }}">
                            {{ $fmt($totalPiutang) }}
                        </td>
                        <td
                            style="padding:16px 8px;text-align:right;font-size:13px;font-weight:700;font-variant-numeric:tabular-nums; {{ $valColor($netTotal) }}">
                            {{ $fmt($netTotal) }}
                        </td>
                    </tr>
                </tbody>
            </table>

        </div>
    </div>

    @if ($paginator->hasPages() || count([5, 10, 20, 50, 100, 'all']) > 1)
        <div style="margin-top: 1.5rem; margin-bottom: 1rem;">
            <x-filament::pagination :paginator="$paginator" :page-options="[5, 10, 20, 50, 100, 'all']"
                current-page-option-property="perPage" />
        </div>
    @endif
</x-filament-panels::page>