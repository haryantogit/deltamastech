<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemesanan - {{ $record->number }}</title>
    <style>
        @page {
            size: A4;
            margin: 15mm 20mm;
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

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 25px;
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

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
            border: 1px solid #2d3748;
        }

        .items-table thead th {
            background-color: #2d3748;
            color: #fff;
            font-size: 8.5pt;
            font-weight: 600;
            padding: 7px 10px;
            text-align: left;
            border: 1px solid #2d3748;
        }

        .items-table tbody td {
            font-size: 9pt;
            padding: 7px 10px;
            border-bottom: 1px solid #e2e8f0;
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

        .terbilang-section {
            font-size: 8.5pt;
            margin-top: 10px;
        }

        .terbilang-section strong {
            text-decoration: underline;
            display: block;
            margin-bottom: 2px;
        }

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

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                padding: 0;
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
    <div class="no-print"
        style="position:fixed;top:0;left:0;right:0;z-index:999;background:#334155;padding:8px 20px;display:flex;justify-content:space-between;align-items:center;color:#fff;font-size:13px;">
        <span>Pratinjau Cetak — Pemesanan</span>
        <button onclick="window.print()"
            style="background:#2563eb;color:#fff;border:none;padding:7px 20px;border-radius:4px;cursor:pointer;font-weight:600;font-size:13px;">🖨
            Cetak Sekarang</button>
    </div>
    <div class="no-print" style="height:45px;"></div>

    <div class="header">
        <div class="logo-container">
            @if($company && $company->logo_path)
                <img src="{{ asset('storage/' . $company->logo_path) }}" alt="Logo">
            @else
                <img src="{{ asset('images/logo.svg') }}" alt="Logo" style="max-height:75px;">
            @endif
        </div>
        <div class="header-right">
            <h1 class="doc-title">Pemesanan</h1>
            <table class="header-info-table">
                <tr>
                    <td>Nomor</td>
                    <td>{{ $record->number }}</td>
                </tr>
                <tr>
                    <td>Tanggal</td>
                    <td>{{ $record->date ? $record->date->format('d/m/Y') : '-' }}</td>
                </tr>
                <tr>
                    <td>Tgl. Jatuh Tempo</td>
                    <td>{{ $record->due_date ? $record->due_date->format('d/m/Y') : '-' }}</td>
                </tr>
            </table>
        </div>
    </div>

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
            <div class="info-label">Order Dari</div>
            @php $customer = $record->customer; @endphp
            <div class="info-company-name">{{ $customer->name ?? '-' }}</div>
            <div class="info-detail">
                {!! nl2br(e($customer->address ?? '-')) !!}
                @if($customer && $customer->phone)<br>Telp: {{ $customer->phone }}@endif
            </div>
        </div>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th width="25%">Produk</th>
                <th width="20%">Deskripsi</th>
                <th width="10%" class="text-center">Kuantitas</th>
                <th width="12%" class="text-right">Harga</th>
                <th width="10%" class="text-center">Diskon</th>
                <th width="10%" class="text-center">Pajak</th>
                <th width="13%" class="text-right">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @foreach($record->items as $item)
                <tr>
                    <td>{{ $item->product->name ?? '-' }}</td>
                    <td>{{ $item->description ?? '-' }}</td>
                    <td class="text-center">{{ number_format($item->quantity, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    <td class="text-center">
                        {{ $item->discount_percent ? number_format($item->discount_percent, 0) . '%' : '0%' }}
                    </td>
                    <td class="text-center">{{ $item->tax_name ?? '-' }}</td>
                    <td class="text-right">{{ number_format($item->total_price, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer-area">
        <div class="footer-left">
            <div class="terbilang-section">
                <strong>Terbilang</strong>
                {{ App\Helpers\NumberHelper::terbilang($record->total_amount) }} Rupiah
            </div>
        </div>
        <div class="footer-right">
            <table class="totals-table">
                <tr>
                    <td class="label-cell">Subtotal</td>
                    <td class="value-cell">Rp {{ number_format($record->sub_total, 0, ',', '.') }}</td>
                </tr>
                @if(($record->items->sum('tax_amount') ?? 0) > 0)
                    <tr>
                        <td class="label-cell">PPN</td>
                        <td class="value-cell">Rp {{ number_format($record->items->sum('tax_amount'), 0, ',', '.') }}</td>
                    </tr>
                @endif
                @if($record->discount_amount > 0)
                    <tr>
                        <td class="label-cell">Pemotongan</td>
                        <td class="value-cell">(Rp {{ number_format($record->discount_amount, 0, ',', '.') }})</td>
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
                @if($record->balance_due > 0)
                    <tr>
                        <td class="label-cell">Sisa Tagihan</td>
                        <td class="value-cell">Rp {{ number_format($record->balance_due, 0, ',', '.') }}</td>
                    </tr>
                @endif
            </table>
        </div>
    </div>

    <div style="clear:both;"></div>
    <div class="signature-area">
        <div class="hormat">Dengan Hormat,</div>
        <div class="company-name">{{ auth()->user()->name ?? $company->name ?? 'PT. Delta Mas Tech' }}</div>
        <div class="jabatan">{{ auth()->user()->job_title ?? 'Jabatan' }}</div>
    </div>
    <div style="clear:both;"></div>
</body>

</html>