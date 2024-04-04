@foreach ($exception->frames() as $frame)
    <div class="overflow-hidden sm:col-span-2" x-show="index === {{ $loop->index }}">
        <div>
            <div class="text-xs text-gray-500 dark:text-gray-400">
                <div class="mb-2">
                    <span class="text-gray-900 dark:text-gray-300">{{ $frame->source() }}</span>
                    <span class="italic text-gray-500 dark:text-gray-400">:{{ $frame->line() }}</span>
                </div>
            </div>
        </div>
        <div class="ml-2 text-xs text-gray-500 dark:text-gray-400">
            <pre id="frame-{{ $loop->index }}"><code
                    class="language-php"
                    data-line-number="{{ $frame->line() }}"
                    data-ln-start-from="{{ max($frame->line() - 11, 1) }}"
                >{{ $frame->snippet() }}</code></pre>
        </div>
    </div>
@endforeach
