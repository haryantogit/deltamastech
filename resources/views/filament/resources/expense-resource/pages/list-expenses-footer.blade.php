{{-- Print-only header (hidden on screen) --}}
<div id="print-report-header" style="display: none;">
    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 4px;">
        <img src="{{ asset('images/logo.png') }}" alt="Logo" style="height: 32px; width: auto;">
        <div>
            <div style="font-size: 13px; font-weight: 700;">{{ config('app.company_name', 'PT Delta Mas Tech') }}</div>
        </div>
    </div>
    <div style="border-bottom: 2px solid #1e293b; margin-bottom: 4px;"></div>
    <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 1px;">Biaya
    </div>
    <div style="font-size: 9px; color: #64748b; margin-bottom: 6px;">
        Dicetak: {{ now()->format('d/m/Y H:i') }}
    </div>
</div>

<style>
    @page {
        size: landscape !important;
        margin: 6mm !important;
    }

    @media print {

        .fi-topbar,
        .fi-sidebar,
        .fi-sidebar-close-overlay,
        .fi-header-heading,
        .fi-page-header,
        .fi-breadcrumbs,
        .fi-tabs,
        .fi-ta-header-toolbar,
        nav,
        button,
        a.fi-btn,
        .fi-header-actions,
        .fi-pagination,
        .fi-ta-ctn>.fi-ta-header-toolbar,
        [class*="fi-ac-"],
        .fi-section-header-ctn,
        .fi-wi-ctn,
        .fi-widgets,
        .fi-footer,
        footer {
            display: none !important;
        }

        #print-report-header {
            display: block !important;
            width: 100% !important;
            margin-bottom: 6px !important;
        }

        *,
        *::before,
        *::after {
            box-shadow: none !important;
        }

        body,
        html {
            margin: 0 !important;
            padding: 0 !important;
            background: white !important;
            font-size: 7px !important;
        }

        .fi-layout {
            display: block !important;
            min-height: 0 !important;
        }

        .fi-body-holder {
            display: block !important;
        }

        .fi-main-ctn,
        .fi-page,
        .fi-page-content,
        main,
        .fi-main {
            background: white !important;
            padding: 0 !important;
            margin: 0 !important;
            max-width: 100% !important;
            width: 100% !important;
        }

        .fi-ta-ctn {
            border: none !important;
            box-shadow: none !important;
            border-radius: 0 !important;
            overflow: visible !important;
        }

        .fi-ta-content,
        .fi-ta-table-ct {
            overflow: visible !important;
        }

        .fi-ta-table {
            font-size: 7px !important;
            width: 100% !important;
            table-layout: fixed !important;
        }

        .fi-ta-header-cell {
            background-color: #e2e8f0 !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            font-size: 6.5px !important;
            padding: 2px 3px !important;
            color: #1e293b !important;
            border: 1px solid #cbd5e1 !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            white-space: nowrap !important;
            line-height: 1.2 !important;
        }

        .fi-ta-cell {
            padding: 2px 3px !important;
            font-size: 7px !important;
            border: 1px solid #e2e8f0 !important;
            line-height: 1.2 !important;
        }

        .fi-ta-cell>div {
            padding: 0 !important;
            margin: 0 !important;
        }

        .fi-ta-row {
            break-inside: avoid !important;
        }

        .fi-ta-text,
        .fi-ta-text-item {
            font-size: 7px !important;
            gap: 0 !important;
        }

        .fi-ta-text-item-description {
            font-size: 6px !important;
        }

        .fi-badge {
            font-size: 6.5px !important;
            padding: 0px 4px !important;
            line-height: 1.4 !important;
            min-height: 0 !important;
        }

        input[type="checkbox"] {
            display: none !important;
        }

        .fi-ta-record-checkbox,
        th:has(input[type="checkbox"]),
        td:has(input[type="checkbox"]) {
            display: none !important;
        }

        .fi-ta-actions,
        .fi-ta-actions-header {
            display: none !important;
        }

        .fi-ta-header-cell-sort-btn svg,
        .fi-ta-header-cell button svg {
            display: none !important;
        }

        .fi-ta-header-cell-sort-btn {
            gap: 0 !important;
        }

        .fi-ta-col-wrp {
            overflow: hidden !important;
            text-overflow: ellipsis !important;
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