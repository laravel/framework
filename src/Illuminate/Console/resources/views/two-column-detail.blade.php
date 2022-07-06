<div class="flex mx-2">
    <span>
        {{ $left }}
    </span>
    <span class="flex-1 content-repeat-[.] text-gray ml-1"></span>
    @if ($right !== '')
        <span class="ml-1">
            {{ $right }}
        </span>
    @endif
</div>
