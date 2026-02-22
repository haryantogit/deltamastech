@php
    $company = \App\Models\Company::first();
    $logoUrl = $company && $company->logo_path ? \Illuminate\Support\Facades\Storage::url($company->logo_path) : asset('images/logo.png');
    $brandName = $company?->name ?? 'Delta Mas Tech';
@endphp
<div class="flex flex-row items-center gap-x-4 transition-all duration-300"
    style="display: flex !important; flex-direction: row !important; align-items: center !important; flex-wrap: nowrap !important;">
    {{-- Logo --}}
    <img src="{{ $logoUrl }}" alt="Logo" class="h-8 w-auto flex-shrink-0 object-contain"
        style="height: 2rem !important; width: auto !important; max-width: none !important; flex-shrink: 0 !important;">

    {{-- Brand Name (Hidden when collapsed) --}}
    <span x-show="$store.sidebar.isOpen" x-cloak x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-x-2" x-transition:enter-end="opacity-100 translate-x-0"
        class="text-xs font-semibold leading-none tracking-wide text-gray-950 dark:text-white whitespace-nowrap shrink-0">
        {{ $brandName }}
    </span>
</div>