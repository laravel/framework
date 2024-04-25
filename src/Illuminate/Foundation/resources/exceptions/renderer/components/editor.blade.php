@foreach ($exception->frames() as $frame)
    <div class="overflow-hidden sm:col-span-2" x-show="index === {{ $loop->index }}">
        <div class="mb-5">
            <div class="text-xs text-gray-500 dark:text-gray-400">
                <div class="mb-2">
                    <span class="text-gray-900 dark:text-gray-300">{{ $frame->file() }} :{{ $frame->line() }}</span>
                </div>
            </div>
        </div>
        <div class="h-full py-4 rounded-md text-sm text-gray-500 dark:text-gray-400 dark:bg-gray-800 border border-gray-200 dark:border-none">
            <pre id="frame-{{ $loop->index }}"><code
                    class="language-php"
                    data-line-number="{{ $frame->line() }}"
                    data-ln-start-from="{{ max($frame->line() - 11, 1) }}"
                >{{ $frame->snippet() }}</code>
            </pre>
        </div>
    </div>
@endforeach
