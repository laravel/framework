@props(['frame', 'previousFrame'])

<div class="flex items-center justify-between gap-2.5 p-2">
    <x-laravel-exceptions-renderer-new::formatted-source :$frame :$previousFrame />
    <x-laravel-exceptions-renderer-new::file-with-line :file="$frame->file()" :line="$frame->line()" />
</div>
