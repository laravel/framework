@foreach ($exception->frames() as $frame)
    <div
        class="sm:col-span-2"
        x-show="index === {{ $loop->index }}"
    >
        <div class="mb-3">
            <div class="text-md text-gray-500 dark:text-gray-400">
                <div class="mb-2">
                    @if (config('app.editor'))
                        <a href="{{ $frame->editorHref() }}" class="text-blue-500 hover:underline">
                            <span class="wrap">{{ $frame->file() }}</span><span class="font-mono text-xs">:{{ $frame->line() }}</span>
                        </a>
                    @else
                        <span class="wrap text-gray-900 dark:text-gray-300">{{ $frame->file() }}</span><span class="font-mono text-xs">:{{ $frame->line() }}</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="pt-4 text-sm text-gray-500 dark:text-gray-400">
            <pre class="h-[32.5rem] rounded-md dark:bg-gray-800 border dark:border-gray-700"><template x-if="true"><code
                    style="display: none;"
                    id="frame-{{ $loop->index }}"
                    class="language-php highlightable-code @if($loop->index === $exception->defaultFrame()) default-highlightable-code @endif scrollbar-hidden overflow-y-hidden"
                    data-line-number="{{ $frame->line() }}"
                    data-ln-start-from="{{ max($frame->line() - 5, 1) }}"
                >{{ $frame->snippet() }}</code></template></pre>
        </div>
    </div>
@endforeach
