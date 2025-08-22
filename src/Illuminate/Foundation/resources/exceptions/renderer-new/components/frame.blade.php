@props(['frame'])

<div class="overflow-hidden rounded-lg border dark:border-white/10">
    <div class="flex h-11 items-center gap-2.5 bg-white pr-2.5 pl-4 dark:bg-white/3">
        {{-- Dot --}}
        <div class="flex size-3 items-center justify-center">
          <div class="size-2 rounded-full bg-neutral-400 dark:bg-neutral-400"></div>
        </div>

        <div class="flex flex-1 items-center justify-between gap-6 overflow-hidden">
            <x-laravel-exceptions-renderer-new::formatted-source :$frame />
            <x-laravel-exceptions-renderer-new::file-with-line :file="$frame->file()" :line="$frame->line()" />
        </div>
    </div>

    @if($snippet = $frame->snippet())
        <x-laravel-exceptions-renderer-new::frame-code :code="$snippet" :highlightedLine="$frame->line()" />
    @endif
</div>
