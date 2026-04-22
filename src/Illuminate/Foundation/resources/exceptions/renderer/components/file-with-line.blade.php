@props(['frame', 'direction' => 'ltr'])

@php
    $file = $frame->file();
    $line = $frame->line();
@endphp

<div
    x-data="{
        copied: false,
        async copyToClipboard() {
            try {
                await window.copyToClipboard('{{ $file }}:{{ $line }}');
                this.copied = true;
                setTimeout(() => { this.copied = false }, 3000);
            } catch (err) {
                console.error('Failed to copy the file path: ', err);
            }
        }
    }"
    {{ $attributes->merge(['class' => 'flex items-center gap-1 font-mono text-xs text-neutral-500 dark:text-neutral-400']) }}
    dir="{{ $direction }}"
>
    <span class="truncate" data-tippy-content="{{ $file }}:{{ $line }}">
        @if (config('app.editor'))
            <a href="{{ $frame->editorHref() }}" @click.stop>
                <span class="hover:underline decoration-neutral-400">{{ $file }}</span><span class="text-neutral-500">:{{ $line }}</span>
            </a>
        @else
            {{ $file }}<span class="text-neutral-500">:{{ $line }}</span>
        @endif
    </span>
    <button
        x-cloak
        @click.stop="copyToClipboard()"
        @class([
            "flex-shrink-0 rounded w-5 h-5  flex items-center justify-center cursor-pointer transition-colors duration-200 ease-in-out",
        ])
    >
        <x-laravel-exceptions-renderer::icons.copy class="w-3 h-3 text-neutral-400" x-show="!copied" />
        <x-laravel-exceptions-renderer::icons.check class="w-3 h-3 text-emerald-500" x-show="copied" />
    </button>
</div>