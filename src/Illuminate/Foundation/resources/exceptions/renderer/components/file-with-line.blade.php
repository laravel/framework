@props(['frame'])

@php
    $file = $frame->file();
    $line = $frame->line();
@endphp

<div
    {{ $attributes->merge(['class' => 'truncate font-mono text-xs text-neutral-700 dark:text-neutral-300']) }}
    dir="rtl"
>
    <span data-tippy-content="{{ $file }}:{{ $line }}">
        @if (config('app.editor'))
            <a href="{{ $frame->editorHref() }}" @click.stop>
                <span class="hover:underline decoration-neutral-400">{{ $file }}</span><span class="text-neutral-500">:{{ $line }}</span>
            </a>
        @else
            {{ $file }}<span class="text-neutral-500">:{{ $line }}</span>
        @endif
    </span>
</div>
