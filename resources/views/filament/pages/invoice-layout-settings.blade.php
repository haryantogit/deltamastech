<x-filament-panels::page>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Panel Kiri: Form Pengaturan -->
        <div class="space-y-6">
            <form wire:submit="save">
                {{ $this->form }}
            </form>
        </div>

        <!-- Panel Kanan: Live Preview Dummy -->
        <div
            class="bg-gray-100 dark:bg-gray-900 rounded-xl p-4 lg:p-8 flex items-start justify-center overflow-auto min-h-[600px] border border-gray-200 dark:border-gray-800">
            <div class="bg-white dark:bg-gray-800 shadow-2xl w-full max-w-[500px] p-8 rounded-lg text-gray-800 dark:text-gray-200 font-sans"
                style="aspect-ratio: 1 / 1.414;">
                <!-- Header Penjual -->
                <div class="flex justify-between items-start mb-8">
                    <div>
                        @if($data['show_company_info'])
                            <h1 class="text-xl font-bold uppercase tracking-tight text-primary-600 dark:text-primary-400">
                                PT. Delta Mas Tech</h1>
                            <p class="text-[10px] text-gray-500 max-w-[180px] leading-relaxed mt-1">
                                Grand Galaxy City, Ruko RSK 3 No. 22 Jaka Setia, Bekasi Selatan, Kota Bekasi 17147
                            </p>
                        @endif
                    </div>
                    <div class="bg-gray-100 dark:bg-gray-700 rounded-md flex items-center justify-center text-gray-400"
                        style="width: {{ $data['logo_size'] }}px; height: {{ $data['logo_size'] * 0.6 }}px;">
                        <span class="text-[10px]">LOGO</span>
                    </div>
                </div>

                <div class="border-t-2 border-gray-100 dark:border-gray-700 my-6"></div>

                <h2 class="text-center text-lg font-bold uppercase tracking-widest mb-8 text-gray-900 dark:text-white">
                    {{ $data['document_type'] }}</h2>

                <!-- Info Invoice -->
                <div class="flex justify-between text-[11px] mb-8">
                    <div class="space-y-4">
                        @if($data['show_billing_info'])
                            <div>
                                <p class="text-gray-400 uppercase font-bold text-[9px] mb-1">Tagihan Kepada</p>
                                <p class="font-bold text-gray-900 dark:text-white">CV. Sejahtera Abadi</p>
                                <p class="text-gray-500 leading-relaxed">Jl. Raya Industri No. 45<br>Cikarang, Jawa Barat
                                </p>
                            </div>
                        @endif
                    </div>
                    <div class="text-right space-y-1.5 min-w-[120px]">
                        @if($data['show_invoice_number'])
                            <div class="flex justify-between gap-4">
                                <span class="text-gray-400">Nomor:</span>
                                <span class="font-mono text-gray-900 dark:text-white">INV/2024/0001</span>
                            </div>
                        @endif
                        @if($data['show_date'])
                            <div class="flex justify-between gap-4">
                                <span class="text-gray-400">Tanggal:</span>
                                <span class="text-gray-900 dark:text-white">{{ now()->format('d/m/Y') }}</span>
                            </div>
                        @endif
                        @if($data['show_due_date'])
                            <div class="flex justify-between gap-4">
                                <span class="text-gray-400">Jatuh Tempo:</span>
                                <span class="text-gray-900 dark:text-white">{{ now()->addDays(30)->format('d/m/Y') }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Table Dummy -->
                <table class="w-full text-[10px] mb-8">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="text-left py-2 px-2 text-gray-400 uppercase font-bold">Produk</th>
                            <th class="text-center py-2 px-2 text-gray-400 uppercase font-bold">Qty</th>
                            <th class="text-right py-2 px-2 text-gray-400 uppercase font-bold">Harga</th>
                            <th class="text-right py-2 px-2 text-gray-400 uppercase font-bold">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <tr>
                            <td class="py-3 px-2">
                                <p class="font-bold text-gray-900 dark:text-white">Laptop Macbook Pro M3</p>
                                <p class="text-[9px] text-gray-400">Serial: A23129301293</p>
                            </td>
                            <td class="text-center py-3 px-2">1</td>
                            <td class="text-right py-3 px-2 text-gray-900 dark:text-white">Rp 25.000.000</td>
                            <td class="text-right py-3 px-2 font-bold text-gray-900 dark:text-white">Rp 25.000.000</td>
                        </tr>
                        <tr>
                            <td class="py-3 px-2 font-bold text-gray-900 dark:text-white">Magic Mouse 2</td>
                            <td class="text-center py-3 px-2">2</td>
                            <td class="text-right py-3 px-2 text-gray-900 dark:text-white">Rp 1.250.000</td>
                            <td class="text-right py-3 px-2 font-bold text-gray-900 dark:text-white">Rp 2.500.000</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Totals -->
                <div class="flex justify-end">
                    <div class="w-1/2 space-y-2 text-[11px]">
                        <div class="flex justify-between text-gray-400">
                            <span>Subtotal:</span>
                            <span class="text-gray-900 dark:text-white">Rp 27.500.000</span>
                        </div>
                        <div
                            class="flex justify-between text-gray-400 border-b border-gray-100 dark:border-gray-700 pb-2">
                            <span>Pajak (11%):</span>
                            <span class="text-gray-900 dark:text-white">Rp 3.025.000</span>
                        </div>
                        <div
                            class="flex justify-between font-bold text-[14px] text-primary-600 dark:text-primary-400 pt-1">
                            <span>Total:</span>
                            <span>Rp 30.525.000</span>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div
                    class="mt-12 text-[9px] text-gray-400 border-t border-dashed border-gray-200 dark:border-gray-700 pt-4 italic">
                    <p>Terima kasih atas kepercayaan Anda bertransaksi dengan PT. Delta Mas Tech.</p>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>