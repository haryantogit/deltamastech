@php
    $record = $this->record;
    $contact = $record->contact;
    $items = $record->items;
    $journalEntry = \App\Models\JournalEntry::where('reference_number', $record->reference_number)->first();
@endphp

<x-filament-panels::page>
    <style>
        .expense-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }

        .dark .expense-card {
            background: #1f2937;
            border-color: #374151;
        }

        .expense-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .dark .expense-header {
            border-color: #374151;
        }

        .expense-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 32px;
            padding: 24px;
        }

        .expense-label {
            font-size: 11px;
            color: #6b7280;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
        }

        .dark .expense-label {
            color: #9ca3af;
        }

        .expense-value {
            font-size: 14px;
            font-weight: 500;
            color: #111827;
        }

        .dark .expense-value {
            color: #f9fafb;
        }

        .expense-value-link {
            color: #2563eb;
            text-decoration: none;
        }

        .expense-value-link:hover {
            text-decoration: underline;
        }

        .expense-table {
            width: 100%;
            border-collapse: collapse;
        }

        .expense-table thead {
            background: #f9fafb;
        }

        .dark .expense-table thead {
            background: #111827;
        }

        .expense-table th {
            padding: 12px 24px;
            font-size: 11px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }

        .dark .expense-table th {
            color: #9ca3af;
            border-color: #374151;
        }

        .expense-table td {
            padding: 16px 24px;
            font-size: 14px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: top;
        }

        .dark .expense-table td {
            border-color: #374151;
        }

        .expense-summary {
            padding: 24px;
            display: flex;
            justify-content: flex-end;
            background: #f9fafb;
            border-radius: 0 0 12px 12px;
        }

        .dark .expense-summary {
            background: #111827;
        }

        .summary-box {
            width: 300px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            font-size: 14px;
        }

        .summary-total {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #e5e7eb;
            font-size: 16px;
            font-weight: 700;
        }

        .dark .summary-total {
            border-color: #374151;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 10px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-success {
            background: #dcfce7;
            color: #166534;
        }

        .badge-warning {
            background: #fef9c3;
            color: #854d0e;
        }

        .payment-info {
            margin-top: 24px;
            padding: 16px 24px;
            border-top: 1px solid #e5e7eb;
            font-size: 13px;
            color: #6b7280;
        }

        .dark .payment-info {
            border-color: #374151;
            color: #9ca3af;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            .expense-card {
                border: none;
                box-shadow: none;
            }

            .expense-summary {
                background: transparent;
            }
        }
    </style>

    <div class="no-print"
        style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
        <div style="display: flex; gap: 12px; align-items: center;">
            <x-filament::button color="gray" size="sm" icon="heroicon-o-chevron-left" tag="a"
                href="{{ url('/admin/biaya') }}">
                Kembali
            </x-filament::button>
            <span class="badge {{ $record->remaining_amount <= 0 ? 'badge-success' : 'badge-warning' }}">
                {{ $record->remaining_amount <= 0 ? 'Lunas' : 'Belum Dibayar' }}
            </span>
        </div>
        <div style="display: flex; gap: 8px;">
            <x-filament::button color="gray" size="sm" icon="heroicon-o-printer" onclick="window.print()">
                Print
            </x-filament::button>
            <x-filament::button size="sm" icon="heroicon-o-pencil" tag="a"
                href="{{ \App\Filament\Resources\ExpenseResource::getUrl('edit', ['record' => $record]) }}">
                Ubah
            </x-filament::button>
        </div>
    </div>

    <div class="expense-card">
        <div class="expense-info-grid">
            <div>
                <div class="expense-label">Diberikan Kepada</div>
                <div class="expense-value" style="font-size: 18px;">
                    @if($contact)
                        <a href="{{ \App\Filament\Resources\ContactResource::getUrl('view', ['record' => $contact]) }}"
                            class="expense-value-link">
                            {{ $contact->name }}
                        </a>
                    @else
                        -
                    @endif
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div>
                    <div class="expense-label">Tgl. Transaksi</div>
                    <div class="expense-value">{{ $record->transaction_date?->format('d/m/Y') ?? '-' }}</div>
                </div>
                <div>
                    <div class="expense-label">No. Biaya</div>
                    <div class="expense-value">{{ $record->reference_number }}</div>
                </div>
                <div>
                    <div class="expense-label">Referensi</div>
                    <div class="expense-value" style="color: #2563eb;">
                        {{ $record->memo ?? '-' }}
                    </div>
                </div>
                <div>
                    <div class="expense-label">Tag</div>
                    <div class="expense-value text-xs">
                        @forelse($record->tags as $tag)
                            <span
                                style="background: #eff6ff; color: #1e40af; padding: 2px 6px; border-radius: 4px; margin-right: 4px;">{{ $tag->name }}</span>
                        @empty
                            -
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <table class="expense-table">
            <thead>
                <tr>
                    <th>Deskripsi</th>
                    <th>Akun Biaya</th>
                    <th style="text-align: right;">Pajak</th>
                    <th style="text-align: right;">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                    <tr>
                        <td>{{ $item->description ?? '-' }}</td>
                        <td>
                            @if($item->account)
                                <a href="{{ url('/admin/kas-bank/detail/' . $item->account->id) }}" class="expense-value-link">
                                    {{ $item->account->code }} - {{ $item->account->name }}
                                </a>
                            @else
                                <div class="expense-value">-</div>
                            @endif
                        </td>
                        <td style="text-align: right;">{{ $item->tax?->name ?? '-' }}</td>
                        <td style="text-align: right; font-weight: 600;">
                            Rp {{ number_format($item->amount, 0, ',', '.') }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Attachment Section --}}
        @if($record->attachment)
            <div style="padding: 16px 24px; border-top: 1px solid #e5e7eb;">
                <div class="expense-label" style="margin-bottom: 8px;">Attachment</div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <svg style="width: 16px; height: 16px; flex-shrink: 0; color: #6b7280;" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13">
                        </path>
                    </svg>
                    <a href="{{ Storage::url($record->attachment) }}" target="_blank" class="expense-value-link">
                        {{ basename($record->attachment) }}
                    </a>
                </div>
            </div>
        @endif

        <div class="expense-summary">
            <div class="summary-box">
                <div class="summary-row">
                    <span style="color: #6b7280;">Sub Total</span>
                    <span class="expense-value">Rp {{ number_format($record->sub_total ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="summary-row summary-total">
                    <span>Total</span>
                    <span>Rp {{ number_format($record->total_amount, 0, ',', '.') }}</span>
                </div>
                <div class="summary-row" style="color: #b91c1c; font-weight: 600; margin-top: 8px;">
                    <span>Sisa Tagihan</span>
                    <span>Rp {{ number_format($record->remaining_amount ?? 0, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <div class="payment-info">
            @if($record->is_pay_later)
                <div style="display: flex; align-items: center; gap: 8px;">
                    <svg style="width: 16px; height: 16px; flex-shrink: 0;" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Bayar Nanti (Hutang)
                </div>
            @elseif($record->account)
                <div style="display: flex; align-items: center; gap: 8px;">
                    <svg style="width: 16px; height: 16px; flex-shrink: 0;" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z">
                        </path>
                    </svg>
                    Dibayar dari
                    <a href="{{ url('/admin/kas-bank/detail/' . $record->account->id) }}" class="expense-value-link">
                        {{ $record->account->code }} - {{ $record->account->name }}
                    </a>
                </div>
            @endif

            @if($journalEntry)
                <div style="margin-top: 8px; display: flex; align-items: center; gap: 8px;">
                    <svg style="width: 16px; height: 16px; flex-shrink: 0;" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                    Jurnal: <a href="#" class="expense-value-link">#{{ $record->reference_number }}</a>
                </div>
            @endif
        </div>
    </div>

    @if($record->memo)
        <div style="margin-top: 24px;">
            <div class="expense-label">Pesan / Memo</div>
            <div class="expense-card" style="padding: 16px 24px; font-style: italic; color: #4b5563;">
                {{ $record->memo }}
            </div>
        </div>
    @endif
</x-filament-panels::page>