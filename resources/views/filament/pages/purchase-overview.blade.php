<x-filament-panels::page>
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
                        Ringkasan Pembelian
                    </p>
                </div>
            </div>
            <div style="text-align: right;">
                <p style="margin: 0; font-size: 10px; color: #64748b;">Periode:</p>
                <p style="margin: 2px 0 0 0; font-size: 13px; font-weight: 600; color: #1e3a5f;">
                    @if($this->filter === 'month')
                        {{ now()->startOfMonth()->format('d M Y') }} &mdash; {{ now()->endOfMonth()->format('d M Y') }}
                    @else
                        {{ now()->startOfYear()->format('d M Y') }} &mdash; {{ now()->endOfYear()->format('d M Y') }}
                    @endif
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

            /* ─── Hide UI chrome ─── */
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

            /* ─── Layout reset ─── */
            .fi-layout {
                display: block !important;
            }

            .fi-page .fi-page-header {
                display: none !important;
            }

            .fi-header-widgets {
                margin-top: 10px !important;
            }

            #print-report-header {
                display: block !important;
                width: 100% !important;
                margin-bottom: 14px !important;
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

            /* ─── Stats overview styling ─── */
            .fi-wi-stats-overview {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                margin-bottom: 10px !important;
            }

            .fi-wi-stats-overview-stat {
                border: 1px solid #e2e8f0 !important;
                border-radius: 6px !important;
                padding: 8px 12px !important;
                background: #f8fafc !important;
            }

            .fi-wi-stats-overview-stat-label {
                font-size: 9px !important;
                font-weight: 700 !important;
                text-transform: uppercase !important;
                color: #64748b !important;
                letter-spacing: 0.5px !important;
            }

            .fi-wi-stats-overview-stat-value {
                font-size: 14px !important;
                font-weight: 700 !important;
                color: #1e293b !important;
            }

            .fi-wi-stats-overview-stat-description {
                font-size: 9px !important;
                color: #94a3b8 !important;
            }

            /* ─── Chart titles — VISIBLE ─── */
            .fi-wi-chart header {
                display: block !important;
                visibility: visible !important;
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
            }

            .fi-wi-chart header p+p {
                font-size: 8px !important;
                color: #94a3b8 !important;
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

            /* ─── Custom widget styling ─── */
            .fi-wi-widget {
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

    <!-- Widgets are rendered automatically by getHeaderWidgets() -->

    <script>
        window.addEventListener('beforeprint', function () {
            var header = document.getElementById('print-report-header');
            var fiPage = document.querySelector('.fi-page');
            if (header && fiPage) {
                fiPage.insertBefore(header, fiPage.firstChild);
            }
        });
    </script>
</x-filament-panels::page>