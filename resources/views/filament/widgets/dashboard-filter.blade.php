<x-filament-widgets::widget>
    {{-- Print-only professional header with logo --}}
    <div id="print-report-header">
        <div
            style="display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #1e3a5f; padding-bottom: 14px; margin-bottom: 10px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <img src="{{ asset('images/logo.png') }}" alt="Logo" style="height: 44px; width: auto;">
                <div>
                    <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: #1e3a5f; letter-spacing: 0.5px;">
                        {{ config('app.name', 'PT Delta Mas Tech') }}
                    </h1>
                    <p style="margin: 3px 0 0 0; font-size: 11px; color: #64748b; font-weight: 500;">
                        Laporan Dashboard Keuangan
                    </p>
                </div>
            </div>
            <div style="text-align: right;">
                <p style="margin: 0; font-size: 10px; color: #64748b;">Periode:</p>
                <p style="margin: 2px 0 0 0; font-size: 13px; font-weight: 600; color: #1e3a5f;">
                    {{ \Carbon\Carbon::parse($this->filters['startDate'] ?? now()->startOfYear())->format('d M Y') }}
                    &mdash;
                    {{ \Carbon\Carbon::parse($this->filters['endDate'] ?? now()->endOfYear())->format('d M Y') }}
                </p>
                <p style="margin: 4px 0 0 0; font-size: 10px; color: #94a3b8;">
                    Dicetak: {{ now()->format('d M Y, H:i') }}
                </p>
            </div>
        </div>
    </div>

    <style>
        @media screen {
            #print-report-header {
                display: none !important;
            }
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 10mm;
            }

            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            body {
                background: white !important;
                font-family: 'Segoe UI', 'Inter', Arial, sans-serif !important;
                color: #1e293b !important;
                margin: 0 !important;
                padding: 0 !important;
                font-size: 10px !important;
            }

            /* ─── Hide UI chrome (SPECIFIC, not generic 'header') ─── */
            .fi-sidebar,
            .fi-sidebar-active,
            .fi-sidebar-close-overlay,
            .fi-topbar,
            .fi-header-actions,
            .fi-breadcrumbs,
            .fi-page-header,
            .fi-ac-actions,
            aside,
            nav,
            footer,
            .fi-btn,
            [role="navigation"],
            .fi-dropdown,
            .fi-modal,
            .fi-notification,
            .fi-global-search,
            .fi-sidebar-nav,
            .fi-header-heading {
                display: none !important;
            }

            /* Hide filter form and stats — keep charts and tables */
            .fi-wi-dashboard-filter form,
            .fi-wi-dashboard-filter .fi-fo-component-ctn,
            .fi-wi-stats-overview {
                display: none !important;
            }

            /* Style table for print */
            .fi-ta-ctn {
                border: 1px solid #cbd5e1 !important;
                border-radius: 8px !important;
                overflow: hidden !important;
                box-shadow: none !important;
            }

            .fi-ta-header-cell {
                background: #f1f5f9 !important;
                font-size: 9px !important;
                font-weight: 700 !important;
                color: #475569 !important;
            }

            .fi-ta-cell {
                font-size: 10px !important;
            }

            #print-report-header {
                display: block !important;
                width: 100% !important;
                margin-bottom: 14px !important;
            }

            /* ─── Layout reset ─── */
            .fi-layout {
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
                width: 100% !important;
                max-width: 100% !important;
                margin-left: 0 !important;
                padding-left: 0 !important;
            }

            /* ─── Chart titles — VISIBLE ─── */
            .fi-wi-chart header {
                display: block !important;
                visibility: visible !important;
                height: auto !important;
                width: auto !important;
                position: static !important;
                overflow: visible !important;
            }

            .fi-wi-chart header p,
            .fi-wi-chart header h3,
            .fi-wi-chart header span {
                display: block !important;
                visibility: visible !important;
            }

            .fi-wi-chart header p:first-child,
            .fi-wi-chart header h3 {
                font-size: 10px !important;
                font-weight: 700 !important;
                color: #1e3a5f !important;
                text-transform: uppercase !important;
                letter-spacing: 0.5px !important;
                border-bottom: 1.5px solid #e2e8f0 !important;
                padding-bottom: 5px !important;
                margin-bottom: 6px !important;
                margin-top: 0 !important;
            }

            .fi-wi-chart header p+p {
                font-size: 8px !important;
                color: #94a3b8 !important;
                margin-top: -4px !important;
                margin-bottom: 4px !important;
                border-bottom: none !important;
                padding-bottom: 0 !important;
                text-transform: none !important;
                letter-spacing: normal !important;
                font-weight: 400 !important;
            }

            /* ─── Chart card styling ─── */
            .fi-wi-chart {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                border: 1px solid #cbd5e1 !important;
                border-radius: 8px !important;
                padding: 10px 12px !important;
                box-shadow: none !important;
                background: white !important;
                overflow: hidden !important;
                margin-bottom: 10px !important;
            }

            canvas {
                max-width: 100% !important;
                height: auto !important;
            }
        }
    </style>

    {{ $this->form }}
</x-filament-widgets::widget>