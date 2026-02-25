<x-filament-panels::page>
    {{-- Section Card --}}
    <x-filament::section>
        <div style="padding: 0px;">
            @foreach ($activities as $activity)
                @php
                    $causer = $activity->causer;
                    $causerName = $causer ? $causer->name : 'Sistem';
                    $rawModelName = class_basename($activity->subject_type ?? 'Unknown');

                    $modelName = match ($rawModelName) {
                        'SalesOrder' => 'Pesanan Penjualan',
                        'SalesInvoice' => 'Faktur Penjualan',
                        'SalesDelivery' => 'Pengiriman Penjualan',
                        'SalesQuotation' => 'Penawaran Penjualan',
                        'PurchaseOrder' => 'Pesanan Pembelian',
                        'PurchaseInvoice' => 'Faktur Pembelian',
                        'PurchaseDelivery' => 'Pengiriman Pembelian',
                        'PurchaseQuote' => 'Penawaran Pembelian',
                        'PaymentTerm' => 'Termin Pembayaran',
                        'ShippingMethod' => 'Metode Pengiriman',
                        'Tax' => 'Pajak',
                        'Unit' => 'Satuan',
                        'User' => 'Pengguna',
                        'Role' => 'Peran',
                        'BankTransaction' => 'Transaksi Bank',
                        'Account' => 'Akun',
                        'JournalEntry' => 'Jurnal',
                        'InventoryTransfer' => 'Transfer Inventori',
                        'InventoryAdjustment' => 'Penyesuaian Inventori',
                        'ProductionOrder' => 'Perintah Produksi',
                        'FixedAsset' => 'Aset Tetap',
                        'Expense' => 'Biaya',
                        'AccountTransaction' => 'Transaksi Akun',
                        'DebtPayment' => 'Pembayaran Hutang',
                        'CreditPayment' => 'Pembayaran Piutang',
                        'ReceivePayment' => 'Penerimaan Pembayaran',
                        default => $rawModelName,
                    };

                    $verb = match ($activity->description) {
                        'created' => 'dibuat',
                        'updated' => 'diubah',
                        'deleted' => 'dihapus',
                        default => $activity->description,
                    };

                    $typeLine = "Tipe: {$rawModelName}";
                    if ($activity->subject_id) {
                        $typeLine .= " ID: {$activity->subject_id}";
                    }

                    $ipAddress = $activity->properties['ip'] ?? request()->ip();
                    $isLast = $loop->last;
                @endphp

                <div style="display: flex; align-items: flex-start; gap: 16px; position: relative;">

                    {{-- Left: Time Ago --}}
                    <div
                        style="width: 120px; flex-shrink: 0; text-align: right; font-size: 13px; color: #9ca3af; padding-top: 3px; white-space: nowrap;">
                        {{ $activity->created_at->diffForHumans() }}
                    </div>

                    {{-- Center: Dot + Line --}}
                    <div
                        style="position: relative; width: 24px; flex-shrink: 0; display: flex; justify-content: center; align-self: stretch;">
                        @unless($isLast)
                            <div
                                style="position: absolute; top: 0; bottom: 0; left: 50%; transform: translateX(-50%); width: 3px; background: #e5e7eb;">
                            </div>
                        @endunless
                        <div
                            style="position: relative; top: 5px; width: 16px; height: 16px; border-radius: 50%; background: white; border: 4px solid #3b82f6; z-index: 2; flex-shrink: 0;">
                        </div>
                    </div>

                    {{-- Right: Details --}}
                    <div style="flex: 1; padding-bottom: 28px;">
                        <div style="font-size: 14px; line-height: 1.6;">
                            <span style="color: #3b82f6; font-weight: 500;">{{ $modelName }}</span>
                            <span style="color: #6b7280;"> {{ $verb }} oleh {{ $causerName }}</span>
                        </div>
                        <div style="margin-top: 3px; font-size: 12px; color: #9ca3af;">
                            {{ $typeLine }}
                        </div>
                        <div style="margin-top: 2px; font-size: 13px; color: #3b82f6; font-weight: 500;">
                            {{ $ipAddress }}
                        </div>
                        <div style="margin-top: 2px; font-size: 12px; color: #9ca3af;">
                            {{ $activity->created_at->format('d/m/Y H:i') }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($activities->hasPages())
            <x-slot name="footer">
                <x-filament::pagination :paginator="$activities" />
            </x-slot>
        @endif
    </x-filament::section>
</x-filament-panels::page>