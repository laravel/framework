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

<div
    {{ $attributes->merge(['class' => 'truncate font-mono text-xs text-violet-500 dark:text-violet-400']) }}
>
    <span data-tippy-content="{{ $source }}">
        @if ($class = $frame->class())
            {{ $class }}{{--
            --}}@if($frame->previous()){{--
                --}}{{ '->' . $frame->previous()->callable() }}{{--
            --}}@endif{{--
            --}}<span class="text-orange-400 dark:text-orange-300 opacity-50">()</span>
        @else
            {{ $frame->source() }}
        @endif
    </span>
</div>
