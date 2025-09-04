@props(['frame'])

<div
    class="rounded-lg border dark:border-white/10"
    x-data="{ expanded: {{ $frame->isMain() ? 'true' : 'false' }} }"
    @expand-button-clicked="expanded = $event.detail.expanded"
>
    <div class="flex h-11 items-center gap-3 bg-white pr-2.5 pl-4 dark:bg-white/3">
        {{-- Dot --}}
        <div class="flex size-3 items-center justify-center flex-shrink-0">
          <div class="size-2 rounded-full bg-neutral-400 dark:bg-neutral-400"></div>
        </div>

        <div class="flex flex-1 items-center justify-between gap-6 min-w-0">
            <div class="flex-shrink-0">
                <x-laravel-exceptions-renderer-new::formatted-source :$frame />
            </div>
            <div class="flex-1 min-w-0">
                <x-laravel-exceptions-renderer-new::file-with-line :file="$frame->file()" :line="$frame->line()" />
            </div>
        </div>

        <div class="flex-shrink-0">
            <x-laravel-exceptions-renderer-new::expand-button :expanded="$frame->isMain()" />
        </div>
    </div>

    @if($snippet = $frame->snippet())
        <x-laravel-exceptions-renderer-new::frame-code :code="$snippet" :highlightedLine="$frame->line()" x-show="expanded" />
    @endif
</div>
