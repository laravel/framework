@props(['frame'])

<div class="flex h-11 items-center justify-between gap-6 px-3">
    @if($frame->previous())
        <x-laravel-exceptions-renderer-new::formatted-source :$frame className="text-xs" />
    @else
        <span class="font-mono text-xs leading-3 text-neutral-500">
          Entrypoint
        </span>
    @endif

    <x-laravel-exceptions-renderer-new::file-with-line :file="$frame->file()" :line="$frame->line()" class="text-xs" />
</div>
