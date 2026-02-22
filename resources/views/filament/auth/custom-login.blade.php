<div class="flex min-h-screen bg-white">
    <!-- Left Side: Login Form -->
    <div class="flex flex-col justify-center w-full px-4 py-12 lg:w-1/2 sm:px-6 lg:px-20 xl:px-24">
        <div class="w-full max-w-sm mx-auto lg:w-96">
            @php
                $company = \App\Models\Company::first();
                $logoUrl = $company && $company->logo_path ? \Illuminate\Support\Facades\Storage::url($company->logo_path) : asset('images/logo.png');
                $brandName = $company?->name ?? 'Delta Mas Tech';
            @endphp
            <!-- Branding -->
            <div class="flex flex-col items-center justify-center gap-3 mb-10 text-center">
                <img src="{{ $logoUrl }}" alt="{{ $brandName }}" class="h-16 w-auto">
                <span class="text-3xl font-bold tracking-tight text-gray-900">
                    {{ $brandName }}
                </span>
            </div>

            <form wire:submit="authenticate" class="space-y-6">

                <!-- Email Input -->
                <div class="space-y-2">
                    <input type="email" id="email" wire:model="data.email"
                        class="block w-full px-4 py-3.5 text-gray-900 placeholder-gray-500 bg-gray-50 border border-gray-100 rounded-xl focus:bg-white focus:border-blue-500 focus:ring-blue-500 sm:text-sm transition-colors duration-200"
                        placeholder="Email" required autofocus />
                    @error('data.email')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password Input -->
                <div class="space-y-2">
                    <input type="password" id="password" wire:model="data.password"
                        class="block w-full px-4 py-3.5 text-gray-900 placeholder-gray-500 bg-gray-50 border border-gray-100 rounded-xl focus:bg-white focus:border-blue-500 focus:ring-blue-500 sm:text-sm transition-colors duration-200"
                        placeholder="Password" required />
                    @error('data.password')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                    class="flex justify-center w-full px-4 py-3 text-sm font-bold text-white transition-all duration-200 bg-blue-600 border border-transparent rounded-xl shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <div class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor" class="size-5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                        +Sign In
                    </div>
                </button>

                <div class="flex items-center justify-between">
                    <!-- Remember Me -->
                    <div class="flex items-center">
                        <input id="remember" type="checkbox" wire:model="data.remember"
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <label for="remember" class="ml-2 text-sm text-gray-600">Remember me</label>
                    </div>

                    <!-- Forgot Password -->
                    <div class="text-sm">
                        <a href="#" class="font-medium text-blue-600 hover:text-blue-500">
                            Forgot password?
                        </a>
                    </div>
                </div>
            </form>


        </div>
    </div>

    <!-- Right Side: Decorative/Illustration -->
    <div class="hidden lg:block relative w-0 flex-1 overflow-hidden bg-white p-12">
        <img src="{{ asset('images/login-illustration.jpg') }}" alt="Login Visual"
            class="absolute inset-0 w-full h-full object-cover rounded-3xl p-6">
    </div>
</div>