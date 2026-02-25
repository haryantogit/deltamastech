<x-filament-panels::page>
    <style>
        .report-header {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1);
        }

        .dark .report-header {
            background: #18181b;
            border-color: rgba(255, 255, 255, 0.1);
            box-shadow: none;
        }

        .header-icon.piutang {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: #dcfce7;
            color: #16a34a;
        }

        .dark .header-icon.piutang {
            background: rgba(22, 163, 74, 0.1);
        }

        .header-icon svg {
            width: 24px;
            height: 24px;
        }

        .header-title {
            font-size: 24px;
            font-weight: 700;
            color: #09090b;
            margin: 0 0 4px 0;
            line-height: 1.2;
        }

        .dark .header-title {
            color: #fafafa;
        }

        .header-subtitle {
            font-size: 14px;
            color: #71717a;
            margin: 0;
        }

        .dark .header-subtitle {
            color: #a1a1aa;
        }

        @media screen {
            .print-only {
                display: none !important;
            }
        }

        @media print {
            body {
                background: white !important;
            }

            .fi-sidebar,
            .fi-topbar,
            .fi-page-header-actions,
            button {
                display: none !important;
            }

            header {
                display: none !important;
            }

            .fi-main {
                margin: 0 !important;
                padding: 0 !important;
            }

            .fi-ta {
                box-shadow: none !important;
                border: none !important;
            }

            .font-bold {
                color: #000 !important;
            }

            .text-danger-600 {
                color: #dc2626 !important;
            }

            .text-warning-600 {
                color: #d97706 !important;
            }

            title {
                display: none;
            }

            .screen-only {
                display: none !important;
            }

            .print-only {
                display: block !important;
                margin-bottom: 24px;
            }

            .print-title {
                font-size: 24px;
                font-weight: bold;
                margin-bottom: 4px;
                color: #000;
            }

            .print-subtitle {
                font-size: 14px;
                color: #333;
            }
        }
    </style>

    <div class="report-header screen-only">
        <div class="header-icon piutang">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
        </div>
        <div>
            <h2 class="header-title">Laporan Umur Piutang (AR Aging)</h2>
            <p class="header-subtitle">
                Posisi Saldo Piutang per Tanggal: <strong
                    style="color: inherit;">{{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</strong>
            </p>
        </div>
    </div>

    <div class="print-only">
        <div class="print-title">Laporan Umur Piutang (AR Aging)</div>
        <div class="print-subtitle">Per Tanggal: {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</div>
    </div>

    {{ $this->table }}
</x-filament-panels::page>