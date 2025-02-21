@if ($paginator->hasPages())
    <div class="ui equal width grid">
        {{-- Previous Page Link --}}
        <div class="left floated column">
            @if ($paginator->onFirstPage())
                <a class="ui disabled button" aria-disabled="true"><span>@lang('pagination.previous')</span></a>
            @else
                <a class="ui button"
                   href="{{ $paginator->previousPageUrl() }}"
                   rel="prev"><i class="left chevron icon"></i>@lang('pagination.previous')</a>
            @endif
        </div>

        {{-- Next Page Link --}}
        <div class="right floated right aligned column">
            @if ($paginator->hasMorePages())
                <a class="ui button"
                   href="{{ $paginator->nextPageUrl() }}"
                   rel="next">@lang('pagination.next')<i class="right chevron icon"></i></a>
            @else
                <a class="ui disabled button" aria-disabled="true"><span>@lang('pagination.next')</span></a>
            @endif
        </div>
    </div>
@endif
