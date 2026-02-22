<x-filament-panels::page>
    <style>
        .kas-grid {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .kas-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            padding: 24px;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            gap: 16px;
            transition: all 0.2s;
            text-decoration: none;
        }

        .kas-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 24px;
            position: relative;
            z-index: 2;
        }

        .kas-card-title h3 {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }

        .kas-card-title p {
            font-size: 14px;
            color: #64748b;
            margin: 4px 0 0;
        }

        .kas-card-actions {
            display: flex;
            gap: 8px;
        }

        .kas-card-body {
            display: flex;
            position: relative;
            min-height: 350px;
            /* Increased for widget */
            gap: 24px;
        }

        .kas-metrics {
            width: 200px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            gap: 24px;
            position: relative;
            z-index: 2;
            padding-top: 20px;
        }

        .metric-item-label {
            font-size: 13px;
            color: #64748b;
            font-weight: 500;
            margin-top: 4px;
        }

        .metric-item-value {
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
        }

        .metric-item-value.large {
            font-size: 20px;
        }

        .kas-widget-container {
            flex-grow: 1;
            /* Ensure it respects the flex container */
            min-width: 0;
        }

        /* Buttons */
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            color: #fff;
            transition: all 0.2s;
            cursor: pointer;
            text-decoration: none;
            white-space: nowrap;
        }

        .btn-connect {
            background-color: #3b82f6;
        }

        .btn-connect:hover {
            background-color: #2563eb;
        }

        .btn-manage {
            background-color: #22c55e;
        }

        .btn-manage:hover {
            background-color: #16a34a;
        }

        /* Dark Mode */
        .dark .kas-card {
            background: #1e293b;
            border-color: #334155;
        }

        .dark .kas-card-title h3,
        .dark .metric-item-value {
            color: #f1f5f9;
        }

        .dark .kas-card-title p,
        .dark .metric-item-label {
            color: #94a3b8;
        }
    </style>

    <div class="kas-grid">
        @livewire(\App\Filament\Widgets\DashboardFilter::class)

        @foreach($accounts as $index => $account)
            {{-- Calculate basic metrics if needed, but Widget handles chart --}}

            <div class="kas-card">
                <div class="kas-card-header">
                    <div class="kas-card-title">
                        <h3>{{ $account['name'] }}</h3>
                        <p>{{ $account['code'] }}</p>
                    </div>
                    <div class="kas-card-actions">
                        @if(str_contains(strtolower($account['name']), 'bca'))
                            <div class="btn-action btn-connect" title="Bank Connect">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
                                </svg>
                                <span>Bank Connect</span>
                            </div>
                        @endif
                        <div onclick="event.preventDefault(); window.location.href='{{ \App\Filament\Pages\KasBankDetail::getUrl(['record' => $account['id']]) }}'"
                            class="btn-action btn-manage">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Atur Akun
                        </div>
                    </div>
                </div>

                <div class="kas-card-body">
                    <div class="kas-metrics">
                        <div>
                            <div class="metric-item-value">0</div>
                            <div class="metric-item-label">Saldo di bank</div>
                        </div>
                        <div>
                            <div class="metric-item-value large">
                                {{ number_format($account['current_balance'], 0, ',', '.') }}
                            </div>
                            <div class="metric-item-label">Saldo di kledo</div>
                        </div>
                    </div>

                    <div class="kas-widget-container">
                        @livewire(\App\Filament\Widgets\AccountBalanceChart::class, [
                            'accountId' => $account['id']
                        ], key($account['id']))
                </div>
                        </div>
                    </div>
        @endforeach
    </div>
</x-filament-panels::page>