<div class="flex items-center justify-center gap-2 max-w-max h-full">
    <div class="hidden lg:flex items-center gap-2">
        {{-- Jual --}}
        <a href="/admin/sales-overview"
            class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-gray-300 transition-all duration-150 shadow-sm">
            <svg class="w-4 h-4 flex-shrink-0" width="16" height="16" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <span>Jual</span>
        </a>

        {{-- Beli --}}
        <a href="/admin/purchase-overview"
            class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-gray-300 transition-all duration-150 shadow-sm">
            <svg class="w-4 h-4 flex-shrink-0" width="16" height="16" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
            </svg>
            <span>Beli</span>
        </a>

        {{-- Biaya --}}
        <a href="/admin/biaya"
            class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-gray-300 transition-all duration-150 shadow-sm">
            <svg class="w-4 h-4 flex-shrink-0" width="16" height="16" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>Biaya</span>
        </a>
    </div>

    {{-- Cari --}}
    <button type="button" x-data="{}" @click="$dispatch('open-global-search')"
        class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-gray-300 transition-all duration-150 shadow-sm">
        <svg class="w-4 h-4 flex-shrink-0" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <span>Cari</span>
    </button>
</div>

<style>
    /* Dark mode adjustments */
    .dark .inline-flex,
    .dark .md\:inline-flex {
        background-color: rgb(31 41 55) !important;
        border-color: rgb(55 65 81) !important;
        color: rgb(229 231 235) !important;
    }

    .dark .inline-flex:hover,
    .dark .md\:inline-flex:hover {
        background-color: rgb(55 65 81) !important;
        border-color: rgb(75 85 99) !important;
    }
</style>