@if ($paginator->hasPages())
    <div class="ui pagination menu">
        {{-- First Page Link --}}
        @if ($paginator->onFirstPage())
            <a class="icon item disabled"> <i class="left double angle icon"></i> </a>
        @else
            <a class="icon item" href="{{ $paginator->url(1) }}" rel="first"> <i class="left double angle icon"></i> </a>
        @endif

        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <a class="icon item disabled"> <i class="left angle icon"></i> </a>
        @else
            <a class="icon item" href="{{ $paginator->previousPageUrl() }}" rel="prev"> <i class="left angle icon"></i> </a>
        @endif

        {{-- Pagination Elements --}}
        @foreach ($elements as $element)
            {{-- "Three Dots" Separator --}}
            @if (is_string($element))
                <a class="icon item disabled">{{ $element }}</a>
            @endif

            {{-- Array Of Links --}}
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <a class="item active" href="{{ $url }}">{{ $page }}</a>
                    @else
                        <a class="item" href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <a class="icon item" href="{{ $paginator->nextPageUrl() }}" rel="next"> <i class="right angle icon"></i> </a>
        @else
            <a class="icon item disabled"> <i class="right angle icon"></i> </a>
        @endif

        {{-- Last Page Link --}}
        @if ($paginator->hasMorePages())
            <a class="icon item" href="{{ $paginator->url($paginator->lastPage()) }}" rel="last"> <i class="right double angle icon"></i> </a>
        @else
            <a class="icon item disabled"> <i class="right double angle icon"></i> </a>
        @endif
    </div>
@endif
