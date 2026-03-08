<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kwitansi - {{ $lastPayment ? $lastPayment->number : $record->invoice_number }}</title>
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

        .detail-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .detail-table td {
            padding: 10px 15px;
            font-size: 9pt;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }

        .detail-table tr:nth-child(even) {
            background-color: #f7f8fa;
        }

        .detail-table .detail-label {
            font-weight: 600;
            color: #1e3a5f;
            width: 180px;
        }

        .detail-table .detail-sep {
            width: 20px;
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .total-section {
            text-align: right;
            margin-top: 20px;
            margin-bottom: 30px;
        }

        .total-label {
            font-size: 11pt;
            font-weight: 700;
            text-decoration: underline;
            display: inline;
        }

        .total-value {
            font-size: 11pt;
            font-weight: 700;
            color: #1e3a5f;
            text-decoration: underline;
            margin-left: 30px;
            display: inline;
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
        <span>Pratinjau Cetak — Kwitansi</span>
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
            <h1 class="doc-title">Kwitansi</h1>
            <table class="header-info-table">
                <tr>
                    <td>Nomor</td>
                    <td>{{ $lastPayment ? $lastPayment->number : '-' }}</td>
                </tr>
                <tr>
                    <td>Tanggal</td>
                    <td>{{ $lastPayment && $lastPayment->date ? \Carbon\Carbon::parse($lastPayment->date)->format('d/m/Y') : ($record->transaction_date ? $record->transaction_date->format('d/m/Y') : '-') }}
                    </td>
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
            <div class="info-label">Telah Terima Dari</div>
            @php $contact = $record->contact; @endphp
            <div class="info-company-name">{{ $contact->name ?? '-' }}</div>
        </div>
    </div>

    <table class="detail-table">
        <tr>
            <td class="detail-label">Banyaknya Uang</td>
            <td class="detail-sep">:</td>
            <td>{{ App\Helpers\NumberHelper::terbilang($totalPaid) }} Rupiah</td>
        </tr>
        <tr>
            <td class="detail-label">Untuk Pembayaran</td>
            <td class="detail-sep">:</td>
            <td>Terima pembayaran tagihan {{ $record->invoice_number }}</td>
        </tr>
        <tr>
            <td class="detail-label">Pembayaran Diterima</td>
            <td class="detail-sep">:</td>
            <td>{{ $lastPayment && $lastPayment->account ? $lastPayment->account->name : '-' }}</td>
        </tr>
    </table>

    <div class="total-section">
        <span class="total-label">Total</span>
        <span class="total-value">Rp {{ number_format($totalPaid, 0, ',', '.') }}</span>
    </div>

    <div class="signatures">
        <div class="sig-block">
            <div class="sig-title">Dibuat Oleh</div>
            <div class="sig-line">(..........................)</div>
        </div>
        <div class="sig-block">
            <div class="sig-title">Diperiksa Oleh</div>
            <div class="sig-line">(..........................)</div>
        </div>
        <div class="sig-block">
            <div class="sig-title">Disetujui Oleh</div>
            <div class="sig-line">(..........................)</div>
        </div>
        <div class="sig-block">
            <div class="sig-title">Diterima Oleh</div>
            <div class="sig-line">(..........................)</div>
        </div>
    </div>
</body>

</html>