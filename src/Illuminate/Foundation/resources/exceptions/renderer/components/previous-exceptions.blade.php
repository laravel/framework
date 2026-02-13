@props(['exception'])

<div id="previous-exceptions" class="flex flex-col gap-2.5 bg-neutral-50 dark:bg-white/1 border border-neutral-200 dark:border-neutral-800 rounded-xl p-2.5 shadow-xs">
    <div class="flex items-center gap-2.5 p-2">
        <div class="bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-white/5 rounded-md w-6 h-6 flex items-center justify-center p-1">
            <x-laravel-exceptions-renderer::icons.alert class="w-2.5 h-2.5 text-blue-500 dark:text-emerald-500" />
        </div>
        <h3 class="text-base font-semibold text-neutral-900 dark:text-white">Previous {{ Str::plural('exception', $exception->previousExceptions()->count()) }}</h3>
    </div>

    <div class="flex flex-col">
        @foreach ($exception->previousExceptions() as $index => $previous)
            <div class="flex gap-2.5 px-2">
                {{-- Timeline column --}}
                @if ($exception->previousExceptions()->count() > 1)
                    <div class="flex flex-col items-center w-6 flex-shrink-0 self-stretch">
                        @if ($index > 0)
                            <div class="h-[23.5px] w-px border-l border-dashed border-emerald-900"></div>
                        @else
                            <div class="h-[23.5px]"></div>
                        @endif

                        <div class="size-[9px] flex-shrink-0 rounded-full bg-emerald-800"></div>

                        @if ($index < $exception->previousExceptions()->count() - 1)
                            <div class="flex-1 w-px border-l border-dashed border-emerald-900"></div>
                        @else
                            <div class="flex-1"></div>
                        @endif
                    </div>
                @endif

                {{-- Exception content --}}
                <div
                    x-data="{ expanded: false }"
                    class="group/exception flex-1 min-w-0 rounded-lg my-1.5"
                    :class="{
                        'border border-neutral-200 bg-white/50 dark:bg-white/2 dark:border-white/5': expanded,
                        @if ($exception->previousExceptions()->count() === 1)
                            'border border-neutral-200 dark:border-transparent dark:bg-white/2': !expanded,
                        @else
                            'hover:border hover:border-neutral-200 dark:hover:border-none': !expanded,
                        @endif
                    }"
                >
                    {{-- Header + Message --}}
                    <div
                        class="flex gap-2.5 p-3 cursor-pointer rounded-lg"
                        :class="{ 'hover:bg-white/50 dark:hover:bg-white/2': !expanded }"
                        @click="expanded = !expanded"
                    >
                        <div
                            class="flex-1 min-w-0"
                            :class="expanded ? 'flex flex-col' : 'flex items-baseline gap-2'"
                        >
                            <h4 class="font-mono text-sm font-medium text-neutral-900 dark:text-white flex-shrink-0 max-w-full truncate">{{ $previous->class() }}</h4>
                            <p
                                class="text-sm text-neutral-500 dark:text-neutral-400"
                                :class="expanded ? 'mt-1 break-words' : 'truncate'"
                            >{{ $previous->message() }}</p>
                        </div>
                        <button
                            type="button"
                            class="flex h-6 w-6 flex-shrink-0 cursor-pointer items-center justify-center rounded-md dark:border dark:border-white/8 group-hover/exception:text-blue-500 group-hover/exception:dark:text-emerald-500"
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
                    <div x-show="expanded" x-cloak class="flex flex-col gap-1.5 p-3">
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
            </div>
        @endforeach
    </div>
</div>
