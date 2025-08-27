@props(['frame'])

<div
    {{ $attributes->merge(['class' => 'text-sm font-mono text-neutral-400']) }}
>
    <span class="text-violet-500 dark:text-violet-400">
        <span class="text-violet-500 dark:text-violet-400">
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
</div>
