<div class="fixed inset-0 z-[9999]">
    @php
        $company = \App\Models\Company::first();
        $logoUrl = $company && $company->logo_path ? \Illuminate\Support\Facades\Storage::url($company->logo_path) : asset('images/logo.png');
        $bgUrl = $company && $company->login_background_path ? \Illuminate\Support\Facades\Storage::url($company->login_background_path) : asset('images/login-bg.jpg');
        $brandName = $company?->name ?? 'Delta Mas Tech';
    @endphp

    <style>
        @keyframes subtle-zoom {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }
        }

        .animate-subtle-zoom {
            animation: subtle-zoom 30s ease-in-out infinite;
        }

        @keyframes fade-in-up {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in-up {
            animation: fade-in-up 0.8s ease-out forwards;
        }

        /* Glass input autofill fix */
        input:-webkit-autofill,
        input:-webkit-autofill:hover,
        input:-webkit-autofill:focus {
            -webkit-text-fill-color: white !important;
            -webkit-box-shadow: 0 0 0px 1000px rgba(255, 255, 255, 0.15) inset !important;
            transition: background-color 5000s ease-in-out 0s;
        }
    </style>

    <!-- Full Screen Background Image -->
    <div class="absolute inset-0 overflow-hidden">
        <img src="{{ $bgUrl }}" alt="Background" class="w-full h-full object-cover animate-subtle-zoom">
        <!-- Dark Overlay for contrast -->
        <div class="absolute inset-0 bg-black/40"></div>
    </div>

    <!-- Centered Glass Login Card -->
    <div class="relative z-10 flex items-center justify-center w-full h-full px-4">
        <div class="w-full max-w-[420px] animate-fade-in-up">

            <!-- Glass Card -->
            <div class="bg-white/10 backdrop-blur-2xl rounded-3xl shadow-2xl p-8 sm:p-10 border border-white/20"
                style="box-shadow: 0 8px 32px rgba(0,0,0,0.3), inset 0 1px 0 rgba(255,255,255,0.1);">

                <!-- Logo & Brand -->
                <div class="text-center mb-8">
                    <div class="flex flex-col items-center gap-3">
                        <div
                            class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center p-2 border border-white/20">
                            <img src="{{ $logoUrl }}" alt="{{ $brandName }}"
                                class="h-full w-full object-contain drop-shadow-lg">
                        </div>
                        <span class="text-xl font-bold tracking-tight text-white drop-shadow-md">{{ $brandName }}</span>
                    </div>
                </div>

                <form wire:submit="authenticate" class="space-y-5">

                    <!-- Email Input -->
                    <div class="space-y-1">
                        <div class="relative group">
                            <div
                                class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-colors group-focus-within:text-white">
                                <svg class="h-5 w-5 text-white/50" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                </svg>
                            </div>
                            <input type="email" id="email" wire:model="data.email"
                                class="block w-full pl-12 pr-4 py-3.5 bg-white/10 border border-white/20 rounded-xl focus:ring-2 focus:ring-white/30 focus:border-white/40 focus:bg-white/15 text-sm text-white placeholder-white/50 transition-all outline-none backdrop-blur-sm"
                                placeholder="Email Address" required autofocus />
                        </div>
                        @error('data.email')
                            <p class="pl-4 text-xs font-semibold text-red-300 italic">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password Input -->
                    <div class="space-y-1">
                        <div class="relative group">
                            <div
                                class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-colors group-focus-within:text-white">
                                <svg class="h-5 w-5 text-white/50" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                </svg>
                            </div>
                            <input type="password" id="password" wire:model="data.password"
                                class="block w-full pl-12 pr-4 py-3.5 bg-white/10 border border-white/20 rounded-xl focus:ring-2 focus:ring-white/30 focus:border-white/40 focus:bg-white/15 text-sm text-white placeholder-white/50 transition-all outline-none backdrop-blur-sm"
                                placeholder="Password" required />
                        </div>
                        @error('data.password')
                            <p class="pl-4 text-xs font-semibold text-red-300 italic">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Login Button -->
                    <div class="pt-2">
                        <button type="submit"
                            class="w-full flex justify-center py-3.5 px-4 rounded-xl text-sm font-bold text-gray-900 bg-white/80 hover:bg-white backdrop-blur-sm shadow-lg hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-white/50 transition-all duration-300 active:scale-[0.98]">
                            Login
                        </button>
                    </div>

                    <!-- Lupa Password -->
                    <div class="flex items-center justify-center pt-1">
                        @php
                            $waNumber = preg_replace('/[^0-9]/', '', $company?->phone ?? '');
                            if (str_starts_with($waNumber, '0')) {
                                $waNumber = '62' . substr($waNumber, 1);
                            }
                            $waNumber = $waNumber ?: '6281234567890';
                        @endphp
                        <a href="https://wa.me/{{ $waNumber }}?text={{ urlencode('Halo, saya lupa password akun saya. Mohon bantuan reset password.') }}"
                            target="_blank"
                            class="text-xs font-medium text-white/50 hover:text-white transition-colors tracking-wide">
                            Lupa Password?
                        </a>
                    </div>

                    <!-- Hidden Remember Me -->
                    <input id="remember" type="checkbox" wire:model="data.remember" class="hidden">
                </form>
            </div>

        </div>
    </div>
</div>