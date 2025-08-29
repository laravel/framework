@props(['frame'])

<span
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
</span>
