@props(['exception'])

@if ($exception->hasPreviousExceptions())
    <div class="flex flex-col gap-2.5 bg-neutral-50 dark:bg-white/1 border border-neutral-200 dark:border-neutral-800 rounded-xl p-2.5 shadow-xs">
        <div class="flex items-center gap-2.5 p-2">
            <div class="bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-white/5 rounded-md w-6 h-6 flex items-center justify-center p-1">
                <x-laravel-exceptions-renderer::icons.alert class="w-2.5 h-2.5 text-amber-500 dark:text-amber-400" />
            </div>
            <h3 class="text-base font-semibold text-neutral-900 dark:text-white">Previous exceptions</h3>
        </div>

        <div class="flex flex-col gap-1.5">
            @foreach ($exception->previousExceptions() as $index => $previousException)
                <div class="bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-white/5 rounded-lg p-3 shadow-xs">
                    <div class="flex flex-col gap-2">
                        <div class="flex items-start gap-2">
                            <x-laravel-exceptions-renderer::badge type="warning" class="flex-shrink-0">
                                #{{ $index + 1 }}
                            </x-laravel-exceptions-renderer::badge>
                            <div class="flex flex-col gap-1 min-w-0 flex-1">
                                <p class="text-sm font-semibold text-neutral-900 dark:text-white font-mono break-words">
                                    {{ $previousException['class'] }}
                                </p>
                                @if ($previousException['message'])
                                    <p class="text-sm text-neutral-600 dark:text-neutral-400 break-words">
                                        {{ $previousException['message'] }}
                                    </p>
                                @endif
                                @if ($previousException['code'])
                                    <p class="text-xs text-neutral-500 dark:text-neutral-500 font-mono">
                                        Code: {{ $previousException['code'] }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
