@props(['exception'])

<div class="flex flex-col gap-2.5 bg-neutral-50 dark:bg-white/1 border border-neutral-200 dark:border-neutral-800 rounded-xl p-2.5 shadow-xs">
    <div class="flex items-center gap-2.5 p-2">
        <div class="bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-white/5 rounded-md w-6 h-6 flex items-center justify-center p-1">
            <x-laravel-exceptions-renderer::icons.alert class="w-2.5 h-2.5 text-blue-500 dark:text-emerald-500" />
        </div>
        <h3 class="text-base font-semibold text-neutral-900 dark:text-white">Previous exceptions</h3>
    </div>

    <div class="pl-7">
        @foreach ($exception->previousExceptions() as $index => $previous)
            <div x-data="{ expanded: false }" class="group relative">
                {{-- Line from top of item to above dot (connects from previous item) --}}
                @if ($index > 0)
                    <div class="absolute -left-[15px] top-0 h-[14px] w-px border-l border-dashed border-neutral-300 dark:border-neutral-600"></div>
                @endif

                {{-- Dot --}}
                <div class="absolute -left-[19px] top-[14px] size-[9px] rounded-full bg-blue-500 dark:bg-emerald-500"></div>

                {{-- Line from below dot to bottom of item (connects to next item) --}}
                @if ($index < $exception->previousExceptions()->count() - 1)
                    <div class="absolute -left-[15px] top-[23px] bottom-0 w-px border-l border-dashed border-neutral-300 dark:border-neutral-600"></div>
                @endif

                {{-- Header + Message --}}
                <div
                    class="flex gap-2.5 pt-2 pb-3 px-2 cursor-pointer rounded-lg hover:bg-white/50 dark:hover:bg-white/2"
                    @click="expanded = !expanded"
                >
                    <div class="flex-1 min-w-0">
                        <h4 class="font-mono text-sm font-medium text-neutral-900 dark:text-white truncate">{{ $previous->class() }}</h4>
                        <p class="text-sm text-neutral-500 dark:text-neutral-400 mt-1 pr-6">{{ $previous->message() }}</p>
                    </div>
                    <button
                        type="button"
                        class="flex h-6 w-6 flex-shrink-0 cursor-pointer items-center justify-center rounded-md dark:border dark:border-white/8 group-hover:text-blue-500 group-hover:dark:text-emerald-500"
                        :class="{
                            'text-blue-500 dark:text-emerald-500 dark:bg-white/5': expanded,
                            'text-neutral-500 dark:text-neutral-500 dark:bg-white/3': !expanded,
                        }"
                    >
                        <x-laravel-exceptions-renderer::icons.chevrons-down-up x-show="expanded" />
                        <x-laravel-exceptions-renderer::icons.chevrons-up-down x-show="!expanded" x-cloak />
                    </button>
                </div>

                {{-- Collapsible trace --}}
                <div x-show="expanded" x-cloak class="flex flex-col gap-1.5 pb-3">
                    @foreach ($previous->frameGroups() as $group)
                        @if ($group['is_vendor'])
                            <x-laravel-exceptions-renderer::vendor-frames :frames="$group['frames']" />
                        @else
                            @foreach ($group['frames'] as $frame)
                                <x-laravel-exceptions-renderer::frame :$frame />
                            @endforeach
                        @endif
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
