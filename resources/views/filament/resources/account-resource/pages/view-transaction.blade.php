<x-filament-panels::page>
    <style>
        .po-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }

        .dark .po-card {
            background: #1f2937;
            border-color: #374151;
        }

        .po-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 32px;
            padding: 24px;
        }

        .po-label {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .dark .po-label {
            color: #9ca3af;
        }

        .po-value {
            font-size: 14px;
            font-weight: 500;
            color: #111827;
        }

        .dark .po-value {
            color: #f9fafb;
        }

        .po-value-link {
            color: #1d4ed8;
            text-decoration: none;
        }

        .po-value-link:hover {
            text-decoration: underline;
        }

        .po-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .po-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
        }

        .po-badge-success {
            background: #d1fae5;
            color: #059669;
        }

        @media (max-width: 768px) {
            .po-info-grid {
                grid-template-columns: 1fr;
                gap: 24px;
            }

            .po-grid-2 {
                grid-template-columns: 1fr;
            }
        }
    </style>

    {{-- Status Header --}}
    <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
        <span class="po-badge po-badge-success">
            Lunas
        </span>
        <div style="display: flex; gap: 8px;">
            <x-filament::button color="gray" size="sm" icon="heroicon-o-share">
                Bagikan
            </x-filament::button>
            <x-filament::button color="gray" size="sm" icon="heroicon-o-printer" onclick="window.print()">
                Print
            </x-filament::button>
        </div>
    </div>

    <div class="po-card">
        {{-- Info Section --}}
        <div class="po-info-grid">
            {{-- Left: Vendor Info --}}
            <div>
                <div class="po-label">Kepada</div>
                <div class="po-value" style="margin-bottom: 8px;">
                    {{ $payment->debt->supplier->name ?? '-' }}
                </div>
                @if($payment->debt->supplier->company ?? false)
                    <div
                        style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #6b7280; margin-bottom: 4px;">
                        <x-heroicon-o-building-office style="width: 16px; height: 16px;" />
                        {{ $payment->debt->supplier->company }}
                    </div>
                @endif
                @if($payment->debt->supplier->phone ?? false)
                    <div
                        style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #6b7280; margin-bottom: 4px;">
                        <x-heroicon-o-phone style="width: 16px; height: 16px;" />
                        {{ $payment->debt->supplier->phone }}
                    </div>
                @endif
                @if($payment->debt->supplier->address ?? false)
                    <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #6b7280;">
                        <x-heroicon-o-map-pin style="width: 16px; height: 16px;" />
                        {{ $payment->debt->supplier->address }}
                    </div>
                @endif
            </div>

            {{-- Right: Details --}}
            <div class="po-grid-2">
                <div>
                    <div class="po-label">Nomor</div>
                    <div class="po-value">{{ $payment->number ?? '-' }}</div>
                </div>
                <div>
                    <div class="po-label">Tanggal</div>
                    <div class="po-value">{{ \Carbon\Carbon::parse($payment->date)->format('d/m/Y') }}</div>
                </div>
                <div>
                    <div class="po-label">Nomor Pemesanan</div>
                    <div class="po-value">
                        @if($this->orderNumber)
                            <a href="{{ $this->orderUrl }}" class="po-value-link">
                                {{ $this->orderNumber }}
                            </a>
                        @else
                            -
                        @endif
                    </div>
                </div>
                <div>
                    <div class="po-label">Metode Pembayaran</div>
                    <div class="po-value">{{ $record->name ?? '-' }}</div>
                </div>
                <div>
                    <div class="po-label">Referensi</div>
                    <div class="po-value">{{ $payment->debt->reference ?? '-' }}</div>
                </div>
                <div>
                    <div class="po-label">Tag</div>
                    <div class="po-value">
                        <span
                            style="background: #eff6ff; color: #1d4ed8; padding: 2px 8px; border-radius: 4px; font-size: 12px;">
                            {{ $record->name }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Details Table --}}
        <div style="margin: 0 24px 0 24px; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                <thead>
                    <tr style="background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                        <th
                            style="padding: 12px 16px; text-align: left; font-weight: 600; color: #4b5563; font-size: 12px; text-transform: uppercase;">
                            Deskripsi</th>
                        <th
                            style="padding: 12px 16px; text-align: right; font-weight: 600; color: #4b5563; font-size: 12px; text-transform: uppercase;">
                            Total</th>
                    </tr>
                </thead>
                <tbody style="background-color: white;">
                    <tr>
                        <td style="padding: 16px; border-bottom: 1px solid #f3f4f6;">
                            <div style="font-size: 14px; font-weight: 500; color: #3b82f6;">
                                @if($this->sourceUrl !== '#')
                                    <a href="{{ $this->sourceUrl }}" class="po-value-link"
                                        style="text-decoration: none; color: #3b82f6;">
                                        Pembayaran pembelian {{ $payment->debt->number ?? '-' }}
                                    </a>
                                @else
                                    Pembayaran pembelian {{ $payment->debt->number ?? '-' }}
                                @endif
                            </div>
                        </td>
                        <td
                            style="padding: 16px; text-align: right; border-bottom: 1px solid #f3f4f6; font-weight: 500;">
                            {{ number_format($payment->amount, 0, ',', '.') }}
                        </td>
                    </tr>
                    @if($this->downPayment > 0)
                        <tr>
                            <td style="padding: 16px; border-bottom: 1px solid #f3f4f6;">
                                <div style="font-size: 14px; color: #6b7280;">
                                    Pembayaran Pemesanan
                                </div>
                            </td>
                            <td
                                style="padding: 16px; text-align: right; border-bottom: 1px solid #f3f4f6; font-weight: 500;">
                                {{ number_format($this->downPayment, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endif
                </tbody>
                <tfoot>
                    <tr style="background-color: #f8fafc;">
                        <td style="padding: 16px; text-align: right; font-weight: 700; color: #1d4ed8;">Total</td>
                        <td
                            style="padding: 16px; text-align: right; font-weight: 700; color: #1d4ed8; font-size: 16px;">
                            {{ number_format($payment->amount, 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Attachment Section --}}
        <div style="margin: 32px 24px 0 24px;">
            <div class="po-label" style="margin-bottom: 8px; font-weight: 600;">ATTACHMENT</div>
            @if($payment->attachment ?? false)
                <div style="margin-top: 8px;">
                    <a href="{{ Storage::url($payment->attachment) }}" target="_blank"
                        style="color: #3b82f6; text-decoration: underline;">
                        Lihat Lampiran
                    </a>
                </div>
            @else
                <div style="font-style: italic; color: #9ca3af;">Tidak ada lampiran</div>
            @endif
        </div>

        {{-- Audit Log Section --}}
        <div style="margin: 32px 24px 24px 24px; padding-top: 24px; border-top: 1px solid #e5e7eb;">
            <a href="#" wire:click.prevent="auditLog"
                style="font-size: 14px; color: #3b82f6; font-weight: 500; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                <x-heroicon-o-clock style="width: 16px; height: 16px;" />
                <span>Pantau log perubahan data</span>
            </a>
            <div
                style="margin-top: 8px; font-size: 12px; color: #9ca3af; display: flex; align-items: center; gap: 4px;">
                <x-heroicon-o-pencil-square style="width: 14px; height: 14px;" />
                Terakhir diubah oleh system pada {{ $payment->updated_at->format('d M Y H:i') }}
            </div>
        </div>
    </div>
</x-filament-panels::page>