<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Label Pengiriman - {{ $record->invoice_number }}</title>
    <style>
        @page {
            size: A5 landscape;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            font-size: 9pt;
            color: #333;
            line-height: 1.4;
            padding: 20px;
        }

        .label-card {
            border: 2px solid #333;
            max-width: 550px;
            margin: 0 auto;
        }

        .section {
            padding: 12px 15px;
        }

        .section-border {
            border-bottom: 2px solid #333;
        }

        .section-title {
            font-size: 8pt;
            font-weight: 600;
            color: #1e3a5f;
            margin-bottom: 2px;
        }

        .section-name {
            font-size: 11pt;
            font-weight: 700;
            color: #333;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .fragile-icon {
            width: 60px;
            height: auto;
        }

        .company-info {
            font-size: 8pt;
            line-height: 1.5;
            color: #444;
            margin-top: 3px;
        }

        .logo-small {
            max-height: 40px;
            width: auto;
            float: right;
            margin-left: 10px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        .items-table thead th {
            background-color: #f1f5f9; color: #334155;
            font-size: 8pt;
            font-weight: 600;
            padding: 5px 8px;
            text-align: left;
        }

        .items-table tbody td {
            font-size: 8.5pt;
            padding: 5px 8px;
            border: 1px solid #cbd5e1;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
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
                background: #f1f5f9;
            }

            .label-card {
                background: #fff;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            }
        }
    </style>
</head>

<body onload="window.print()">
    <div class="no-print"
        style="position:fixed;top:0;left:0;right:0;z-index:999;background:#334155;padding:8px 20px;display:flex;justify-content:space-between;align-items:center;color:#fff;font-size:13px;">
        <span>Pratinjau Cetak — Label Pengiriman</span>
        <button onclick="window.print()"
            style="background:#2563eb;color:#fff;border:none;padding:7px 20px;border-radius:4px;cursor:pointer;font-weight:600;font-size:13px;">🖨
            Cetak Sekarang</button>
    </div>
    <div class="no-print" style="height:45px;"></div>

    <div class="label-card">
        {{-- Kepada Section --}}
        <div class="section section-border">
            <div class="section-header">
                <div>
                    <div class="section-title">Kepada</div>
                    @php $contact = $record->contact; @endphp
                    <div class="section-name">{{ $contact->name ?? '-' }}</div>
                    @if($contact && $contact->address)
                        <div class="company-info">{{ $contact->address }}</div>
                    @endif
                    @if($contact && $contact->phone)
                        <div class="company-info">Telp: {{ $contact->phone }}</div>
                    @endif
                </div>
                <div style="text-align:center;">
                    <div style="font-size:40px;color:#dc2626;">⚠️</div>
                    <div style="font-size:8pt;font-weight:700;color:#dc2626;">FRAGILE</div>
                </div>
            </div>
        </div>

        {{-- Dari Section --}}
        <div class="section section-border">
            <div class="section-header">
                <div>
                    <div class="section-title">Dari</div>
                    <div class="section-name">{{ $company->name ?? 'PT. Delta Mas Tech' }}</div>
                    <div class="company-info">
                        {!! nl2br(e($company->address ?? "Taman Raya Rajeg Blok K 23 No. 23, Mekarsari\nRajeg Pasar Kemis, Kab. Tangerang, Banten")) !!}<br>
                        Telp: {{ $company->phone ?? '087880363936' }}<br>
                        Email: {{ $company->email ?? 'deltamastech@gmail.com' }}<br>
                        NPWP : {{ $company->npwp ?? '53.364.447.2-402.000' }}
                    </div>
                    @if($delivery)
                        <div class="company-info" style="margin-top:5px;">
                            @if($delivery->courier)
                                Ekspedisi: {{ $delivery->courier }}<br>
                            @endif
                            @if($delivery->tracking_number)
                                Nomor Resi: {{ $delivery->tracking_number }}
                            @endif
                        </div>
                    @endif
                </div>
                <div>
                    @if($company && $company->logo_path)
                        <img src="{{ asset('storage/' . $company->logo_path) }}" class="logo-small" alt="Logo">
                    @else
                        <img src="{{ asset('images/logo.svg') }}" class="logo-small" alt="Logo">
                    @endif
                </div>
            </div>
        </div>

        {{-- Items Section --}}
        <div class="section">
            @php
                $items = ($delivery && $delivery->items->count() > 0) ? $delivery->items : $record->items;
            @endphp
            <table class="items-table">
                <thead>
                    <tr>
                        <th width="45%">Produk</th>
                        <th width="35%">Deskripsi</th>
                        <th width="20%" class="text-center">Kuantitas</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        <tr>
                            <td>{{ $item->product->name ?? '-' }}</td>
                            <td>{{ $item->description ?? '-' }}</td>
                            <td class="text-center">{{ number_format($item->quantity ?? $item->qty ?? 0, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div style="position: fixed; bottom: 0; left: 0; right: 0; font-size: 8px; color: #64748b; text-align: left; padding: 5px 0;">
        Dicetak: {{ now('Asia/Jakarta')->format('d/m/Y H:i') }} oleh {{ auth()->user()->name ?? 'System' }}
    </div>
</body>

</html>