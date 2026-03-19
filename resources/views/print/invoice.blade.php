<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faktur - {{ $record->invoice_number }}</title>
    <style>
        @page {
            size: A4;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            font-size: 9.5pt;
            color: #333;
            line-height: 1.5;
            padding: 30px 40px;
        }

        /* ===== HEADER ===== */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 25px;
        }

        .logo-container {
            flex: 0 0 auto;
        }

        .logo-container img {
            max-height: 75px;
            width: auto;
        }

        .header-right {
            text-align: right;
        }

        .doc-title {
            font-size: 22pt;
            font-weight: 700;
            color: #1e3a5f;
            margin: 0 0 6px 0;
        }

        .header-info-table {
            border-collapse: collapse;
            margin-left: auto;
        }

        .header-info-table td {
            padding: 1px 0;
            font-size: 9pt;
            color: #333;
        }

        .header-info-table td:first-child {
            text-align: right;
            padding-right: 10px;
            font-weight: 600;
        }

        .header-info-table td:last-child {
            text-align: right;
            min-width: 90px;
        }

        /* ===== INFO SECTIONS ===== */
        .info-row {
            display: flex;
            gap: 40px;
            margin-bottom: 20px;
        }

        .info-col {
            flex: 1;
        }

        .info-label {
            font-size: 9pt;
            font-weight: 400;
            color: #555;
            border-bottom: 1px solid #999;
            padding-bottom: 4px;
            margin-bottom: 10px;
        }

        .info-company-name {
            font-size: 10pt;
            font-weight: 700;
            color: #1e3a5f;
            margin-bottom: 3px;
        }

        .info-detail {
            font-size: 8.5pt;
            line-height: 1.6;
            color: #444;
        }

        /* ===== ITEMS TABLE ===== */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
            border: 1px solid #cbd5e1;
        }

        .items-table thead th {
            background-color: #f1f5f9; color: #334155;
            font-size: 8.5pt;
            font-weight: 600;
            padding: 7px 10px;
            text-align: left;
            border: 1px solid #cbd5e1;
        }

        .items-table tbody td {
            font-size: 9pt;
            padding: 7px 10px;
            border: 1px solid #cbd5e1;
            vertical-align: top;
        }

        .items-table tbody tr:nth-child(even) {
            background-color: #f7f8fa;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        /* ===== FOOTER AREA ===== */
        .footer-area {
            display: flex;
            gap: 0;
            margin-top: 0;
        }

        .footer-left {
            flex: 1;
            padding-right: 20px;
        }

        .footer-right {
            flex: 0 0 300px;
        }

        /* Pesan Section */
        .pesan-label {
            font-size: 9pt;
            font-weight: 700;
            font-style: italic;
            margin-bottom: 6px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 3px;
        }

        .pesan-content {
            font-size: 8.5pt;
            margin-bottom: 10px;
            min-height: 20px;
        }

        .snk {
            font-size: 8.5pt;
            margin-bottom: 12px;
        }

        .snk strong {
            display: block;
            margin-bottom: 2px;
        }

        .terbilang-section {
            font-size: 8.5pt;
            margin-top: 10px;
        }

        .terbilang-section strong {
            text-decoration: underline;
            display: block;
            margin-bottom: 2px;
        }

        /* Totals */
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-table td {
            font-size: 9pt;
            padding: 4px 0;
        }

        .totals-table .label-cell {
            text-align: right;
            padding-right: 15px;
            font-weight: 600;
        }

        .totals-table .value-cell {
            text-align: right;
        }

        .totals-table tr.total-row td {
            font-size: 11pt;
            font-weight: 700;
            padding-top: 8px;
            border-top: 1px solid #333;
        }

        .totals-table tr.total-row .label-cell {
            text-decoration: underline;
        }

        .totals-table tr.total-row .value-cell {
            color: #1e3a5f;
            text-decoration: underline;
        }

        .totals-table tr.balance-row td {
            font-size: 10pt;
            font-weight: 700;
            padding-top: 6px;
            color: #dc2626;
        }

        /* ===== SIGNATURE ===== */
        .signature-area {
            margin-top: 40px;
            text-align: center;
            float: right;
            width: 250px;
        }

        .signature-area .hormat {
            font-size: 9pt;
            margin-bottom: 60px;
        }

        .signature-area .company-name {
            font-size: 10pt;
            font-weight: 700;
            color: #1e3a5f;
            text-decoration: underline;
        }

        .signature-area .jabatan {
            font-size: 9pt;
            color: #555;
        }

        /* ===== PRINT ===== */
        @media print {
            .no-print {
                display: none !important;
            }
        }

        @media screen {
            body {
                max-width: 210mm;
                margin: 0 auto;
                background: #fff;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            }
        }
    </style>
</head>

<body onload="window.print()">
    {{-- Print toolbar --}}
    <div class="no-print"
        style="position:fixed;top:0;left:0;right:0;z-index:999;background:#334155;padding:8px 20px;display:flex;justify-content:space-between;align-items:center;color:#fff;font-size:13px;">
        <span>Pratinjau Cetak — Faktur</span>
        <button onclick="window.print()"
            style="background:#2563eb;color:#fff;border:none;padding:7px 20px;border-radius:4px;cursor:pointer;font-weight:600;font-size:13px;">🖨
            Cetak Sekarang</button>
    </div>
    <div class="no-print" style="height:45px;"></div>

    {{-- ===== HEADER ===== --}}
    <div class="header">
        <div class="logo-container">
            @if($company && $company->logo_path)
                <img src="{{ asset('storage/' . $company->logo_path) }}" alt="Logo">
            @else
                <img src="{{ asset('images/logo.svg') }}" alt="Logo" style="max-height:75px;">
            @endif
        </div>
        <div class="header-right">
            <h1 class="doc-title">Faktur</h1>
            <table class="header-info-table">
                <tr>
                    <td>Nomor</td>
                    <td>{{ $record->invoice_number }}</td>
                </tr>
                <tr>
                    <td>Tanggal</td>
                    <td>{{ $record->transaction_date ? $record->transaction_date->format('d/m/Y') : '-' }}</td>
                </tr>
                <tr>
                    <td>Tgl. Jatuh Tempo</td>
                    <td>{{ $record->due_date ? $record->due_date->format('d/m/Y') : '-' }}</td>
                </tr>
                @if($record->paymentTerm)
                    <tr>
                        <td>Termin</td>
                        <td>{{ $record->paymentTerm->name }}</td>
                    </tr>
                @endif
            </table>
        </div>
    </div>

    {{-- ===== INFO SECTIONS ===== --}}
    <div class="info-row">
        <div class="info-col">
            <div class="info-label">Informasi Perusahaan</div>
            <div class="info-company-name">{{ $company->name ?? 'PT. Delta Mas Tech' }}</div>
            <div class="info-detail">
                {!! nl2br(e($company->address ?? "Taman Raya Rajeg Blok K 23 No. 23, Mekarsari\nRajeg Pasar Kemis, Kab. Tangerang, Banten")) !!}<br>
                Telp: {{ $company->phone ?? '087880363936' }}<br>
                Email: {{ $company->email ?? 'deltamastech@gmail.com' }}<br>
                NPWP : {{ $company->npwp ?? '53.364.447.2-402.000' }}
            </div>
        </div>
        <div class="info-col">
            <div class="info-label">Tagihan Kepada</div>
            @php
                $contact = $record->contact;
            @endphp
            <div class="info-company-name">{{ $contact->name ?? '-' }}</div>
            <div class="info-detail">
                {!! nl2br(e($contact->address ?? '-')) !!}
                @if($contact && $contact->phone)
                    <br>Telp: {{ $contact->phone }}
                @endif
            </div>
        </div>
    </div>

    {{-- ===== ITEMS TABLE ===== --}}
    <table class="items-table">
        <thead>
            <tr>
                <th width="5%" class="text-center">No.</th>
                <th width="30%">Produk</th>
                <th width="15%" class="text-center">Kuantitas</th>
                <th width="15%" class="text-right">Harga</th>
                <th width="12%" class="text-center">Diskon</th>
                <th width="18%" class="text-right">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @foreach($record->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->product->name ?? '-' }}</td>
                    <td class="text-center">
                        {{ number_format($item->qty, 0, ',', '.') }}{{ $item->unit ? ' ' . $item->unit->name : '' }}
                    </td>
                    <td class="text-right">{{ number_format($item->price, 0, ',', '.') }}</td>
                    <td class="text-center">
                        {{ $item->discount_percent ? number_format($item->discount_percent, 0) . '%' : '0%' }}
                    </td>
                    <td class="text-right">{{ number_format($item->subtotal ?? ($item->qty * $item->price), 0, ',', '.') }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ===== FOOTER ===== --}}
    <div class="footer-area">
        {{-- LEFT: Pesan, S&K, Terbilang --}}
        <div class="footer-left">
            <div class="pesan-label">Pesan</div>
            <div class="pesan-content">{!! nl2br(e($record->notes)) !!}</div>

            <div class="snk">
                <strong>S&K</strong>
            </div>

            <div class="terbilang-section">
                <strong>Terbilang</strong>
                {{ App\Helpers\NumberHelper::terbilang($record->total_amount) }} Rupiah
            </div>
        </div>

        {{-- RIGHT: Totals --}}
        <div class="footer-right">
            @php
                $totalTax = $record->items->sum('tax_amount');
                if ($totalTax == 0 && $record->total_tax > 0) {
                    $totalTax = $record->total_tax;
                }
            @endphp
            <table class="totals-table">
                <tr>
                    <td class="label-cell">Subtotal</td>
                    <td class="value-cell">Rp {{ number_format($record->sub_total, 0, ',', '.') }}</td>
                </tr>
                @if($totalTax > 0)
                    <tr>
                        <td class="label-cell">Pajak PPN 11%</td>
                        <td class="value-cell">Rp {{ number_format($totalTax, 0, ',', '.') }}</td>
                    </tr>
                @endif
                @if($record->discount_total > 0)
                    <tr>
                        <td class="label-cell">Diskon</td>
                        <td class="value-cell">- Rp {{ number_format($record->discount_total, 0, ',', '.') }}</td>
                    </tr>
                @endif
                @if($record->shipping_cost > 0)
                    <tr>
                        <td class="label-cell">Biaya Pengiriman</td>
                        <td class="value-cell">Rp {{ number_format($record->shipping_cost, 0, ',', '.') }}</td>
                    </tr>
                @endif
                @if($record->other_cost > 0)
                    <tr>
                        <td class="label-cell">Biaya Lain</td>
                        <td class="value-cell">Rp {{ number_format($record->other_cost, 0, ',', '.') }}</td>
                    </tr>
                @endif
                <tr class="total-row">
                    <td class="label-cell">Total</td>
                    <td class="value-cell">Rp {{ number_format($record->total_amount, 0, ',', '.') }}</td>
                </tr>
                @if($record->down_payment > 0)
                    <tr>
                        <td class="label-cell" style="color:#16a34a;">Pembayaran</td>
                        <td class="value-cell" style="color:#16a34a;">- Rp
                            {{ number_format($record->down_payment, 0, ',', '.') }}
                        </td>
                    </tr>
                @endif
                @if($balanceDue > 0)
                    <tr class="balance-row">
                        <td class="label-cell">Sisa Tagihan</td>
                        <td class="value-cell">Rp {{ number_format($balanceDue, 0, ',', '.') }}</td>
                    </tr>
                @endif
            </table>
        </div>
    </div>

    {{-- ===== SIGNATURE ===== --}}
    <div style="clear:both;"></div>
    <div class="signature-area">
        <div class="hormat">Dengan Hormat,</div>
        <div class="company-name">{{ auth()->user()->name ?? $company->name ?? 'PT. Delta Mas Tech' }}</div>
        <div class="jabatan">{{ auth()->user()->job_title ?? 'Jabatan' }}</div>
    </div>
    <div style="clear:both;"></div>


    <div style="position: fixed; bottom: 0; left: 0; right: 0; font-size: 8px; color: #64748b; text-align: left; padding: 5px 0;">
        Dicetak: {{ now('Asia/Jakarta')->format('d/m/Y H:i') }} oleh {{ auth()->user()->name ?? 'System' }}
    </div>
</body>

</html>