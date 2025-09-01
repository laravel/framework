@props(['file', 'line'])

<x-laravel-exceptions-renderer-new::tooltip side="right">
    <x-slot:trigger>
        <div
            {{ $attributes->merge(['class' => 'truncate font-mono text-xs text-neutral-700 dark:text-neutral-300']) }}
            dir="rtl"
        >
            {{ $file }}<span class="text-neutral-500">:{{ $line }}</span>
        </div>
    </x-slot>

    <span>
        {{ $file }}<span class="text-neutral-500">:{{ $line }}</span>
    </span>
</x-laravel-exceptions-renderer-new::tooltip>
