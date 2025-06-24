@if ($paginator->hasPages())
    <ul
        class="pagination mt-4"
        data-role="pagination"
        data-show-previous="true"
        data-show-next="true"
        data-show-first="false"
        data-show-last="false"
        data-show-items="false"
        data-size="medium"
        data-cls-active="active"
        data-cls-disabled="disabled"
        data-cls-link="page-link"
    >
        {{-- Previous Page --}}
        @if ($paginator->onFirstPage())
            <li class="disabled">
                <span class="page-link">«</span>
            </li>
        @else
            <li>
                <a href="{{ $paginator->previousPageUrl() }}" class="page-link" rel="prev">«</a>
            </li>
        @endif

        {{-- Pagination Elements --}}
        @foreach ($elements as $element)
            {{-- Dots separator --}}
            @if (is_string($element))
                <li class="disabled"><span class="page-link">{{ $element }}</span></li>
            @endif

            {{-- Page links --}}
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <li class="active"><span class="page-link">{{ $page }}</span></li>
                    @else
                        <li><a href="{{ $url }}" class="page-link">{{ $page }}</a></li>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next Page --}}
        @if ($paginator->hasMorePages())
            <li>
                <a href="{{ $paginator->nextPageUrl() }}" class="page-link" rel="next">»</a>
            </li>
        @else
            <li class="disabled"><span class="page-link">»</span></li>
        @endif
    </ul>
@endif
