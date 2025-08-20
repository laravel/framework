@props(['frame', 'previousFrame'])

<div class="flex items-center justify-between gap-2.5 p-2">
    <div class="text-sm font-mono text-neutral-400">
        {{ $frame->source() }}@if($previousFrame){{ '->' . $previousFrame->callable() }}@endif
    </div>
    <div class="text-xs font-mono text-neutral-400">
        {{ $frame->file() }}:{{ $frame->line() }}
    </div>
</div>
