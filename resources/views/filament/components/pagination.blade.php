@props([
    'currentPageOptionProperty' => 'tableRecordsPerPage',
    'extremeLinks' => false,
    'paginator',
    'pageOptions' => [],
])

@php
    use Illuminate\Contracts\Pagination\CursorPaginator;

    $isRtl = __('filament-panels::layout.direction') === 'rtl';
    $isSimple = ! $paginator instanceof \Illuminate\Pagination\LengthAwarePaginator;
@endphp

<nav
    aria-label="{{ __('filament::components/pagination.label') }}"
    role="navigation"
    {{ $attributes->class(['fi-ta-pagination']) }}
>
    {{-- Left Side: Per Page Select --}}
    @if (count($pageOptions) > 1)
        <div class="fi-ta-pagination-records-per-page-ctn flex items-center gap-3">
            <label class="fi-ta-pagination-records-per-page-label text-sm font-medium text-gray-700">
                {{ __('filament::components/pagination.fields.records_per_page.label') }}
            </label>

            <x-filament::input.wrapper class="fi-ta-pagination-records-per-page-select-wrapper">
                <x-filament::input.select
                    :wire:model.live="$currentPageOptionProperty"
                    class="fi-ta-pagination-records-per-page-select"
                >
                    @foreach ($pageOptions as $option)
                        <option value="{{ $option }}">
                            {{ $option === 'all' ? __('filament::components/pagination.fields.records_per_page.options.all') : $option }}
                        </option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </div>
    @else
        <div></div>
    @endif

    {{-- Right Side: Pagination Links --}}
    <div class="flex items-center gap-4">
        @if (! $isSimple)
            <span class="fi-ta-pagination-overview text-sm text-gray-600 hidden md:inline">
                {{
                    trans_choice(
                        'filament::components/pagination.overview',
                        $paginator->total(),
                        [
                            'first' => \Illuminate\Support\Number::format($paginator->firstItem() ?? 0),
                            'last' => \Illuminate\Support\Number::format($paginator->lastItem() ?? 0),
                            'total' => \Illuminate\Support\Number::format($paginator->total()),
                        ],
                    )
                }}
            </span>
        @endif

        @if ($paginator->hasPages())
            <ol class="fi-ta-pagination-list flex items-center gap-1">
                @if (! $paginator->onFirstPage())
                    {{-- Previous Page --}}
                    <li class="fi-pagination-item">
                        <button
                            type="button"
                            rel="prev"
                            wire:click="previousPage('{{ $paginator->getPageName() }}')"
                            wire:key="{{ $this->getId() }}.pagination.previous"
                            class="fi-pagination-link"
                            title="{{ __('filament::components/pagination.actions.previous.label') }}"
                        >
                            <x-heroicon-m-chevron-left class="w-4 h-4" />
                        </button>
                    </li>
                @endif

                @if (! $isSimple)
                    @foreach ($paginator->render()->offsetGet('elements') as $element)
                        @if (is_string($element))
                            <li class="fi-pagination-item">
                                <span class="fi-pagination-link fi-disabled">{{ $element }}</span>
                            </li>
                        @endif

                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                <li class="fi-pagination-item">
                                    <button
                                        type="button"
                                        wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                                        wire:key="{{ $this->getId() }}.pagination.{{ $paginator->getPageName() }}.{{ $page }}"
                                        @class([
                                            'fi-pagination-link',
                                            'fi-active' => $page === $paginator->currentPage(),
                                        ])
                                        aria-label="{{ trans_choice('filament::components/pagination.actions.go_to_page.label', $page, ['page' => \Illuminate\Support\Number::format($page)]) }}"
                                        @if($page === $paginator->currentPage()) aria-current="page" @endif
                                    >
                                        {{ \Illuminate\Support\Number::format($page) }}
                                    </button>
                                </li>
                            @endforeach
                        @endif
                    @endforeach
                @endif

                @if ($paginator->hasMorePages())
                    {{-- Next Page --}}
                    <li class="fi-pagination-item">
                        <button
                            type="button"
                            rel="next"
                            wire:click="nextPage('{{ $paginator->getPageName() }}')"
                            wire:key="{{ $this->getId() }}.pagination.next"
                            class="fi-pagination-link"
                            title="{{ __('filament::components/pagination.actions.next.label') }}"
                        >
                            <x-heroicon-m-chevron-right class="w-4 h-4" />
                        </button>
                    </li>
                @endif
            </ol>
        @endif
    </div>
</nav>
