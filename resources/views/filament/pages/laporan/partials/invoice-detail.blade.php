@php
    $formatNumber = function ($num) {
        if ($num == 0)
            return '0';
        return number_format($num, 0, ',', '.');
    };

    $statusColor = match (strtolower($invoice->status)) {
        'lunas', 'paid' => '#10b981',
        'terbit', 'posted', 'open' => '#3b82f6',
        'draf', 'draft' => '#64748b',
        default => '#ef4444',
    };
    $statusLabel = match (strtolower($invoice->status)) {
        'lunas', 'paid' => 'Lunas',
        'terbit', 'posted', 'open' => 'Terbit',
        'draf', 'draft' => 'Draf',
        default => ucfirst($invoice->status),
    };

    $receivable = \App\Models\Receivable::where('invoice_number', $invoice->invoice_number)->first();
    $paid = ($receivable ? $receivable->payments()->sum('amount') : 0) + ($invoice->down_payment ?? 0);
@endphp

<div style="font-family: inherit; line-height: 1.5;">
    {{-- Invoice Summary --}}
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem;">
        <div>
            <div
                style="font-size: 0.7rem; font-weight: 600; color: #9ca3af; text-transform: uppercase; margin-bottom: 0.125rem;">
                Nomor</div>
            <div style="font-size: 0.875rem; font-weight: 700; color: #3b82f6;">{{ $invoice->invoice_number }}</div>
        </div>
        <div>
            <div
                style="font-size: 0.7rem; font-weight: 600; color: #9ca3af; text-transform: uppercase; margin-bottom: 0.125rem;">
                Tanggal</div>
            <div style="font-size: 0.875rem; font-weight: 600; color: #111827;">
                {{ $invoice->transaction_date->format('d/m/Y') }}</div>
        </div>
        <div>
            <div
                style="font-size: 0.7rem; font-weight: 600; color: #9ca3af; text-transform: uppercase; margin-bottom: 0.125rem;">
                Nama Kontak</div>
            <div style="font-size: 0.875rem; font-weight: 600; color: #111827;">{{ $invoice->contact->name }}</div>
        </div>
        <div>
            <div
                style="font-size: 0.7rem; font-weight: 600; color: #9ca3af; text-transform: uppercase; margin-bottom: 0.125rem;">
                Status</div>
            <div>
                <span
                    style="display: inline-flex; align-items: center; padding: 0.125rem 0.625rem; border-radius: 9999px; font-size: 0.7rem; font-weight: 700; color: white; background-color: {{ $statusColor }};">{{ $statusLabel }}</span>
            </div>
        </div>
    </div>

    {{-- Items Table --}}
    <div style="border: 1px solid #e5e7eb; border-radius: 0.5rem; overflow: hidden; margin-bottom: 1.5rem;">
        <table style="width: 100%; border-collapse: collapse; font-size: 0.8125rem;">
            <thead>
                <tr style="background-color: #f9fafb;">
                    <th
                        style="padding: 0.5rem 0.75rem; text-align: left; font-size: 0.7rem; font-weight: 700; color: #6b7280; text-transform: uppercase;">
                        No.</th>
                    <th
                        style="padding: 0.5rem 0.75rem; text-align: left; font-size: 0.7rem; font-weight: 700; color: #6b7280; text-transform: uppercase;">
                        Kode/SKU</th>
                    <th
                        style="padding: 0.5rem 0.75rem; text-align: left; font-size: 0.7rem; font-weight: 700; color: #6b7280; text-transform: uppercase;">
                        Nama Produk</th>
                    <th
                        style="padding: 0.5rem 0.75rem; text-align: right; font-size: 0.7rem; font-weight: 700; color: #6b7280; text-transform: uppercase;">
                        Qty</th>
                    <th
                        style="padding: 0.5rem 0.75rem; text-align: left; font-size: 0.7rem; font-weight: 700; color: #6b7280; text-transform: uppercase;">
                        Satuan</th>
                    <th
                        style="padding: 0.5rem 0.75rem; text-align: right; font-size: 0.7rem; font-weight: 700; color: #6b7280; text-transform: uppercase;">
                        Harga</th>
                    <th
                        style="padding: 0.5rem 0.75rem; text-align: right; font-size: 0.7rem; font-weight: 700; color: #6b7280; text-transform: uppercase;">
                        Diskon</th>
                    <th
                        style="padding: 0.5rem 0.75rem; text-align: right; font-size: 0.7rem; font-weight: 700; color: #6b7280; text-transform: uppercase;">
                        Pajak</th>
                    <th
                        style="padding: 0.5rem 0.75rem; text-align: right; font-size: 0.7rem; font-weight: 700; color: #6b7280; text-transform: uppercase;">
                        Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->items as $index => $item)
                    <tr style="border-top: 1px solid #f3f4f6;">
                        <td style="padding: 0.5rem 0.75rem; color: #6b7280;">{{ $index + 1 }}</td>
                        <td style="padding: 0.5rem 0.75rem; font-family: monospace; font-size: 0.7rem; color: #6b7280;">
                            {{ $item->product->sku ?? '-' }}</td>
                        <td style="padding: 0.5rem 0.75rem; font-weight: 600; color: #3b82f6;">
                            {{ $item->product->name ?? '-' }}
                            @if ($item->qty < 0)
                                <span style="color: #ef4444; font-size: 0.7rem;">(Retur)</span>
                            @endif
                        </td>
                        <td style="padding: 0.5rem 0.75rem; text-align: right;">{{ number_format($item->qty, 0) }}</td>
                        <td style="padding: 0.5rem 0.75rem; color: #6b7280;">{{ $item->unit->name ?? '-' }}</td>
                        <td style="padding: 0.5rem 0.75rem; text-align: right;">{{ $formatNumber($item->price) }}</td>
                        <td style="padding: 0.5rem 0.75rem; text-align: right;">{{ $item->discount_percent ?? 0 }}%</td>
                        <td style="padding: 0.5rem 0.75rem; text-align: right;">{{ $formatNumber($item->tax_amount) }}</td>
                        <td style="padding: 0.5rem 0.75rem; text-align: right; font-weight: 700;">
                            {{ $formatNumber($item->subtotal) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Totals --}}
    <div style="display: flex; justify-content: flex-end;">
        <div style="width: 18rem; font-size: 0.8125rem;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem; color: #6b7280;">
                <span>Sub Total</span>
                <span style="font-weight: 700; color: #111827;">{{ $formatNumber($invoice->sub_total) }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; color: #6b7280;">
                <span>Pajak</span>
                <span style="font-weight: 700; color: #111827;">{{ $formatNumber($invoice->total_tax) }}</span>
            </div>
            <div
                style="display: flex; justify-content: space-between; border-top: 2px solid #e5e7eb; padding-top: 0.75rem; margin-bottom: 0.25rem;">
                <span style="font-weight: 700; color: #111827;">Total</span>
                <span
                    style="font-weight: 800; color: #3b82f6; font-size: 1.125rem;">{{ $formatNumber($invoice->total_amount) }}</span>
            </div>
            <div
                style="display: flex; justify-content: space-between; font-size: 0.75rem; color: #9ca3af; margin-bottom: 0.125rem;">
                <span>Total Dibayar</span>
                <span>{{ $formatNumber($paid) }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 0.75rem;">
                <span style="color: #9ca3af;">Sisa Tagihan</span>
                <span style="font-weight: 700; color: #ef4444;">{{ $formatNumber($invoice->balance_due) }}</span>
            </div>
        </div>
    </div>
</div>