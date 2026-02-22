<div class="fi-in-text">
    <div class="space-y-4">
        @php
            $purchaseOrder = $getRecord()->purchaseOrder;
        @endphp

        @if($purchaseOrder)
            {{-- Purchase Order --}}
            <div class="rounded-xl border border-gray-200 dark:border-white/5 p-4 bg-gray-50/50 dark:bg-white/5">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <div class="text-xs font-bold text-gray-500 uppercase tracking-wider">PESANAN PEMBELIAN</div>
                        <div class="text-sm font-medium text-primary-600">{{ $purchaseOrder->number }}</div>
                    </div>
                    <div class="text-xs text-gray-500">{{ $purchaseOrder->date->format('d/m/Y') }}</div>
                </div>
                <div class="text-lg font-bold text-gray-950 dark:text-white">
                    Rp {{ number_format($purchaseOrder->total_amount, 0, ',', '.') }}
                </div>
                @if($purchaseOrder->tags->isNotEmpty())
                    <div class="flex flex-wrap gap-1 mt-2">
                        @foreach($purchaseOrder->tags as $tag)
                            <span
                                class="inline-flex items-center px-2 py-0.5 text-xs font-medium text-gray-600 bg-gray-200 rounded dark:text-gray-400 dark:bg-white/10">{{ $tag->name }}</span>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Invoices --}}
            @foreach($purchaseOrder->invoices as $invoice)
                <div class="rounded-xl border border-gray-200 dark:border-white/5 p-4">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <div class="text-xs font-bold text-gray-500 uppercase tracking-wider">TAGIHAN PEMBELIAN</div>
                            <div class="text-sm font-medium text-primary-600">{{ $invoice->number }}</div>
                        </div>
                        <div class="text-xs text-gray-500">{{ $invoice->date->format('d/m/Y') }}</div>
                    </div>
                    <div class="text-lg font-bold text-gray-950 dark:text-white">
                        Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}
                    </div>
                    @if($invoice->tags->isNotEmpty())
                        <div class="flex flex-wrap gap-1 mt-2">
                            @foreach($invoice->tags as $tag)
                                <span
                                    class="inline-flex items-center px-2 py-0.5 text-xs font-medium text-gray-600 bg-gray-200 rounded dark:text-gray-400 dark:bg-white/10">{{ $tag->name }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach

            {{-- Down Payment --}}
            @if($purchaseOrder->down_payment > 0)
                <div class="rounded-xl border border-gray-200 dark:border-white/5 p-4 border-l-4 border-l-primary-500">
                    <div class="flex justify-between items-start mb-1">
                        <div>
                            <div class="text-xs font-bold text-gray-500 uppercase tracking-wider">UANG MUKA</div>
                            <div class="text-sm text-gray-500">
                                PP/{{ \Illuminate\Support\Str::after($purchaseOrder->number, '/') }}</div>
                        </div>
                    </div>
                    <div class="text-lg font-bold text-gray-950 dark:text-white">
                        Rp {{ number_format($purchaseOrder->down_payment, 0, ',', '.') }}
                    </div>
                </div>
            @endif
        @else
            <div class="text-sm text-gray-500 italic p-4 text-center">Tidak ada transaksi terkait</div>
        @endif
    </div>
</div>