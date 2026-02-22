@php
    $record = $this->record;
    $account = $record->items()
        ->whereHas('account', function ($q) {
            $q->where('category', 'Kas & Bank');
        })
        ->first()?->account;

    $accountName = $account ? $account->name : 'Kas';
    $accountCode = $account ? $account->code : '';

    $txData = $this->getTransactionData();
    $transactionType = "Transaksi: " . $txData['type'];
    $sourceUrl = $txData['source_url'];
    $recipientName = $txData['recipient'];
    $recipientUrl = $txData['recipient_url'];
    $refinedRef = $txData['reference'];
@endphp

<x-filament-panels::page>
    <style>
        .tx-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }

        .dark .tx-card {
            background: #1f2937;
            border-color: #374151;
        }

        .tx-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 20px;
            padding: 24px;
        }

        .tx-label {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .dark .tx-label {
            color: #9ca3af;
        }

        .tx-value {
            font-size: 14px;
            font-weight: 500;
            color: #111827;
        }

        .dark .tx-value {
            color: #f9fafb;
        }

        .tx-value-link {
            color: #1d4ed8;
            text-decoration: none;
        }

        .tx-value-link:hover {
            text-decoration: underline;
        }

        .tx-table {
            width: 100%;
            border-collapse: collapse;
        }

        .tx-table thead {
            background: #f9fafb;
        }

        .dark .tx-table thead {
            background: #111827;
        }

        .tx-table th {
            padding: 12px 16px;
            font-size: 12px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid #e5e7eb;
        }

        .dark .tx-table th {
            color: #9ca3af;
            border-color: #374151;
        }

        .tx-table td {
            padding: 12px 16px;
            font-size: 14px;
            border-bottom: 1px solid #f3f4f6;
        }

        .dark .tx-table td {
            border-color: #374151;
        }

        .tx-summary {
            padding: 24px;
            border-top: 1px solid #e5e7eb;
        }

        .dark .tx-summary {
            border-color: #374151;
        }

        .tx-summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
        }

        .tx-summary-total {
            border-top: 2px solid #e5e7eb;
            margin-top: 8px;
            padding-top: 12px;
        }

        .dark .tx-summary-total {
            border-color: #374151;
        }

        .tx-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
        }

        .tx-badge-unreconciled {
            background: #fef2f2;
            color: #dc2626;
        }

        .tx-badge-reconciled {
            background: #d1fae5;
            color: #059669;
        }

        .tx-tag {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            background: #dbeafe;
            color: #1e40af;
            margin-right: 4px;
        }

        .dark .tx-tag {
            background: #1e3a8a;
            color: #dbeafe;
        }

        .tx-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .tx-header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .tx-info-grid {
                grid-template-columns: 1fr;
                gap: 24px;
                padding: 16px;
            }

            .tx-grid-2 {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .tx-header-actions {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 12px;
            }
        }
    </style>

    {{-- Header Actions --}}
    <div class="no-print tx-header-actions">
        <div>
            <span class="tx-badge tx-badge-unreconciled">
                Unreconciled
            </span>
        </div>
        <div style="display: flex; gap: 8px;">
            <x-filament::button color="gray" size="sm" icon="heroicon-o-share">
                Bagikan
            </x-filament::button>
            <x-filament::button color="gray" size="sm" icon="heroicon-o-printer" onclick="window.print()">
                Print
            </x-filament::button>
        </div>
    </div>

    {{-- Main Card --}}
    <div class="tx-card">
        {{-- Info Section --}}
        <div class="tx-info-grid">
            {{-- Column 1: Recipient --}}
            <div>
                <div class="tx-label">Penerima</div>
                <div class="tx-value">
                    @if($recipientUrl)
                        <a href="{{ $recipientUrl }}" class="tx-value tx-value-link"
                            style="color: #1d4ed8; font-weight: 500;">
                            {{ $recipientName }}
                        </a>
                    @else
                        <span style="color: #1d4ed8; font-weight: 500;">{{ $recipientName }}</span>
                    @endif
                </div>
            </div>

            {{-- Column 2: Date --}}
            <div>
                <div class="tx-label">Tanggal Transaksi</div>
                <div class="tx-value">{{ $record->transaction_date?->format('d/m/Y') ?? '-' }}</div>
            </div>

            {{-- Column 3: Reference --}}
            <div>
                <div class="tx-label">Referensi</div>
                <div class="tx-value" style="color: #6b7280; font-weight: normal;">
                    {{ $refinedRef ?: '-' }}
                </div>
            </div>

            {{-- Column 4: Tag --}}
            <div>
                <div class="tx-label">Tag</div>
                <div style="margin-top: 4px;">
                    @forelse($record->tags as $tag)
                        <span class="tx-tag">{{ $tag->name }}</span>
                    @empty
                        <span class="tx-value" style="color: #6b7280;">-</span>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Items Table --}}
        <div style="overflow-x: auto;">
            <table class="tx-table">
                <thead>
                    <tr>
                        <th style="text-align: left; min-width: 300px;">Deskripsi</th>
                        <th style="text-align: right; min-width: 120px;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($record->items as $item)
                        @continue($account && $item->account_id === $account->id)
                        <tr>
                            <td>
                                @if($sourceUrl)
                                    <a href="{{ $sourceUrl }}" class="tx-value-link" style="color: #1d4ed8; font-weight: 400;">
                                        {{ $txData['type'] }} {{ $record->reference_number }}
                                    </a>
                                @else
                                    <div style="color: #1d4ed8; font-weight: 400;">
                                        {{ $txData['type'] }} {{ $record->reference_number }}
                                    </div>
                                @endif
                            </td>
                            <td style="text-align: right; font-weight: 600;">
                                {{ number_format($item->debit > 0 ? $item->debit : $item->credit, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Summary Section --}}
        <div class="tx-summary">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px;">
                {{-- Notes (Left) --}}
                <div>
                    @if($record->attachment)
                        <div class="tx-label" style="margin-bottom: 8px;">Attachment</div>
                        <div class="tx-value">
                            <a href="{{ Storage::url($record->attachment) }}" target="_blank" class="tx-value-link">
                                <x-heroicon-m-paper-clip class="w-4 h-4 inline" />
                                {{ basename($record->attachment) }}
                            </a>
                        </div>
                    @endif
                </div>

                {{-- Totals (Right) --}}
                <div style="display: flex; justify-content: flex-end;">
                    <div style="width: 100%; max-width: 320px;">
                        <div class="tx-summary-row tx-summary-total">
                            <span style="font-weight: 600; font-size: 15px;">Total</span>
                            <span
                                style="font-weight: 700; font-size: 16px;">{{ number_format($record->total_amount ?? 0, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Audit Log --}}
    <div style="margin-top: 48px; padding-top: 32px; border-top: 1px solid #e5e7eb;">
        <div style="font-size: 14px; font-weight: 500; margin-bottom: 8px;">
            <a href="{{ \App\Filament\Pages\ComingSoon::getUrl(['feature' => 'Audit Log']) }}" class="tx-value-link"
                style="text-decoration: none;">
                Pantau log perubahan data
            </a>
        </div>
        <div style="display: flex; align-items: center; gap: 6px; font-size: 13px; color: #6b7280;">
            <x-heroicon-o-pencil-square style="width: 14px; height: 14px;" />
            Terakhir diubah pada {{ $record->updated_at?->format('d M Y H:i') ?? '-' }}
        </div>
    </div>
</x-filament-panels::page>