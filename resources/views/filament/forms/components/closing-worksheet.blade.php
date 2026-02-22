<div>
    <div class="space-y-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Pendapatan</p>
                <p class="text-2xl font-semibold text-green-600 dark:text-green-400">
                    Rp {{ number_format($livewire->totalRevenue, 2, ',', '.') }}
                </p>
            </div>
            <div class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Beban</p>
                <p class="text-2xl font-semibold text-red-600 dark:text-red-400">
                    Rp {{ number_format($livewire->totalExpense, 2, ',', '.') }}
                </p>
            </div>
            <div class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Laba/Rugi Bersih</p>
                <p
                    class="text-2xl font-semibold {{ $livewire->netIncome >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    Rp {{ number_format($livewire->netIncome, 2, ',', '.') }}
                </p>
            </div>
        </div>

        <div class="p-4 mb-4 text-sm text-blue-800 rounded-lg bg-blue-50 dark:bg-gray-800 dark:text-blue-400"
            role="alert">
            <span class="font-medium">Info:</span> Saldo Laba/Rugi Bersih ini akan dipindahkan ke akun <strong>Laba
                Ditahan (Retained Earnings)</strong>.
        </div>
    </div>
</div>