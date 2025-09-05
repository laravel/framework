@props(['frame'])

<div class="flex h-11 items-center justify-between gap-6 px-3">
    <div class="flex-shrink-0">
        @if($frame->previous())
            <x-laravel-exceptions-renderer-new::formatted-source :$frame className="text-xs" />
        @else
            <span class="font-mono text-xs leading-3 text-neutral-500">
            Entrypoint
            </span>
        @endif
    </div>
    <div class="flex-1 min-w-0">
        <x-laravel-exceptions-renderer-new::file-with-line :$frame class="text-xs" />
    </div>
</div>
