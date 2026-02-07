@props(['exception'])

<div class="flex flex-col gap-2.5 bg-neutral-50 dark:bg-white/1 border border-neutral-200 dark:border-neutral-800 rounded-xl p-2.5 shadow-xs">
    <div class="flex items-center gap-2.5 p-2">
        <div class="bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-white/5 rounded-md w-6 h-6 flex items-center justify-center p-1">
            <x-laravel-exceptions-renderer::icons.alert class="w-2.5 h-2.5 text-blue-500 dark:text-emerald-500" />
        </div>
        <h3 class="text-base font-semibold text-neutral-900 dark:text-white">Previous exceptions</h3>
    </div>

    <div class="flex flex-col gap-1.5">
        @forelse ($exception->previous() as $index => $previous)
            <div class="flex flex-col items-start gap-4 p-4 bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-white/5 rounded-lg p-3 shadow-xs overflow-x-auto">
                <div class="flex flex-col gap-2">
                    <div class="flex gap-2 items-center">
                        <x-laravel-exceptions-renderer::badge type="warning">
                            #{{ $index + 1 }}
                        </x-laravel-exceptions-renderer::badge>
                        <h4 class="text-sm text-neutral-900 dark:text-white break-words">
                            {{ $previous->class() }}
                        </h4>
                    </div>
                    <x-laravel-exceptions-renderer::file-with-line :frame="$previous->frames()->first()" class="text-xs font-mono" />
                </div>
                <div class="flex flex-col gap-1 min-w-0 flex-1">
                    @if ($previous->code())
                        <p class="text-xs text-neutral-500 dark:text-neutral-500 font-mono">
                            Code: {{ $previous->code() }}
                        </p>
                    @endif
                    @if ($previous->message())
                        <p class="text-sm text-neutral-600 dark:text-neutral-400 break-words">
                            {{ $previous->message() }}
                        </p>
                    @endif
                </div>
            </div>
        @empty
            <x-laravel-exceptions-renderer::empty-state message="No previous exceptions" />
        @endforelse
    </div>
</div>
