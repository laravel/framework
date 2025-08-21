@props(['frame', 'previousFrame'])

<div
    {{ $attributes->merge(['class' => 'text-sm font-mono text-neutral-400']) }}
>
    <span class="text-violet-500 dark:text-violet-400">{{ $frame->source() }}@if($previousFrame){{ '->' . $previousFrame->callable() }}@endif</span><!--
    --><span class="text-orange-400 dark:text-orange-300 opacity-50">()</span>
</div>
