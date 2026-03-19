{{-- Print-only header (hidden on screen) --}}
<div id="print-report-header" style="display: none;">
    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 0;">
        <img src="{{ asset('images/logo.png') }}" alt="Logo" style="height: 32px; width: auto;">
        <div>
            <div style="font-size: 13px; font-weight: 700;">{{ config('app.company_name', 'PT Delta Mas Tech') }}</div>
        </div>
    </div>
    <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 0;">Daftar Produk
    </div>
    <div style="font-size: 9px; color: #64748b; margin-bottom: 0;">
        Dicetak: {{ now('Asia/Jakarta')->format('d/m/Y H:i') }} oleh {{ auth()->user()->name ?? 'System' }}
    </div>
</div>

<style>
        @page {
        size: A4 landscape !important;
        margin: 5mm 10mm !important;
    }

    

    @media print {
        .fi-topbar, .fi-sidebar, .fi-sidebar-close-overlay, .fi-header-heading, .fi-page-header,
        .fi-breadcrumbs, .fi-tabs, .fi-ta-header-toolbar, nav, button, a.fi-btn,
        .fi-header-actions, .fi-pagination, .fi-footer, footer, [class*="fi-ac-"] {
            display: none !important;
        }

                #print-report-header {
            display: block !important;
            width: 100% !important;
            margin-bottom: 0 !important;
            padding: 5mm 5mm 0 5mm !important;
            background-color: white !important;
            border: none !important;
        }

        body, html {
            margin: 0 !important;
            padding: 0 !important;
            background: white !important;
            font-size: 7px !important;
        }

        .fi-ta-ctn {
            border: none !important;
            box-shadow: none !important;
            border-radius: 0 !important;
            overflow: visible !important;
            background: white !important;
        }

        .fi-ta-table {
            width: 100% !important;
            table-layout: fixed !important;
            border-collapse: collapse !important;
            border: 1px solid #cbd5e1 !important;
        }

        .fi-ta-header-cell {
            background-color: #f1f5f9 !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            font-size: 6.5px !important;
            padding: 3px 4px !important;
            color: #1e293b !important;
            border: 1px solid #cbd5e1 !important;
            white-space: nowrap !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .fi-ta-cell {
            padding: 3px 4px !important;
            font-size: 7px !important;
            border: 1px solid #cbd5e1 !important;
            background-color: white !important;
        }

        .fi-ta-row {
            break-inside: avoid !important;
            background-color: white !important;
        }

        .fi-badge {
            font-size: 6px !important;
            padding: 0 4px !important;
            border: none !important;
        }

        .fi-ta-actions, .fi-ta-record-checkbox, th:has(input), td:has(input) {
            display: none !important;
        }
    }
</style>

<script>
    window.addEventListener('beforeprint', function () {
        const header = document.getElementById('print-report-header');
        const page = document.querySelector('.fi-page');
        if (header && page && header.parentNode !== page) {
            page.insertBefore(header, page.firstChild);
        }
    });
</script>