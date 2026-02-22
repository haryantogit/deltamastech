<x-filament-widgets::widget>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">

        {{-- LEFT CARD: Product Identity --}}
        <x-filament::section>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                {{-- Product Name & SKU --}}
                <div style="padding-bottom: 1rem; border-bottom: 1px solid #e5e7eb;">
                    <h1 style="font-size: 1.5rem; font-weight: 700; color: #111827; margin-bottom: 0.5rem;">
                        {{ $record->name }}
                    </h1>
                    <div style="font-size: 0.875rem; color: #6b7280; font-family: monospace;">
                        {{ $record->sku ?? 'N/A' }}
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    {{-- Category --}}
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <span style="font-size: 0.875rem; font-weight: 500; color: #6b7280; width: 8.5rem;">Kategori</span>
                        <span style="font-size: 0.875rem; color: #9ca3af;">:</span>
                        <span style="font-size: 0.875rem; color: #ea580c; font-weight: 600;">
                            {{ $record->category->name ?? 'Umum' }}
                        </span>
                    </div>

                    {{-- Inventory Account --}}
                    @if($record->track_inventory)
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <span style="font-size: 0.875rem; font-weight: 500; color: #6b7280; width: 8.5rem;">Akun Persediaan</span>
                            <span style="font-size: 0.875rem; color: #9ca3af;">:</span>
                            @if($record->inventoryAccount)
                                <a href="{{ route('filament.admin.pages.kas-bank.detail.{record}', ['record' => $record->inventoryAccount->id]) }}"
                                    style="font-size: 0.875rem; font-weight: 600; color: #2563eb; text-decoration: none; transition: color 0.2s;"
                                    onmouseover="this.style.color='#1d4ed8'; this.style.textDecoration='underline'"
                                    onmouseout="this.style.color='#2563eb'; this.style.textDecoration='none'">
                                    {{ $record->inventoryAccount->name }} <span
                                        style="color: #6b7280; font-weight: 400;">({{ $record->inventoryAccount->code }})</span>
                                </a>
                            @else
                                <span style="font-size: 0.875rem; font-weight: 600; color: #111827;">-</span>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Description --}}
                @if($record->description)
                    <div
                        style="background: linear-gradient(135deg, #fef3c7 0%, #fef9c3 100%); border-radius: 0.5rem; padding: 1rem; border-left: 4px solid #f59e0b; margin-top: 0.5rem;">
                        <h1
                            style="font-size: 0.75rem; font-weight: 700; color: #92400e; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                            <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                            Deskripsi
                        </h1>
                        <p style="font-size: 0.875rem; color: #374151; line-height: 1.6;">
                            {{ $record->description }}
                        </p>
                    </div>
                @endif
            </div>
        </x-filament::section>

        {{-- RIGHT CARD: Financial Data --}}
        <x-filament::section>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                {{-- Pembelian Section --}}
                <div style="background: #f9fafb; border-radius: 0.5rem; padding: 1rem;">
                    <h3
                        style="font-size: 0.75rem; font-weight: 700; color: #374151; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.75rem;">
                        Pembelian
                    </h3>
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <span style="font-size: 0.875rem; font-weight: 500; color: #6b7280; width: 7.5rem;">Harga</span>
                        <span style="font-size: 0.875rem; color: #9ca3af;">:</span>
                        <span style="font-size: 1rem; font-weight: 700; color: #111827;">
                            {{ number_format($record->buy_price ?? 0, 0, ',', '.') }}
                        </span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-top: 0.5rem;">
                        <span style="font-size: 0.875rem; font-weight: 500; color: #6b7280; width: 7.5rem;">Akun
                            Pembelian</span>
                        <span style="font-size: 0.875rem; color: #9ca3af;">:</span>
                        @if($record->purchaseAccount)
                            <a href="{{ route('filament.admin.pages.kas-bank.detail.{record}', ['record' => $record->purchaseAccount->id]) }}"
                                style="font-size: 0.875rem; font-weight: 600; color: #2563eb; text-decoration: none; transition: color 0.2s;"
                                onmouseover="this.style.color='#1d4ed8'; this.style.textDecoration='underline'"
                                onmouseout="this.style.color='#2563eb'; this.style.textDecoration='none'">
                                {{ $record->purchaseAccount->name }} <span
                                    style="color: #6b7280; font-weight: 400;">({{ $record->purchaseAccount->code }})</span>
                            </a>
                        @else
                            <span style="font-size: 0.875rem; font-weight: 600; color: #111827;">-</span>
                        @endif
                    </div>
                </div>

                {{-- Penjualan Section --}}
                <div
                    style="background: linear-gradient(135deg, #dbeafe 0%, #eff6ff 100%); border-radius: 0.5rem; padding: 1rem; border: 1px solid #bfdbfe;">
                    <h3
                        style="font-size: 0.75rem; font-weight: 700; color: #1d4ed8; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.75rem;">
                        Penjualan
                    </h3>
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <span style="font-size: 0.875rem; font-weight: 500; color: #6b7280; width: 7.5rem;">Harga</span>
                        <span style="font-size: 0.875rem; color: #9ca3af;">:</span>
                        <span style="font-size: 1rem; font-weight: 700; color: #111827;">
                            {{ number_format($record->sell_price ?? 0, 0, ',', '.') }}
                        </span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-top: 0.5rem;">
                        <span style="font-size: 0.875rem; font-weight: 500; color: #6b7280; width: 7.5rem;">Akun
                            Penjualan</span>
                        <span style="font-size: 0.875rem; color: #9ca3af;">:</span>
                        @if($record->salesAccount)
                            <a href="{{ route('filament.admin.pages.kas-bank.detail.{record}', ['record' => $record->salesAccount->id]) }}"
                                style="font-size: 0.875rem; font-weight: 600; color: #2563eb; text-decoration: none; transition: color 0.2s;"
                                onmouseover="this.style.color='#1d4ed8'; this.style.textDecoration='underline'"
                                onmouseout="this.style.color='#2563eb'; this.style.textDecoration='none'">
                                {{ $record->salesAccount->name }} <span
                                    style="color: #6b7280; font-weight: 400;">({{ $record->salesAccount->code }})</span>
                            </a>
                        @else
                            <span style="font-size: 0.875rem; font-weight: 600; color: #111827;">-</span>
                        @endif
                    </div>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-widgets::widget>