@props(['file', 'line'])

<span
    {{ $attributes->merge(['class' => 'truncate font-mono text-xs text-neutral-700 dark:text-neutral-300']) }}
    dir="rtl"
>
    {{ $file }}<span class="text-neutral-500">:{{ $line }}</span>
</span>
