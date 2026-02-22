<x-filament-panels::page>
    @php
        $company = \App\Models\Company::first();
        $logoUrl = $company && $company->logo_path ? \Illuminate\Support\Facades\Storage::url($company->logo_path) : asset('images/logo.png');
    @endphp
    <div style="
        min-height: 80vh; 
        display: flex; 
        flex-direction: column; 
        align-items: center; 
        justify-content: center; 
        position: relative; 
        overflow: hidden; 
        padding: 2rem; 
        font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    ">

        {{-- Animated Background Gradients --}}
        <div style="position: absolute; inset: 0; pointer-events: none; z-index: 0;">
            <div
                style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(to bottom, #f9fafb, #ffffff);">
            </div>
            {{-- Dark mode support via media query style block below --}}

            <div class="blob-animation"
                style="position: absolute; top: -5rem; left: -5rem; width: 24rem; height: 24rem; background-color: rgba(249, 115, 22, 0.15); border-radius: 9999px; filter: blur(64px);">
            </div>
            <div class="blob-animation delay-2000"
                style="position: absolute; top: 50%; right: -5rem; width: 24rem; height: 24rem; background-color: rgba(99, 102, 241, 0.15); border-radius: 9999px; filter: blur(64px);">
            </div>
            <div class="blob-animation delay-4000"
                style="position: absolute; bottom: -5rem; left: 50%; width: 24rem; height: 24rem; background-color: rgba(168, 85, 247, 0.15); border-radius: 9999px; filter: blur(64px); transform: translateX(-50%);">
            </div>
        </div>

        {{-- Main Floating Card --}}
        <div style="
            position: relative; 
            width: 100%; 
            max-width: 42rem; 
            background-color: rgba(255, 255, 255, 0.8); 
            backdrop-filter: blur(16px); 
            -webkit-backdrop-filter: blur(16px); 
            border-radius: 1.5rem; 
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15); 
            border: 1px solid rgba(255, 255, 255, 0.5); 
            padding: 3.5rem; 
            text-align: center; 
            z-index: 10;
        ">

            {{-- Top Highlight --}}
            <div
                style="position: absolute; top: 0; left: 0; width: 100%; height: 6px; background: linear-gradient(to right, #f97316, #6366f1, #f97316); border-top-left-radius: 1.5rem; border-top-right-radius: 1.5rem;">
            </div>

            {{-- Logo Area --}}
            <div style="margin-bottom: 2.5rem; display: flex; justify-content: center;">
                <div style="
                    padding: 1rem; 
                    background-color: white; 
                    border-radius: 1rem; 
                    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.025); 
                    border: 1px solid rgba(243, 244, 246, 1);
                ">
                    <img src="{{ $logoUrl }}" alt="Logo"
                        style="height: 3rem; width: auto; max-width: 160px; object-fit: contain;"
                        onerror="this.src='{{ asset('images/logo.png') }}'; this.onerror=null;">
                </div>
            </div>

            {{-- Status Pill --}}
            <div style="
                display: inline-flex; 
                align-items: center; 
                gap: 0.5rem; 
                padding: 0.375rem 1rem; 
                border-radius: 9999px; 
                background-color: #fff7ed; 
                border: 1px solid #ffedd5; 
                color: #c2410c; 
                margin-bottom: 2rem; 
                margin-left: auto; 
                margin-right: auto;
            ">
                <span style="position: relative; display: flex; height: 10px; width: 10px;">
                    <span
                        style="animation: ping 1s cubic-bezier(0, 0, 0.2, 1) infinite; position: absolute; display: inline-flex; height: 100%; width: 100%; border-radius: 50%; background-color: #fb923c; opacity: 0.75;"></span>
                    <span
                        style="position: relative; display: inline-flex; border-radius: 50%; height: 10px; width: 10px; background-color: #f97316;"></span>
                </span>
                <span
                    style="font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em;">Sedang
                    Dikembangkan</span>
            </div>

            {{-- Headline --}}
            <h1 style="
                font-size: 2.25rem; 
                font-weight: 800; 
                margin-bottom: 1.5rem; 
                line-height: 1.1; 
                letter-spacing: -0.025em;
                background: linear-gradient(to bottom right, #111827, #374151); 
                -webkit-background-clip: text; 
                -webkit-text-fill-color: transparent;
                color: #111827; /* Fallback */
            ">
                Fitur <span style="color: #ea580c; -webkit-text-fill-color: #ea580c;">{{ $feature }}</span>
            </h1>

            <p style="
                font-size: 1.125rem; 
                color: #4b5563; 
                margin-bottom: 2.5rem; 
                max-width: 32rem; 
                margin-left: auto; 
                margin-right: auto; 
                line-height: 1.625;
            ">
                Kami sedang merobak baris kode terakhir untuk fitur ini. Tunggu sebentar lagi untuk pengalaman yang
                lebih baik.
            </p>

            {{-- Progress Indicator --}}
            <div style="width: 100%; max-width: 28rem; margin-left: auto; margin-right: auto; margin-bottom: 3rem;">
                <div
                    style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 0.75rem;">
                    <span
                        style="font-size: 0.75rem; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">Coming
                        Soon</span>
                    <span style="font-size: 0.875rem; font-weight: 900; color: #ea580c;">85%</span>
                </div>
                <div
                    style="height: 0.75rem; width: 100%; background-color: #f3f4f6; border-radius: 9999px; overflow: hidden; box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.06);">
                    <div style="
                        height: 100%; 
                        width: 85%; 
                        border-radius: 9999px; 
                        position: relative; 
                        overflow: hidden; 
                        background: linear-gradient(to right, #f97316, #6366f1);
                    ">
                        <div style="
                            position: absolute; 
                            inset: 0; 
                            background-color: rgba(255, 255, 255, 0.3); 
                            width: 100%; 
                            height: 100%; 
                            transform: skewX(-12deg); 
                            animation: shimmer 2s infinite linear;
                        "></div>
                    </div>
                </div>
            </div>

            {{-- Action Button --}}
            <a href="javascript:history.back()" style="
                display: inline-block;
                background-color: #111827; 
                color: white; 
                font-weight: 700; 
                padding: 0.75rem 2rem; 
                border-radius: 0.75rem; 
                text-decoration: none; 
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); 
                transition: transform 0.2s, background-color 0.2s;
            " onmouseover="this.style.backgroundColor='#000000'; this.style.transform='translateY(-2px)'"
                onmouseout="this.style.backgroundColor='#111827'; this.style.transform='translateY(0)'">
                &larr; Kembali Dashboard
            </a>

            {{-- Background Decoration --}}
            <div style="
                position: absolute; 
                bottom: -2.5rem; 
                right: -2.5rem; 
                font-size: 8rem; 
                font-weight: 900; 
                color: rgba(17, 24, 39, 0.03); 
                pointer-events: none; 
                user-select: none; 
                z-index: 0;
            ">
                DELTA
            </div>
        </div>
    </div>

    <style>
        @keyframes blob {
            0% {
                transform: translate(0px, 0px) scale(1);
            }

            33% {
                transform: translate(30px, -50px) scale(1.1);
            }

            66% {
                transform: translate(-20px, 20px) scale(0.9);
            }

            100% {
                transform: translate(0px, 0px) scale(1);
            }
        }

        .blob-animation {
            animation: blob 7s infinite;
        }

        .delay-2000 {
            animation-delay: 2s;
        }

        .delay-4000 {
            animation-delay: 4s;
        }

        @keyframes ping {

            75%,
            100% {
                transform: scale(2);
                opacity: 0;
            }
        }

        @keyframes shimmer {
            0% {
                transform: translateX(-100%) skewX(-12deg);
            }

            100% {
                transform: translateX(200%) skewX(-12deg);
            }
        }

        /* Dark mode overrides if filament adds .dark class to html or body */
        .dark .bg-gradient-to-b {
            background: linear-gradient(to bottom, #111827, #1f2937) !important;
        }

        .dark .blob-animation {
            opacity: 0.2;
        }
    </style>
</x-filament-panels::page>