<x-filament-panels::page>
    <style>
        .kledo-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            padding: 24px;
        }

        .kledo-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .header-left h1 {
            font-size: 20px;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
        }

        .header-left p {
            font-size: 14px;
            color: #64748b;
            margin: 4px 0 0;
        }

        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .toolbar-left {
            display: flex;
            gap: 10px;
        }

        .toolbar-right {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .btn-white {
            background: #fff;
            border: 1px solid #e2e8f0;
            color: #475569;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-white:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }

        .search-input {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 6px 12px 6px 32px;
            font-size: 13px;
            width: 200px;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'/%3E%3C/svg%3E") no-repeat 8px center;
            background-size: 16px;
        }

        .date-range {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 13px;
            color: #475569;
            display: flex;
            align-items: center;
            gap: 8px;
            background: #fff;
        }

        .kledo-table {
            width: 100%;
            border-collapse: collapse;
        }

        .kledo-table th {
            text-align: left;
            padding: 12px 16px;
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            border-bottom: 1px solid #f1f5f9;
        }

        .kledo-table td {
            padding: 12px 16px;
            font-size: 13px;
            color: #334155;
            border-bottom: 1px solid #f8fafc;
        }

        .kledo-table tr:hover td {
            background: #f8fafc;
        }

        .text-right {
            text-align: right;
        }

        .text-blue {
            color: #3b82f6;
        }

        .tag {
            background: #f1f5f9;
            color: #64748b;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            display: inline-block;
        }
    </style>

    <div class="kledo-card">
        <div class="kledo-header">
            <div class="header-left">
                <h1>Transaksi Kas</h1>
                <p>{{ $record->name }} - {{ $record->code }}</p>
            </div>
            <div>
                <x-filament::button color="gray" icon="heroicon-m-printer" outlined>Print</x-filament::button>
            </div>
        </div>

        <div class="toolbar">
            <div class="toolbar-left">
                <button class="btn-white">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Filter
                </button>
                <button class="btn-white">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                    </svg>
                    Tampilkan Grafik
                </button>
            </div>
            <div class="toolbar-right">
                <input type="text" placeholder="Cari" class="search-input">
                <div class="date-range">
                    <span>18/12/2025</span>
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                    <span>18/01/2026</span>
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="mb-6">
            @livewire(\App\Filament\Widgets\AccountBalanceChart::class, ['accountId' => $record->id])
        </div>
        <table class="kledo-table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Sumber</th>
                    <th>Deskripsi</th>
                    <th>Referensi</th>
                    <th>Nomor</th>
                    <th class="text-right">Debit</th>
                    <th class="text-right">Kredit</th>
                    <th class="text-right">Saldo Berjalan</th>
                </tr>
            </thead>
            <tbody>
                <!-- Saldo Awal Row -->
                <tr style="background: #fafafa;">
                    <td colspan="5" style="font-weight: 500;">Saldo Awal</td>
                    <td class="text-right">181.000</td>
                    <td class="text-right">0</td>
                    <td class="text-right font-bold">181.000</td>
                </tr>

                @php $runningBalance = 0; @endphp
                @forelse ($transactions as $transaction)
                    @php
                        $debit = (float) $transaction->debit;
                        $credit = (float) $transaction->credit;
                        if ($is_debit_normal) {
                            $runningBalance += ($debit - $credit);
                        } else {
                            $runningBalance += ($credit - $debit);
                        }
                    @endphp
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($transaction->journalEntry->transaction_date)->format('d/m/Y') }}</td>
                        <td>Biaya</td> {{-- Placeholder for Source column --}}
                        <td class="text-blue">{{ $transaction->journalEntry->description ?? '-' }}</td>
                        <td>{{ $transaction->journalEntry->reference_number ?? '-' }}</td>
                        <td>EXP/{{ substr($transaction->journalEntry->id, -5) }}</td>
                        <td class="text-right">{{ $debit > 0 ? number_format($debit, 0, ',', '.') : '0' }}</td>
                        <td class="text-right">{{ $credit > 0 ? number_format($credit, 0, ',', '.') : '0' }}</td>
                        <td class="text-right">{{ number_format($runningBalance, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-8 text-gray-500">Belum ada transaksi</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($transactions->hasPages())
            <div class="mt-4">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>
</x-filament-panels::page>