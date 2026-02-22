<div>
    <div style="font-family: inherit;">
        <!-- Header -->
        <div class="flex justify-between items-start mb-6 border-b border-gray-200 pb-4"
            style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; border-bottom: 1px solid #e5e7eb; padding-bottom: 1rem;">
            <div>
                <h2 class="text-2xl font-bold text-gray-900"
                    style="font-size: 1.5rem; font-weight: 700; color: #111827;">Transaksi {{ $this->account->name }}
                </h2>
                <p class="text-sm text-gray-500 font-mono mt-1"
                    style="font-size: 0.875rem; color: #6b7280; font-family: monospace; margin-top: 0.25rem;">
                    {{ $this->account->code }}
                </p>
            </div>
            <div>
                <button type="button"
                    class="flex items-center gap-2 px-3 py-1.5 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50"
                    style="display: flex; align-items: center; gap: 0.5rem; padding: 0.375rem 0.75rem; background-color: #fff; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.875rem; color: #374151;">
                    <svg style="width: 1rem; height: 1rem; color: #6b7280;" xmlns="http://www.w3.org/2000/svg"
                        fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M6.72 13.89l-4.72-4.72g-3.045 3.045L4.5 9.75l4.5 4.5m1.125-1.125l9-9M6.75 6.75h.75m1.5 0h.75m-1.5 3h.75m1.5 0h.75m-1.5 3h.75m1.5 0h.75M3.75 6.75h.75m1.5 0h.75m-1.5 3h.75m1.5 0h.75m-1.5 3h.75m1.5 0h.75M4.5 21h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v12A2.25 2.25 0 004.5 21z" />
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M6.75 6.75h10.5a.75.75 0 01.75.75v12.75a.75.75 0 01-.75.75H6.75a.75.75 0 01-.75-.75V7.5a.75.75 0 01.75-.75z" />
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M17.25 6.75V4.5a2.25 2.25 0 00-2.25-2.25h-6a2.25 2.25 0 00-2.25 2.25v2.25" />
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.5 21.75h-9a2.25 2.25 0 01-2.25-2.25v-5.25h13.5v5.25a2.25 2.25 0 01-2.25 2.25z" />
                    </svg>
                    Print
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4"
            style="display: flex; flex-direction: row; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; gap: 1rem;">
            <div class="flex items-center gap-4 w-full sm:w-auto"
                style="display: flex; align-items: center; gap: 1rem;">
                <div class="flex items-center gap-2 bg-gray-50 border border-gray-300 rounded-lg px-3 py-1.5"
                    style="display: flex; align-items: center; gap: 0.5rem; background-color: #f9fafb; border: 1px solid #d1d5db; border-radius: 0.5rem; padding: 0.375rem 0.75rem;">
                    <svg style="width: 1rem; height: 1rem; color: #6b7280;" xmlns="http://www.w3.org/2000/svg"
                        fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                    </svg>
                    <input type="date" wire:model.live="startDate"
                        class="bg-transparent border-none text-sm text-gray-600 focus:ring-0 p-0"
                        style="background: transparent; border: none; font-size: 0.875rem; color: #4b5563; padding: 0; outline: none;">
                    <span class="text-gray-400" style="color: #9ca3af;">—</span>
                    <input type="date" wire:model.live="endDate"
                        class="bg-transparent border-none text-sm text-gray-600 focus:ring-0 p-0"
                        style="background: transparent; border: none; font-size: 0.875rem; color: #4b5563; padding: 0; outline: none;">
                </div>
            </div>

            <div class="relative w-full sm:w-auto" style="position: relative;">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                    style="position: absolute; top: 0; bottom: 0; left: 0; padding-left: 0.75rem; display: flex; align-items: center; pointer-events: none;">
                    <svg style="width: 1rem; height: 1rem; color: #9ca3af;" xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd"
                            d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari transaksi..."
                    class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                    style="display: block; width: 100%; padding-left: 2.5rem; padding-right: 0.75rem; padding-top: 0.5rem; padding-bottom: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.5rem; background-color: #fff; font-size: 0.875rem; line-height: 1.25rem;">
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto border border-gray-200 rounded-lg shadow-sm"
            style="overflow-x: auto; border: 1px solid #e5e7eb; border-radius: 0.5rem; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);">
            <table class="min-w-full divide-y divide-gray-200"
                style="min-width: 100%; border-collapse: collapse; width: 100%;">
                <thead class="bg-gray-50" style="background-color: #f9fafb;">
                    <tr>
                        <th scope="col"
                            style="padding: 0.75rem 1.5rem; text-align: left; font-size: 0.75rem; font-weight: 500; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid #e5e7eb;">
                            Tanggal</th>
                        <th scope="col"
                            style="padding: 0.75rem 1.5rem; text-align: left; font-size: 0.75rem; font-weight: 500; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid #e5e7eb;">
                            Sumber</th>
                        <th scope="col"
                            style="padding: 0.75rem 1.5rem; text-align: left; font-size: 0.75rem; font-weight: 500; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid #e5e7eb;">
                            Deskripsi</th>
                        <th scope="col"
                            style="padding: 0.75rem 1.5rem; text-align: left; font-size: 0.75rem; font-weight: 500; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid #e5e7eb;">
                            Referensi</th>
                        <th scope="col"
                            style="padding: 0.75rem 1.5rem; text-align: left; font-size: 0.75rem; font-weight: 500; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid #e5e7eb;">
                            Nomor</th>
                        <th scope="col"
                            style="padding: 0.75rem 1.5rem; text-align: right; font-size: 0.75rem; font-weight: 500; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid #e5e7eb;">
                            Debit</th>
                        <th scope="col"
                            style="padding: 0.75rem 1.5rem; text-align: right; font-size: 0.75rem; font-weight: 500; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid #e5e7eb;">
                            Kredit</th>
                        <th scope="col"
                            style="padding: 0.75rem 1.5rem; text-align: right; font-size: 0.75rem; font-weight: 500; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid #e5e7eb;">
                            Saldo Berjalan</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200"
                    style="background-color: #fff; border-top: 1px solid #e5e7eb;">
                    @forelse ($transactions as $transaction)
                        <tr class="hover:bg-gray-50 transition duration-150 ease-in-out"
                            style="border-bottom: 1px solid #f3f4f6;">
                            <td
                                style="padding: 1rem 1.5rem; white-space: nowrap; font-size: 0.875rem; color: #111827; border-bottom: 1px solid #eff0f2;">
                                {{ $transaction->transaction_date->format('d/m/Y') }}
                            </td>
                            <td
                                style="padding: 1rem 1.5rem; white-space: nowrap; font-size: 0.875rem; color: #6b7280; border-bottom: 1px solid #eff0f2;">
                                {{ $transaction->sumber }}
                            </td>
                            <td
                                style="padding: 1rem 1.5rem; font-size: 0.875rem; font-weight: 500; color: #2563eb; cursor: pointer; border-bottom: 1px solid #eff0f2;">
                                @if(isset($transaction->transaction_url) && $transaction->transaction_url !== '#')
                                    <a href="{{ $transaction->transaction_url }}" target="_blank" class="hover:underline">
                                        {{ $transaction->description }}
                                    </a>
                                @else
                                    {{ $transaction->description }}
                                @endif
                            </td>
                            <td
                                style="padding: 1rem 1.5rem; white-space: nowrap; font-size: 0.875rem; color: #6b7280; border-bottom: 1px solid #eff0f2;">
                                {{ $transaction->referensi }}
                            </td>
                            <td
                                style="padding: 1rem 1.5rem; white-space: nowrap; font-size: 0.875rem; font-family: monospace; color: #6b7280; border-bottom: 1px solid #eff0f2;">
                                @if(isset($transaction->transaction_url) && $transaction->transaction_url !== '#')
                                    <a href="{{ $transaction->transaction_url }}" target="_blank"
                                        class="text-blue-600 hover:underline">
                                        {{ $transaction->nomor }}
                                    </a>
                                @else
                                    {{ $transaction->nomor }}
                                @endif
                            </td>
                            <td
                                style="padding: 1rem 1.5rem; white-space: nowrap; font-size: 0.875rem; color: #111827; text-align: right; border-bottom: 1px solid #eff0f2;">
                                {{ $transaction->debit > 0 ? number_format($transaction->debit, 0, ',', '.') : '0' }}
                            </td>
                            <td
                                style="padding: 1rem 1.5rem; white-space: nowrap; font-size: 0.875rem; color: #111827; text-align: right; border-bottom: 1px solid #eff0f2;">
                                {{ $transaction->credit > 0 ? number_format($transaction->credit, 0, ',', '.') : '0' }}
                            </td>
                            <td
                                style="padding: 1rem 1.5rem; white-space: nowrap; font-size: 0.875rem; font-weight: 700; color: {{ $transaction->running_balance < 0 ? '#dc2626' : '#111827' }}; text-align: right; border-bottom: 1px solid #eff0f2;">
                                {{ number_format($transaction->running_balance, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8"
                                style="padding: 2.5rem; text-align: center; font-size: 0.875rem; color: #6b7280; border-bottom: 1px solid #eff0f2;">
                                Tidak ada transaksi yang ditemukan untuk periode ini.
                            </td>
                        </tr>
                    @endforelse

                    <!-- Opening Balance Row at the bottom for DESC order -->
                    @if($transactions->isEmpty() || $transactions->onLastPage())
                        <tr class="bg-gray-50 italic" style="background-color: #f9fafb; font-style: italic;">
                            <td colspan="5"
                                style="padding: 1rem 1.5rem; white-space: nowrap; font-size: 0.875rem; font-weight: 500; color: #6b7280; border-bottom: 1px solid #eff0f2;">
                                Saldo Awal
                                (Sebelum {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }})</td>
                            <td
                                style="padding: 1rem 1.5rem; white-space: nowrap; font-size: 0.875rem; color: #111827; text-align: right; border-bottom: 1px solid #eff0f2;">
                                -</td>
                            <td
                                style="padding: 1rem 1.5rem; white-space: nowrap; font-size: 0.875rem; color: #111827; text-align: right; border-bottom: 1px solid #eff0f2;">
                                -</td>
                            <td
                                style="padding: 1rem 1.5rem; white-space: nowrap; font-size: 0.875rem; font-weight: 700; color: {{ $openingBalance < 0 ? '#dc2626' : '#111827' }}; text-align: right; border-bottom: 1px solid #eff0f2;">
                                {{ number_format($openingBalance, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endif
                </tbody>
                <!-- Summary/Footer -->
                <!-- Only show if there are transactions or at least opening balance -->
                <tfoot class="bg-gray-100 font-semibold border-t-2 border-gray-300"
                    style="background-color: #f3f4f6; font-weight: 600; border-top: 2px solid #d1d5db;">
                    <tr>
                        <td colspan="5"
                            style="padding: 1rem 1.5rem; text-align: right; font-size: 0.875rem; color: #374151;">Total
                            Periode Ini</td>
                        <td style="padding: 1rem 1.5rem; text-align: right; font-size: 0.875rem; color: #111827;">
                            {{ number_format($totalDebit, 0, ',', '.') }}
                        </td>
                        <td style="padding: 1rem 1.5rem; text-align: right; font-size: 0.875rem; color: #111827;">
                            {{ number_format($totalCredit, 0, ',', '.') }}
                        </td>
                        <td style="padding: 1rem 1.5rem; text-align: right; font-size: 0.875rem; color: #111827;"></td>
                    </tr>
                    <tr class="bg-gray-200 border-t border-gray-300"
                        style="background-color: #e5e7eb; border-top: 1px solid #d1d5db;">
                        <td colspan="4"
                            style="padding: 1rem 1.5rem; text-align: right; font-size: 0.875rem; font-weight: 700; text-transform: uppercase; color: #111827;">
                            Saldo
                            Akhir</td>
                        <td colspan="2"></td>
                        <td
                            style="padding: 1rem 1.5rem; text-align: right; font-size: 1.125rem; font-weight: 700; color: {{ $closingBalance < 0 ? '#dc2626' : '#111827' }};">
                            {{ number_format($closingBalance, 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-4" style="margin-top: 1rem; display: flex; justify-content: flex-end;">
            {{ $transactions->links() }}
        </div>

        <!-- Footer Buttons -->
        <div class="flex justify-end mt-6" style="display: flex; justify-content: flex-end; margin-top: 1.5rem;">
            <button onclick="document.querySelector('.fi-modal-close-overlay')?.click()"
                class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50 transition"
                style="padding: 0.5rem 1rem; background-color: #fff; border: 1px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem; color: #374151; cursor: pointer;">
                Tutup
            </button>
        </div>
    </div>
</div>