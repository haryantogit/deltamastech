<div class="account-transactions-container" style="width: 100%; font-family: inherit;">
    @php
        $fmt = function ($num) {
            if ($num == 0)
                return '0';
            $prefix = $num < 0 ? '(' : '';
            $suffix = $num < 0 ? ')' : '';
            return $prefix . number_format(abs($num), 0, ',', '.') . $suffix;
        };
    @endphp

    <div style="padding: 1rem 0;">
        <!-- Top Header: Title & Print -->
        <div
            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; padding: 0 0.75rem;">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <h2 style="margin: 0; font-size: 1.25rem; font-weight: 700;" class="text-gray-900 dark:text-white">
                    {{ $account->code }} - {{ $account->name }}
                </h2>
            </div>
            <button type="button" class="transition-all hover:opacity-80"
                style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.875rem; font-weight: 600; cursor: pointer; background-color: #ffffff; border: 1px solid #d1d5db; color: #374151; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                <svg style="width: 1.1rem; height: 1.1rem;" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18.75 12h.008v.008h-.008V12zm-3 0h.008v.008h-.008V12z" />
                </svg>
                <span>Print</span>
            </button>
        </div>

        <!-- Filter Bar: Buttons, Search, Date -->
        <div style="display: flex; justify-content: space-between; align-items: center; background-color: rgba(128,128,128,0.05); padding: 0.75rem 1rem; border-radius: 8px; margin: 0 0.75rem 1.5rem 0.75rem; gap: 1rem; flex-wrap: wrap;"
            class="dark:border dark:border-white/10">
            <!-- Left Side: Filter & Chart Buttons -->
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <button class="transition-all hover:opacity-90"
                    style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.875rem; font-weight: 700; cursor: pointer; background-color: #111827; color: #ffffff; border: none; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                    <svg style="width: 1.1rem; height: 1.1rem;" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                    </svg>
                    <span>Filter</span>
                </button>
                <button class="transition-all hover:bg-gray-50 dark:hover:bg-white/10"
                    style="padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.875rem; font-weight: 600; cursor: pointer; background-color: #ffffff; border: 1px solid #d1d5db; color: #374151; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                    Tampilkan Grafik
                </button>
            </div>

            <!-- Right Side: Search & Date Range -->
            <div style="display: flex; align-items: center; gap: 1rem;">
                <!-- Search (Mockup as this is a static modal view) -->
                <div style="position: relative; display: flex; align-items: center;">
                    <div style="position: absolute; left: 0.6rem; color: #9ca3af;">
                        <svg style="width: 1rem; height: 1rem;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <input type="text" placeholder="Cari"
                        class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 text-gray-900 dark:text-white"
                        style="padding: 0.4rem 0.6rem 0.4rem 2rem; border-radius: 4px; font-size: 0.875rem; outline: none; width: 10rem; border-style: solid;"
                        readonly>
                </div>
                <!-- Date Range -->
                <div class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700"
                    style="display: flex; align-items: center; gap: 0.4rem; padding: 0.4rem 0.6rem; border-radius: 4px; border-style: solid;">
                    <span class="text-gray-700 dark:text-gray-200" style="font-size: 0.75rem;">{{ $startFormatted }} —
                        {{ $endFormatted }}</span>
                    <svg style="width: 0.875rem; height: 0.875rem; color: #9ca3af;" xmlns="http://www.w3.org/2000/svg"
                        fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div style="width: 100%; overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; min-width: 800px;">
                <thead class="bg-gray-200 dark:bg-gray-700">
                    <tr>
                        @php $thBase = 'padding: 0.6rem 0.75rem; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #4b5563; text-align: left;'; @endphp
                        <th style="{{ $thBase }}" class="dark:text-gray-200">Tanggal</th>
                        <th style="{{ $thBase }}" class="dark:text-gray-200">Sumber</th>
                        <th style="{{ $thBase }}" class="dark:text-gray-200">Deskripsi</th>
                        <th style="{{ $thBase }}" class="dark:text-gray-200">Referensi</th>
                        <th style="{{ $thBase }}" class="dark:text-gray-200">Nomor</th>
                        <th style="{{ $thBase }} text-align: right;" class="dark:text-gray-200">Debit</th>
                        <th style="{{ $thBase }} text-align: right;" class="dark:text-gray-200">Kredit</th>
                        <th style="{{ $thBase }} text-align: right;" class="dark:text-gray-200">Saldo Berjalan</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900">
                    <!-- Saldo Awal Row -->
                    <tr style="border-bottom: 1px solid rgba(128,128,128,0.1);">
                        <td style="padding: 0.75rem; font-size: 0.875rem; font-weight: 700;" colspan="5"
                            class="text-gray-900 dark:text-gray-100 italic">Saldo Awal</td>
                        <td style="padding: 0.75rem; font-size: 0.875rem; text-align: right;"
                            class="text-gray-900 dark:text-gray-200 font-bold">
                            {{ $saldoAwal > 0 ? number_format(abs($saldoAwal), 0, ',', '.') : '0' }}
                        </td>
                        <td style="padding: 0.75rem; font-size: 0.875rem; text-align: right;"
                            class="text-gray-900 dark:text-gray-200 font-bold">
                            {{ $saldoAwal < 0 ? number_format(abs($saldoAwal), 0, ',', '.') : '0' }}
                        </td>
                        <td style="padding: 0.75rem; font-size: 0.875rem; text-align: right; font-weight: 700;">
                            @if($saldoAwal < 0)
                                <span style="color: #ef4444;">({{ number_format(abs($saldoAwal), 0, ',', '.') }})</span>
                            @else
                                <span
                                    class="text-gray-900 dark:text-white">{{ number_format($saldoAwal, 0, ',', '.') }}</span>
                            @endif
                        </td>
                    </tr>

                    <!-- Transactions -->
                    @php $runningBalance = $saldoAwal; @endphp
                    @forelse ($transactions as $trx)
                        @php $runningBalance += ($trx['debit'] - $trx['kredit']); @endphp
                        <tr style="border-bottom: 1px solid rgba(128,128,128,0.1);"
                            class="hover:bg-gray-50 dark:hover:bg-white/5">
                            <td style="padding: 0.75rem; font-size: 0.875rem;" class="text-gray-900 dark:text-gray-200">
                                {{ $trx['tanggal'] }}
                            </td>
                            <td style="padding: 0.75rem; font-size: 0.875rem; color: #3b82f6;">{{ $trx['sumber'] }}</td>
                            <td style="padding: 0.75rem; font-size: 0.875rem; color: #3b82f6; max-width: 250px;">
                                {{ $trx['deskripsi'] }}
                            </td>
                            <td style="padding: 0.75rem; font-size: 0.875rem; text-align: center; color: #9ca3af;">
                                {{ $trx['referensi'] ?: '-' }}
                            </td>
                            <td style="padding: 0.75rem; font-size: 0.875rem; color: #3b82f6; font-family: monospace;">
                                {{ $trx['nomor'] }}
                            </td>
                            <td style="padding: 0.75rem; font-size: 0.875rem; text-align: right;"
                                class="text-gray-900 dark:text-gray-200 font-bold">
                                {{ $trx['debit'] > 0 ? number_format($trx['debit'], 0, ',', '.') : '0' }}
                            </td>
                            <td style="padding: 0.75rem; font-size: 0.875rem; text-align: right;"
                                class="text-gray-900 dark:text-gray-200 font-bold">
                                {{ $trx['kredit'] > 0 ? number_format($trx['kredit'], 0, ',', '.') : '0' }}
                            </td>
                            <td style="padding: 0.75rem; font-size: 0.875rem; text-align: right; font-weight: 700;">
                                @if($runningBalance < 0)
                                    <span
                                        style="color: #ef4444;">({{ number_format(abs($runningBalance), 0, ',', '.') }})</span>
                                @else
                                    <span
                                        class="text-gray-900 dark:text-white">{{ number_format($runningBalance, 0, ',', '.') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="padding: 3rem; text-align: center; color: #9ca3af;">Tidak ada data
                                transaksi.</td>
                        </tr>
                    @endforelse

                    <!-- Saldo Akhir Row -->
                    @php
                        $totalDebit = collect($transactions)->sum('debit');
                        $totalCredit = collect($transactions)->sum('kredit');
                        $finalDebitTotal = $totalDebit + ($saldoAwal > 0 ? $saldoAwal : 0);
                        $finalCreditTotal = $totalCredit + ($saldoAwal < 0 ? abs($saldoAwal) : 0);
                    @endphp
                    <tr class="bg-gray-100 dark:bg-gray-800" style="font-weight: 700;">
                        <td colspan="5" style="padding: 1rem; font-size: 0.875rem; text-transform: uppercase;"
                            class="text-gray-900 dark:text-white">Saldo Akhir</td>
                        <td style="padding: 1rem; font-size: 0.875rem; text-align: right;"
                            class="text-gray-900 dark:text-white">{{ number_format($finalDebitTotal, 0, ',', '.') }}
                        </td>
                        <td style="padding: 1rem; font-size: 0.875rem; text-align: right;"
                            class="text-gray-900 dark:text-white">{{ number_format($finalCreditTotal, 0, ',', '.') }}
                        </td>
                        <td style="padding: 1rem; font-size: 1rem; text-align: right;" class="font-bold">
                            @if($runningBalance < 0)
                                <span
                                    style="color: #ef4444;">({{ number_format(abs($runningBalance), 0, ',', '.') }})</span>
                            @else
                                <span
                                    class="text-gray-900 dark:text-white">{{ number_format($runningBalance, 0, ',', '.') }}</span>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Footer / Closing -->
        <div style="display: flex; justify-content: flex-end; margin-top: 2rem; padding: 0 0.75rem;">
            <button onclick="document.querySelector('.fi-modal-close-overlay')?.click()"
                class="transition-all hover:bg-gray-50 dark:hover:bg-white/10"
                style="padding: 0.5rem 2rem; border-radius: 6px; font-size: 0.875rem; font-weight: 700; cursor: pointer; background-color: #ffffff; border: 1px solid #d1d5db; color: #374151; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                Tutup
            </button>
        </div>
    </div>
</div>