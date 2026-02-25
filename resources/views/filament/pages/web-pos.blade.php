<x-filament-panels::page>
    <style>

        .webpos-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 75vh;
        }

        .webpos-container {
            width: 100%;
            max-width: 580px;
            background: linear-gradient(135deg, #1e3a5f 0%, #0f172a 100%);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 25px 60px rgba(0,0,0,0.3);
        }

        .webpos-header {
            display: flex;
            align-items: stretch;
        }

        .webpos-welcome {
            flex: 1;
            padding: 28px 28px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .webpos-avatar {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: rgba(59, 130, 246, 0.15);
            border: 2px solid rgba(59, 130, 246, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .webpos-avatar svg {
            width: 26px;
            height: 26px;
            color: #60a5fa;
        }

        .webpos-greeting h2 {
            font-size: 17px;
            font-weight: 700;
            color: #fff;
            margin: 0 0 4px 0;
            line-height: 1.3;
        }

        .webpos-greeting p {
            font-size: 12px;
            color: #94a3b8;
            margin: 0;
        }

        .webpos-clock {
            background: linear-gradient(135deg, #f97316, #ea580c);
            padding: 20px 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-width: 110px;
        }

        .webpos-clock-time {
            font-size: 28px;
            font-weight: 900;
            color: #fff;
            letter-spacing: -1px;
            font-variant-numeric: tabular-nums;
            line-height: 1;
        }

        .webpos-clock-date {
            font-size: 9px;
            font-weight: 700;
            color: rgba(255,255,255,0.8);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-top: 4px;
        }

        .webpos-body {
            padding: 28px;
            background: #fff;
        }

        .webpos-outlet-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .webpos-outlet-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 14px;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .webpos-outlet-item:hover {
            border-color: #3b82f6;
            background: #eff6ff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
        }

        .webpos-outlet-icon {
            width: 44px;
            height: 44px;
            background: #f1f5f9;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
        }

        .webpos-outlet-item:hover .webpos-outlet-icon {
            background: #fff;
        }

        .webpos-outlet-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .webpos-outlet-icon svg {
            width: 22px;
            height: 22px;
            color: #94a3b8;
        }

        .webpos-outlet-info {
            flex: 1;
        }

        .webpos-outlet-name {
            font-size: 14px;
            font-weight: 700;
            color: #1e293b;
            margin: 0 0 2px 0;
        }

        .webpos-outlet-code {
            font-size: 11px;
            color: #94a3b8;
            margin: 0;
        }

        .webpos-outlet-arrow {
            width: 20px;
            height: 20px;
            color: #cbd5e1;
            transition: color 0.2s;
        }

        .webpos-outlet-item:hover .webpos-outlet-arrow {
            color: #3b82f6;
        }

        .webpos-empty {
            text-align: center;
            padding: 40px 20px;
        }

        .webpos-empty-icon {
            width: 56px;
            height: 56px;
            background: #f1f5f9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }

        .webpos-empty-icon svg {
            width: 28px;
            height: 28px;
            color: #cbd5e1;
        }

        .webpos-empty p {
            font-size: 13px;
            color: #94a3b8;
            line-height: 1.6;
        }

        .webpos-footer {
            background: #f8fafc;
            padding: 16px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-top: 1px solid #e2e8f0;
        }

        .webpos-footer-brand {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .webpos-footer-label {
            font-size: 9px;
            font-weight: 700;
            color: #cbd5e1;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .webpos-footer-name {
            font-size: 11px;
            font-weight: 900;
            color: #3b82f6;
            letter-spacing: -0.5px;
        }

        .webpos-footer-copy {
            font-size: 9px;
            color: #94a3b8;
        }

        /* Dark mode adjustments */
        .dark .webpos-body {
            background: #1e293b;
        }

        .dark .webpos-outlet-item {
            border-color: #334155;
        }

        .dark .webpos-outlet-item:hover {
            border-color: #3b82f6;
            background: rgba(59, 130, 246, 0.1);
        }

        .dark .webpos-outlet-icon {
            background: #0f172a;
        }

        .dark .webpos-outlet-item:hover .webpos-outlet-icon {
            background: #1e293b;
        }

        .dark .webpos-outlet-name {
            color: #f1f5f9;
        }

        .dark .webpos-footer {
            background: #0f172a;
            border-color: #1e293b;
        }

        .dark .webpos-empty-icon {
            background: #334155;
        }
    </style>

    <div class="webpos-wrapper">
        <div class="webpos-container">
            {{-- Header --}}
            <div class="webpos-header">
                <div class="webpos-welcome">
                    <div class="webpos-avatar">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div class="webpos-greeting">
                        <h2>Halo, {{ auth()->user()->name }}!</h2>
                        <p>Silahkan pilih outlet untuk melanjutkan</p>
                    </div>
                </div>
                <div class="webpos-clock">
                    <div class="webpos-clock-time" id="clock-time">00:00</div>
                    <div class="webpos-clock-date" id="clock-date">--/--/----</div>
                </div>
            </div>

            {{-- Body --}}
            <div class="webpos-body">
                @if($outlets->count() > 0)
                    <div class="webpos-outlet-list">
                        @foreach($outlets as $outlet)
                            <a href="{{ \App\Filament\Pages\Pos\CashierPage::getUrl() }}?outlet={{ $outlet->id }}" class="webpos-outlet-item">
                                <div class="webpos-outlet-icon">
                                    @if($outlet->image)
                                        <img src="{{ Storage::disk('public')->url($outlet->image) }}" alt="{{ $outlet->name }}">
                                    @else
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                    @endif
                                </div>
                                <div class="webpos-outlet-info">
                                    <p class="webpos-outlet-name">{{ $outlet->name }}</p>
                                    <p class="webpos-outlet-code">{{ $outlet->code }}</p>
                                </div>
                                <svg class="webpos-outlet-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="webpos-empty">
                        <div class="webpos-empty-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <p>Belum ada outlet aktif.<br>Silahkan buat outlet di pengaturan POS.</p>
                    </div>
                @endif
            </div>

            {{-- Footer --}}
            <div class="webpos-footer">
                <div class="webpos-footer-brand">
                    <span class="webpos-footer-label">Powered by</span>
                    <span class="webpos-footer-name">Delta Mas Tech</span>
                </div>
                <span class="webpos-footer-copy">&copy; {{ date('Y') }} PT. DELTA MAS TECH</span>
            </div>
        </div>
    </div>

    <script>
        function updateClock() {
            const now = new Date();
            const timeEl = document.getElementById('clock-time');
            const dateEl = document.getElementById('clock-date');

            if (timeEl) {
                timeEl.textContent = now.toLocaleTimeString('en-US', {
                    hour12: false,
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }

            if (dateEl) {
                dateEl.textContent = now.toLocaleDateString('en-GB', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric'
                });
            }
        }

        setInterval(updateClock, 1000);
        updateClock();
    </script>
</x-filament-panels::page>