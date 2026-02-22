<div>
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="Pagination Navigation"
            style="display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; justify-content: space-between; flex: 1; align-items: center;">
                <div>
                    <p class="text-sm text-gray-700" style="font-size: 0.875rem; color: #374151; margin: 0;">
                        Showing
                        <span class="font-medium" style="font-weight: 500;">{{ $paginator->firstItem() }}</span>
                        to
                        <span class="font-medium" style="font-weight: 500;">{{ $paginator->lastItem() }}</span>
                        of
                        <span class="font-medium" style="font-weight: 500;">{{ $paginator->total() }}</span>
                        results
                    </p>
                </div>

                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="@lang('pagination.previous')"
                            style="display: flex; align-items: center; padding: 0.5rem 0.75rem; font-size: 0.875rem; font-weight: 500; color: #9ca3af; background-color: #fff; border: 1px solid #d1d5db; border-radius: 0.375rem; cursor: default;">
                            <svg style="width: 1rem; height: 1rem;" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                            </svg>
                        </span>
                    @else
                        <button type="button" wire:click="previousPage" wire:loading.attr="disabled" rel="prev"
                            aria-label="@lang('pagination.previous')"
                            style="display: flex; align-items: center; padding: 0.5rem 0.75rem; font-size: 0.875rem; font-weight: 500; color: #374151; background-color: #fff; border: 1px solid #d1d5db; border-radius: 0.375rem; cursor: pointer; transition: background-color 0.2s;"
                            onmouseover="this.style.backgroundColor='#f9fafb'" onmouseout="this.style.backgroundColor='#fff'">
                            <svg style="width: 1rem; height: 1rem;" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                            </svg>
                        </button>
                    @endif

                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        <button type="button" wire:click="nextPage" wire:loading.attr="disabled" rel="next"
                            aria-label="@lang('pagination.next')"
                            style="display: flex; align-items: center; padding: 0.5rem 0.75rem; font-size: 0.875rem; font-weight: 500; color: #374151; background-color: #fff; border: 1px solid #d1d5db; border-radius: 0.375rem; cursor: pointer; transition: background-color 0.2s;"
                            onmouseover="this.style.backgroundColor='#f9fafb'" onmouseout="this.style.backgroundColor='#fff'">
                            <svg style="width: 1rem; height: 1rem;" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </button>
                    @else
                        <span aria-disabled="true" aria-label="@lang('pagination.next')"
                            style="display: flex; align-items: center; padding: 0.5rem 0.75rem; font-size: 0.875rem; font-weight: 500; color: #9ca3af; background-color: #fff; border: 1px solid #d1d5db; border-radius: 0.375rem; cursor: default;">
                            <svg style="width: 1rem; height: 1rem;" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </span>
                    @endif
                </div>
            </div>
        </nav>
    @endif
</div>