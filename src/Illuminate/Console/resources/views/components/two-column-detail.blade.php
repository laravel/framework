<div class="flex mx-2">
    <span>
        {{ $first }}
    </span>
    <span class="flex-1 content-repeat-[.] text-gray ml-1"></span>
    @if ($second !== '')
        <span class="ml-1">
            {{ $second }}
        </span>
    @endif
</div>
