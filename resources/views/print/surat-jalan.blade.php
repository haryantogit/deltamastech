<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Jalan - {{ $delivery ? $delivery->number : $record->invoice_number }}</title>
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
            margin-bottom: 20px;
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

        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            text-align: center;
        }

        .sig-block {
            width: 22%;
        }

        .sig-block .sig-title {
            font-size: 8.5pt;
            font-weight: 600;
            color: #555;
            margin-bottom: 60px;
        }

        .sig-block .sig-line {
            font-size: 8.5pt;
            color: #999;
        }

        .sig-block .sig-name {
            font-size: 9pt;
            font-weight: 700;
            color: #1e3a5f;
            text-decoration: underline;
        }

        .sig-block .sig-jabatan {
            font-size: 8pt;
            color: #555;
        }

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
    <div class="no-print"
        style="position:fixed;top:0;left:0;right:0;z-index:999;background:#334155;padding:8px 20px;display:flex;justify-content:space-between;align-items:center;color:#fff;font-size:13px;">
        <span>Pratinjau Cetak — Surat Jalan</span>
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
            <h1 class="doc-title">Surat Jalan</h1>
            <table class="header-info-table">
                <tr>
                    <td>Nomor</td>
                    <td>{{ $delivery ? $delivery->number : '-' }}</td>
                </tr>
                <tr>
                    <td>Tanggal</td>
                    <td>{{ $delivery && $delivery->date ? $delivery->date->format('d/m/Y') : ($record->transaction_date ? $record->transaction_date->format('d/m/Y') : '-') }}
                    </td>
                </tr>
                <tr>
                    <td>Pemesanan</td>
                    <td>{{ $record->salesOrder ? $record->salesOrder->number : '-' }}</td>
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
            <div class="info-label">Kepada</div>
            @php $contact = $record->contact; @endphp
            <div class="info-company-name">{{ $contact->name ?? '-' }}</div>
            <div class="info-detail">
                @if($delivery && $delivery->shippingMethod)
                    Ekspedisi: {{ $delivery->courier ?? $delivery->shippingMethod->name ?? '-' }}<br>
                @elseif($delivery && $delivery->courier)
                    Ekspedisi: {{ $delivery->courier }}<br>
                @endif
                @if($delivery && $delivery->tracking_number)
                    Nomor Resi: {{ $delivery->tracking_number }}
                @endif
            </div>
        </div>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th width="5%" class="text-center">No.</th>
                <th width="30%">Produk</th>
                <th width="30%">Deskripsi</th>
                <th width="15%" class="text-center">Kuantitas</th>
                <th width="20%" class="text-right">Harga</th>
            </tr>
        </thead>
        <tbody>
            @php
                $items = ($delivery && $delivery->items->count() > 0) ? $delivery->items : $record->items;
            @endphp
            @foreach($items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->product->name ?? '-' }}</td>
                    <td>{{ $item->description ?? '-' }}</td>
                    <td class="text-center">{{ number_format($item->quantity ?? $item->qty ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right">
                        @if(isset($item->price))
                            {{ number_format($item->price, 0, ',', '.') }}
                        @elseif(isset($item->unit_price))
                            {{ number_format($item->unit_price, 0, ',', '.') }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="signatures">
        <div class="sig-block">
            <div class="sig-title">Diperiksa Oleh</div>
            <div class="sig-line">(..........................)</div>
        </div>
        <div class="sig-block">
            <div class="sig-title">Diterima Oleh</div>
            <div class="sig-line">(..........................)</div>
        </div>
        <div class="sig-block">
            <div class="sig-title">Pengirim</div>
            <div class="sig-line">(..........................)</div>
        </div>
        <div class="sig-block">
            <div class="sig-title">Dengan Hormat,</div>
            <div class="sig-name">{{ auth()->user()->name ?? $company->name ?? 'PT. Delta Mas Tech' }}</div>
            <div class="sig-jabatan">{{ auth()->user()->job_title ?? 'Jabatan' }}</div>
        </div>
    </div>

    <div style="position: fixed; bottom: 0; left: 0; right: 0; font-size: 8px; color: #64748b; text-align: left; padding: 5px 0;">
        Dicetak: {{ now('Asia/Jakarta')->format('d/m/Y H:i') }} oleh {{ auth()->user()->name ?? 'System' }}
    </div>
</body>

</html>