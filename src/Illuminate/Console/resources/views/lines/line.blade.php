<div class="mx-2 mb-1 mt-{{ $marginTop }}">
    @if ($title)
        <span class="px-1 bg-{{ $bgColor }} text-{{ $fgColor }} uppercase">{{ $title }}</span>
    @endif
    <span class="@if ($title) ml-1 @endif">
        {{ $slot }}
    </span>
</div>
