@if ($paginator->hasPages())
    <div class="ui pagination menu" role="navigation">
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <a class="icon item disabled"
               aria-disabled="true"
               aria-label="@lang('pagination.previous')">
                @lang('pagination.previous')
            </a>
        @else
            <a class="icon item"
               href="{{ $paginator->previousPageUrl() }}"
               aria-label="@lang('pagination.previous')"
               rel="prev">
                @lang('pagination.previous')
            </a>
        @endif

        {{-- Pagination Elements --}}
        @foreach ($elements as $element)
            {{-- "Three Dots" Separator --}}
            @if (is_string($element))
                <a class="icon item disabled" aria-disabled="true">{{ $element }}</a>
            @endif

            {{-- Array Of Links --}}
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <a class="item active"
                           href="{{ $url }}"
                           aria-current="page">{{ $page }}</a>
                    @else
                        <a class="item" href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <a class="icon item"
               href="{{ $paginator->nextPageUrl() }}"
               aria-label="@lang('pagination.next')"
               rel="next">
                @lang('pagination.next')
            </a>
        @else
            <a class="icon item disabled"
               aria-disabled="true"
               aria-label="@lang('pagination.next')">
                @lang('pagination.next')
            </a>
        @endif
    </div>
@endif
