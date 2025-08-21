@props(['file', 'line'])

<div
    {{ $attributes->merge(['class' => 'truncate font-mono text-xs text-neutral-700 dark:text-neutral-300']) }}
>
    {{ $file }}<span class="text-neutral-500">:{{ $line }}</span>
</div>
