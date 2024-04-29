@foreach ($exception->frames() as $frame)
    <div class="sm:col-span-2" x-show="index === {{ $loop->index }}">
        <div class="mb-3">
            <div class="text-md text-gray-500 dark:text-gray-400">
                <div class="mb-2">
                    <span class="text-gray-900 dark:text-gray-300 wrap">{{ $frame->file() }}</span>
                    <span class="font-mono text-xs">:{{ $frame->line() }}</span>
                </div>
            </div>
        </div>
        <div class="pt-4 text-sm text-gray-500 dark:text-gray-400">
            <pre x-data="{}"><template x-if="true"><code
                    id="frame-{{ $loop->index }}"
                    class="highlightable-code @if($loop->index === $exception->defaultFrame()) default-highlightable-code @endif scrollbar-hidden rounded-md dark:bg-gray-800 border dark:border-gray-700 overflow-y-hidden"
                    style="height: 32.5rem;"
                    data-line-number="{{ $frame->line() }}"
                    data-ln-start-from="{{ max($frame->line() - 5, 1) }}"
                >{{ $frame->snippet() }}</code></template></pre>
        </div>
    </div>
@endforeach
