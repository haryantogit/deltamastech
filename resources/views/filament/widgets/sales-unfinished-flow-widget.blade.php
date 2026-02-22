<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-bold uppercase tracking-wider text-gray-500">ALUR PENJUALAN BELUM SELESAI</h3>
        </div>

        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem;">
            <!-- Step 1: Penawaran -->
            <div class="relative h-full group filter drop-shadow-sm transition-all hover:drop-shadow-md hover:z-50">
                <div class="flex flex-col h-full w-full"
                    style="border-radius: 2rem; overflow: hidden; border: 1px solid #e5e7eb; background-color: white; height: 100%;">
                    <!-- Header -->
                    <div
                        style="height: 9rem; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 1rem; background-color: #f59e0b; color: white;">
                        <div
                            class="mb-2 w-12 h-12 flex items-center justify-center bg-white/20 rounded-xl group-hover:scale-110 transition-transform">
                            <x-heroicon-o-shopping-cart class="w-8 h-8 text-white" style="width: 32px; height: 32px;" />
                        </div>
                        <span
                            style="font-size: 1rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em;">Penawaran</span>
                    </div>
                    <!-- Body -->
                    <div
                        style="flex: 1; padding: 1.5rem; text-align: center; display: flex; flex-direction: column; justify-content: center; background-color: #f9fafb;">
                        <div
                            style="font-size: 2rem; font-weight: 900; color: #1f2937; line-height: 1; margin-bottom: 0.5rem;">
                            {{ $quotationCount ?? 0 }}
                        </div>
                        <div
                            style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; font-weight: 600; line-height: 1.25;">
                            Penawaran disetujui</div>
                    </div>
                </div>
            </div>

            <!-- Step 2: Pemesanan -->
            <div class="relative h-full group filter drop-shadow-sm transition-all hover:drop-shadow-md hover:z-50">
                <div class="flex flex-col h-full w-full"
                    style="border-radius: 2rem; overflow: hidden; border: 1px solid #e5e7eb; background-color: white; height: 100%;">
                    <div
                        style="height: 9rem; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 1rem; background-color: #ef4444; color: white;">
                        <div
                            class="mb-2 w-12 h-12 flex items-center justify-center bg-white/20 rounded-xl group-hover:scale-110 transition-transform">
                            <x-heroicon-o-shopping-bag class="w-8 h-8 text-white" style="width: 32px; height: 32px;" />
                        </div>
                        <span
                            style="font-size: 1rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em;">Pemesanan</span>
                    </div>
                    <div
                        style="flex: 1; padding: 1.5rem; text-align: center; display: flex; flex-direction: column; justify-content: center; background-color: #f9fafb;">
                        <div
                            style="font-size: 2rem; font-weight: 900; color: #1f2937; line-height: 1; margin-bottom: 0.5rem;">
                            {{ $orderCount ?? 0 }}
                        </div>
                        <div
                            style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; font-weight: 600; line-height: 1.25;">
                            Pemesanan belum selesai</div>
                    </div>
                </div>
            </div>

            <!-- Step 3: Pengiriman -->
            <div class="relative h-full group filter drop-shadow-sm transition-all hover:drop-shadow-md hover:z-50">
                <div class="flex flex-col h-full w-full"
                    style="border-radius: 2rem; overflow: hidden; border: 1px solid #e5e7eb; background-color: white; height: 100%;">
                    <div
                        style="height: 9rem; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 1rem; background-color: #06b6d4; color: white;">
                        <div
                            class="mb-2 w-12 h-12 flex items-center justify-center bg-white/20 rounded-xl group-hover:scale-110 transition-transform">
                            <x-heroicon-o-truck class="w-8 h-8 text-white" style="width: 32px; height: 32px;" />
                        </div>
                        <span
                            style="font-size: 1rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em;">Pengiriman</span>
                    </div>
                    <div
                        style="flex: 1; padding: 1.5rem; text-align: center; display: flex; flex-direction: column; justify-content: center; background-color: #f9fafb;">
                        <div
                            style="font-size: 2rem; font-weight: 900; color: #1f2937; line-height: 1; margin-bottom: 0.5rem;">
                            {{ $deliveryUnbilledCount ?? 0 }}
                        </div>
                        <div
                            style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; font-weight: 600; line-height: 1.25;">
                            Pengiriman belum ditagih</div>
                    </div>
                </div>
            </div>

            <!-- Step 4: Tagihan -->
            <div class="relative h-full group filter drop-shadow-sm transition-all hover:drop-shadow-md hover:z-50">
                <div class="flex flex-col h-full w-full"
                    style="border-radius: 2rem; overflow: hidden; border: 1px solid #e5e7eb; background-color: white; height: 100%;">
                    <div
                        style="height: 9rem; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 1rem; background-color: #3b82f6; color: white;">
                        <div
                            class="mb-2 w-12 h-12 flex items-center justify-center bg-white/20 rounded-xl group-hover:scale-110 transition-transform">
                            <x-heroicon-o-document-text class="w-8 h-8 text-white" style="width: 32px; height: 32px;" />
                        </div>
                        <span
                            style="font-size: 1rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em;">Tagihan</span>
                    </div>
                    <div
                        style="flex: 1; padding: 1.5rem; text-align: center; display: flex; flex-direction: column; justify-content: center; background-color: #f9fafb;">
                        <div
                            style="font-size: 2rem; font-weight: 900; color: #1f2937; line-height: 1; margin-bottom: 0.5rem;">
                            {{ $overdueCount ?? 0 }}
                        </div>
                        <div
                            style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; font-weight: 600; line-height: 1.25;">
                            {{ $overdueCount }} Tagihan jatuh tempo
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>