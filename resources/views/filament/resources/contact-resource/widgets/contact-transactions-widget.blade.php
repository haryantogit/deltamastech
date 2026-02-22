<x-filament-widgets::widget>
    <x-filament::section>
        {{-- Simple Tabs (like Product page) --}}
        <div class="mb-4">
            <x-filament::tabs>
                @php
                    $stats = $this->tabStats;
                    $tabs = [
                        'transaksi' => ['label' => 'Semua', 'count' => $stats['transaksi']['count']],
                        'piutang' => ['label' => 'Belum Dibayar', 'count' => $stats['piutang']['count']],
                        'hutang' => ['label' => 'Dibayar Sebagian', 'count' => $stats['hutang']['count']],
                    ];
                @endphp

                @foreach($tabs as $key => $data)
                    <x-filament::tabs.item :active="$activeTab === $key" wire:click="setTab('{{ $key }}')">
                        {{ $data['label'] }}
                        @if($data['count'] > 0)
                            <x-slot name="badge">
                                {{ $data['count'] }}
                            </x-slot>
                        @endif
                    </x-filament::tabs.item>
                @endforeach
            </x-filament::tabs>
        </div>

        {{-- Search Bar --}}
        <div class="mb-4 flex justify-end">
            <div class="w-64">
                <x-filament::input.wrapper prefix-icon="heroicon-m-magnifying-glass">
                    <x-filament::input wire:model.live.debounce.500ms="search" placeholder="Cari..." type="search" />
                </x-filament::input.wrapper>
            </div>
        </div>

        {{-- Table --}}
        <div
            class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="overflow-x-auto">
                <table class="w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                    <thead class="bg-gray-50 dark:bg-white/5">
                        <tr>
                            <th class="px-3 py-3.5 text-start sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                <span
                                    class="text-xs font-semibold uppercase tracking-wider text-gray-950 dark:text-white">
                                    Tanggal
                                </span>
                            </th>
                            <th class="px-3 py-3.5 text-start sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                <span
                                    class="text-xs font-semibold uppercase tracking-wider text-gray-950 dark:text-white">
                                    Transaksi
                                </span>
                            </th>
                            <th class="px-3 py-3.5 text-start sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                <span
                                    class="text-xs font-semibold uppercase tracking-wider text-gray-950 dark:text-white">
                                    Keterangan
                                </span>
                            </th>
                            <th class="px-3 py-3.5 text-end sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                <span
                                    class="text-xs font-semibold uppercase tracking-wider text-gray-950 dark:text-white">
                                    Total
                                </span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5">
                        @php $allTx = $this->transactions; @endphp

                        @forelse($allTx as $tx)
                            <tr class="transition duration-75 hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="px-3 py-4 text-sm sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                    {{ \Carbon\Carbon::parse($tx->date)->format('d/m/Y') }}
                                </td>
                                <td class="px-3 py-4 text-sm sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                    <a href="#" class="font-medium text-primary-600 hover:underline dark:text-primary-400">
                                        {{ $tx->type_label }} {{ $tx->number }}
                                    </a>
                                </td>
                                <td
                                    class="px-3 py-4 text-sm text-gray-500 dark:text-gray-400 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                    @if($tx->type_label === 'Pembelian')
                                        {{ $tx->number }}
                                    @elseif($tx->type_label === 'Biaya')
                                        {{ $tx->number }}
                                    @else
                                        {{ $tx->number }}
                                    @endif
                                </td>
                                <td
                                    class="px-3 py-4 text-end text-sm font-medium sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                    Rp {{ number_format($tx->amount, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-12 text-center sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                    <div class="flex flex-col items-center justify-center text-gray-400">
                                        <x-filament::icon icon="heroicon-o-inbox" class="mb-3 h-12 w-12 opacity-25" />
                                        <p class="text-sm font-medium">Tidak ada transaksi ditemukan.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($allTx->hasPages())
                <div class="border-t border-gray-200 px-3 py-3 dark:border-white/10">
                    {{ $allTx->links() }}
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>