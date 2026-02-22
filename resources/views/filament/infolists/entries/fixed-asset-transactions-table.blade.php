@php
    $record = $getRecord();
    $purchasePrice = $record->purchase_price ?? 0;
    $salvageValue = $record->salvage_value ?? 0;
    $usefulLifeYears = $record->useful_life_years ?? 0;
    $usefulLifeMonths = $record->useful_life_months ?? 0;
    $rate = $record->depreciation_rate ?? 0;
    $method = $record->depreciation_method ?? 'straight_line';
    $startDate = $record->depreciation_start_date ? \Carbon\Carbon::parse($record->depreciation_start_date) : null;
    $purchaseDate = $record->purchase_date ? \Carbon\Carbon::parse($record->purchase_date) : null;

    $upgrades = $record->fixedAssetUpgrades()->get();
    $postedDepreciations = $record->fixedAssetDepreciations()->with('journalEntry')->get();
    $postedPeriods = $postedDepreciations->pluck('period')->toArray();

    $monthlyDepreciation = 0;
    $totalMonths = ($usefulLifeYears * 12) + $usefulLifeMonths;

    if ($method === 'straight_line') {
        if ($totalMonths > 0) {
            $monthlyDepreciation = round(($purchasePrice - $salvageValue) / $totalMonths);
        } elseif ($rate > 0) {
            $monthlyDepreciation = round(($purchasePrice * ($rate / 100)) / 12);
        }
    } else {
        if ($rate > 0) {
            $monthlyDepreciation = ($purchasePrice * ($rate / 100)) / 12;
        }
    }

    $events = collect([]);

    if ($purchaseDate) {
        $events->push([
            'date' => $purchaseDate,
            'source' => 'Pembelian',
            'description' => 'Pendaftaran Aset Tetap',
            'reference' => $record->sku ?? '-',
            'debit' => $purchasePrice,
            'credit' => 0,
            'type' => 'purchase',
            'status' => 'posted'
        ]);
    }

    foreach ($upgrades as $upgrade) {
        $events->push([
            'date' => $upgrade->date,
            'source' => 'Upgrade',
            'description' => $upgrade->description ?? 'Upgrade Aset',
            'reference' => $upgrade->reference ?? '-',
            'debit' => $upgrade->amount,
            'credit' => 0,
            'type' => 'upgrade',
            'status' => 'posted'
        ]);
    }

    foreach ($postedDepreciations as $posted) {
        if (!$posted->journalEntry)
            continue;
        $events->push([
            'date' => $posted->journalEntry->transaction_date,
            'source' => 'Penyusutan',
            'description' => $posted->journalEntry->description,
            'reference' => $posted->journalEntry->reference_number,
            'debit' => 0,
            'credit' => $posted->amount,
            'type' => 'depreciation',
            'status' => 'posted'
        ]);
    }

    if ($record->has_depreciation && $startDate && $monthlyDepreciation > 0) {
        $currentDate = now();
        $projectionMonths = 12;

        for ($i = 0; $i < ($totalMonths ?: 120); $i++) {
            $deprDate = $startDate->copy()->addMonths($i)->endOfMonth();
            $period = $deprDate->format('Y-m');

            if (in_array($period, $postedPeriods))
                continue;
            if ($deprDate->gt($currentDate->copy()->addMonths($projectionMonths)))
                break;

            $events->push([
                'date' => $deprDate,
                'source' => 'Penyusutan',
                'description' => 'Penyusutan Bulanan (Draft)',
                'reference' => '-',
                'debit' => 0,
                'credit' => $monthlyDepreciation,
                'type' => 'depreciation',
                'status' => 'scheduled'
            ]);
        }
    }

    $events = $events->sortBy(fn($e) => $e['date']->timestamp);

    $runningBalance = 0;
    $allTransactions = $events->map(function ($event) use (&$runningBalance) {
        $runningBalance += ($event['debit'] - $event['credit']);
        $event['balance'] = $runningBalance;
        return $event;
    });

    // Pagination
    $perPage = (int) request()->get('perPage', 10);
    $currentPage = (int) request()->get('page', 1);
    $total = $allTransactions->count();
    $offset = ($currentPage - 1) * $perPage;
    $transactions = $allTransactions->slice($offset, $perPage);
    $lastPage = (int) ceil($total / $perPage);
@endphp

<div
    style="border-radius: 12px; background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border: 1px solid #e5e7eb; overflow: hidden;">
    {{-- Scrollable table area --}}
    <div style="overflow-x: auto;">
        <table style="width: 100%; min-width: 960px; border-collapse: collapse; table-layout: fixed;">
            <colgroup>
                <col style="width: 110px">
                <col style="width: 110px">
                <col>
                <col style="width: 130px">
                <col style="width: 135px">
                <col style="width: 135px">
                <col style="width: 145px">
            </colgroup>
            <thead>
                <tr style="background: #f9fafb; border-bottom: 2px solid #e5e7eb;">
                    <th
                        style="padding: 10px 12px 10px 24px; text-align: left; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280;">
                        Tanggal
                    </th>
                    <th
                        style="padding: 10px 12px; text-align: left; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280;">
                        Sumber
                    </th>
                    <th
                        style="padding: 10px 12px; text-align: left; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280;">
                        Deskripsi
                    </th>
                    <th
                        style="padding: 10px 12px; text-align: left; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280;">
                        Referensi
                    </th>
                    <th
                        style="padding: 10px 12px; text-align: right; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280;">
                        Debit
                    </th>
                    <th
                        style="padding: 10px 12px; text-align: right; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280;">
                        Kredit
                    </th>
                    <th
                        style="padding: 10px 12px 10px 12px; text-align: right; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; padding-right: 24px;">
                        Saldo
                    </th>
                </tr>
            </thead>
            <tbody>
                @if($currentPage == 1)
                    <tr style="background: #f9fafb;">
                        <td colspan="6"
                            style="padding: 12px 12px 12px 24px; font-size: 13px; font-weight: 600; color: #4b5563; text-transform: uppercase; letter-spacing: 0.03em;">
                            Saldo Awal
                        </td>
                        <td
                            style="padding: 12px 24px 12px 12px; font-size: 13px; font-weight: 600; text-align: right; color: #4b5563; white-space: nowrap;">
                            Rp 0
                        </td>
                    </tr>
                @endif

                @forelse ($transactions as $transaction)
                    @php
                        $rowStyle = 'border-bottom: 1px solid #f3f4f6; transition: background 0.15s;';
                        if ($transaction['status'] === 'scheduled') {
                            // $rowStyle .= ' opacity: 0.55;'; // User wants them "activated"
                        }

                        $badgeStyle = match ($transaction['type']) {
                            'purchase' => 'background: #fef3c7; color: #92400e; border: 1px solid rgba(217,119,6,0.2);',
                            'upgrade' => 'background: #d1fae5; color: #065f46; border: 1px solid rgba(16,185,129,0.2);',
                            'depreciation' => $transaction['status'] === 'posted'
                            ? 'background: #e0f2fe; color: #075985; border: 1px solid rgba(14,165,233,0.2);'
                            : 'background: #f3f4f6; color: #4b5563; border: 1px solid rgba(107,114,128,0.15);',
                            default => 'background: #f3f4f6; color: #4b5563; border: 1px solid rgba(107,114,128,0.15);',
                        };
                    @endphp
                    <tr style="{{ $rowStyle }}" onmouseover="this.style.background='#f9fafb'"
                        onmouseout="this.style.background='{{ $transaction['status'] === 'scheduled' ? '' : '' }}'">
                        <td style="padding: 12px 12px 12px 24px; font-size: 13px; color: #4b5563; white-space: nowrap;">
                            {{ $transaction['date']->format('d/m/Y') }}
                        </td>
                        <td style="padding: 12px; white-space: nowrap;">
                            <span
                                style="display: inline-flex; align-items: center; border-radius: 6px; padding: 3px 8px; font-size: 11px; font-weight: 600; {{ $badgeStyle }}">
                                {{ $transaction['source'] }}
                            </span>
                        </td>
                        <td style="padding: 12px; font-size: 13px; color: #4b5563;">
                            <span style="{{ $transaction['status'] === 'scheduled' ? 'font-style: italic;' : '' }}">
                                {{ Str::limit($transaction['description'], 45) }}
                            </span>
                            @if($transaction['status'] === 'scheduled')
                                <span
                                    style="font-size: 11px; color: #9ca3af; font-style: italic; margin-left: 4px;">(Proyeksi)</span>
                            @endif
                        </td>
                        <td style="padding: 12px; font-size: 13px; white-space: nowrap;">
                            @if($transaction['reference'] !== '-')
                                <span style="font-weight: 500; color: #2563eb;">{{ $transaction['reference'] }}</span>
                            @else
                                <span style="color: #d1d5db;">—</span>
                            @endif
                        </td>
                        <td
                            style="padding: 12px; font-size: 13px; text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; color: {{ $transaction['debit'] > 0 ? '#111827' : '#d1d5db' }};">
                            {{ $transaction['debit'] > 0 ? 'Rp ' . number_format($transaction['debit'], 0, ',', '.') : 'Rp 0' }}
                        </td>
                        <td
                            style="padding: 12px; font-size: 13px; text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; color: {{ $transaction['credit'] > 0 ? '#111827' : '#d1d5db' }};">
                            {{ $transaction['credit'] > 0 ? 'Rp ' . number_format($transaction['credit'], 0, ',', '.') : 'Rp 0' }}
                        </td>
                        <td
                            style="padding: 12px 24px 12px 12px; font-size: 13px; font-weight: 600; text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; color: #111827;">
                            Rp {{ number_format($transaction['balance'], 0, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="padding: 48px 24px; text-align: center;">
                            <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                                <svg style="width: 32px; height: 32px; color: #d1d5db;" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                </svg>
                                <p style="font-size: 13px; color: #9ca3af;">Belum ada transaksi</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>

            @if($allTransactions->isNotEmpty())
                <tfoot>
                    <tr style="background: #eff6ff; border-top: 2px solid #bfdbfe;">
                        <td colspan="6"
                            style="padding: 12px 12px 12px 24px; font-size: 13px; font-weight: 700; color: #374151;">
                            Saldo Akhir Buku
                        </td>
                        <td
                            style="padding: 12px 24px 12px 12px; font-size: 13px; font-weight: 700; text-align: right; color: #2563eb; white-space: nowrap; font-variant-numeric: tabular-nums;">
                            Rp {{ number_format($runningBalance, 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

    {{-- Pagination (outside scroll container) --}}
    @if($total > 0)
        <div
            style="border-top: 1px solid #e5e7eb; padding: 12px 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
            {{-- Left: info --}}
            <p style="font-size: 13px; color: #6b7280; white-space: nowrap; margin: 0;">
                Menampilkan {{ ($currentPage - 1) * $perPage + 1 }} – {{ min($currentPage * $perPage, $total) }} dari
                {{ $total }} hasil
            </p>

            {{-- Center: per page --}}
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 13px; color: #6b7280; white-space: nowrap;">per halaman</span>
                <select
                    style="height: 32px; border-radius: 8px; border: 1px solid #d1d5db; background: transparent; padding: 4px 32px 4px 12px; font-size: 13px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); cursor: pointer;"
                    onchange="window.location.href = '?perPage=' + this.value">
                    <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                    <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                </select>
            </div>

            {{-- Right: page numbers --}}
            @if($lastPage > 1)
                <nav style="display: flex; align-items: center; gap: 4px;">
                    {{-- Previous --}}
                    @if($currentPage > 1)
                        <a href="?page={{ $currentPage - 1 }}&perPage={{ $perPage }}"
                            style="min-width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid #d1d5db; font-size: 13px; color: #6b7280; text-decoration: none; transition: background 0.15s;"
                            onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background=''">
                            <svg style="width: 16px; height: 16px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                                    clip-rule="evenodd" />
                            </svg>
                        </a>
                    @endif

                    @php
                        $startPage = max(1, $currentPage - 2);
                        $endPage = min($lastPage, $currentPage + 2);
                    @endphp

                    @if($startPage > 1)
                        <a href="?page=1&perPage={{ $perPage }}"
                            style="min-width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid #d1d5db; padding: 0 8px; font-size: 13px; font-weight: 500; color: #374151; text-decoration: none; transition: background 0.15s;"
                            onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background=''">
                            1
                        </a>
                        @if($startPage > 2)
                            <span
                                style="min-width: 24px; height: 32px; display: inline-flex; align-items: center; justify-content: center; font-size: 13px; color: #9ca3af;">…</span>
                        @endif
                    @endif

                    @for($i = $startPage; $i <= $endPage; $i++)
                        @if($i == $currentPage)
                            <span
                                style="min-width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid rgba(37,99,235,0.4); background: #eff6ff; padding: 0 8px; font-size: 13px; font-weight: 600; color: #2563eb;">
                                {{ $i }}
                            </span>
                        @else
                            <a href="?page={{ $i }}&perPage={{ $perPage }}"
                                style="min-width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid #d1d5db; padding: 0 8px; font-size: 13px; font-weight: 500; color: #374151; text-decoration: none; transition: background 0.15s;"
                                onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background=''">
                                {{ $i }}
                            </a>
                        @endif
                    @endfor

                    @if($endPage < $lastPage)
                        @if($endPage < $lastPage - 1)
                            <span
                                style="min-width: 24px; height: 32px; display: inline-flex; align-items: center; justify-content: center; font-size: 13px; color: #9ca3af;">…</span>
                        @endif
                        <a href="?page={{ $lastPage }}&perPage={{ $perPage }}"
                            style="min-width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid #d1d5db; padding: 0 8px; font-size: 13px; font-weight: 500; color: #374151; text-decoration: none; transition: background 0.15s;"
                            onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background=''">
                            {{ $lastPage }}
                        </a>
                    @endif

                    {{-- Next --}}
                    @if($currentPage < $lastPage)
                        <a href="?page={{ $currentPage + 1 }}&perPage={{ $perPage }}"
                            style="min-width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid #d1d5db; font-size: 13px; color: #6b7280; text-decoration: none; transition: background 0.15s;"
                            onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background=''">
                            <svg style="width: 16px; height: 16px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                    clip-rule="evenodd" />
                            </svg>
                        </a>
                    @endif
                </nav>
            @endif
        </div>
    @endif
</div>