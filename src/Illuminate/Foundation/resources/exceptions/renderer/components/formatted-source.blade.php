@props(['frame'])

@php
    if ($class = $frame->class()) {
        $source = $class;

        if ($previous = $frame->previous()) {
            $source .= $previous->operator();
            $source .= $previous->callable();
            $source .= '('.implode(', ', $previous->args()).')';
        }
    } else {
        $source = $frame->source();
    }
@endphp

<x-laravel-exceptions-renderer::syntax-highlight
    :code="$source"
    grammar="php"
    truncate
    class="text-xs min-w-0"
    data-tippy-content="{{ $source }}"
/>
