@props(['frame'])

@php
    if ($class = $frame->class()) {
        $source = $class;

        if ($previous = $frame->previous()) {
            $source .= $previous->type();
            $source .= $previous->callable();
            $source .= '<span class="text-orange-400 dark:text-orange-300 opacity-50">(';
            $source .= implode(', ', $previous->args());
            $source .= ')</span>';
        }
    } else {
        $source = $frame->source();
    }
@endphp

<div
    {{ $attributes->merge(['class' => 'truncate font-mono text-xs text-violet-500 dark:text-violet-400']) }}
>
    <span data-tippy-content="{{ $source }}">{!! $source !!}</span>
</div>
