@props(['frame'])

@php
    if ($class = $frame->class()) {
        $source = $class;

        if ($previous = $frame->previous()) {
            $source .= "->{$previous->callable()}()";
        }
    } else {
        $source = $frame->source();
    }
@endphp

<x-laravel-exceptions-renderer-new::tooltip side="left">
    <x-slot:trigger>
        <div
            {{ $attributes->merge(['class' => 'text-xs font-mono truncate text-violet-500 dark:text-violet-400']) }}
        >
            @if($class = $frame->class())
                {{ $class }}{{--
                --}}@if($frame->previous()){{--
                    --}}{{ '->' . $frame->previous()->callable() }}{{--
                --}}@endif{{--
                --}}<span class="text-orange-400 dark:text-orange-300 opacity-50">()</span>
            @else
                {{ $frame->source() }}
            @endif
        </div>
    </x-slot>

    <span>{{ $source }}</span>
</x-laravel-exceptions-renderer-new::tooltip>
