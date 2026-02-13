@props(['exception'])

@foreach ($exception->previousExceptions() as $previous)
    <div
        x-data="{ expanded: false }"
        class="group flex flex-col gap-2.5 bg-neutral-50 dark:bg-white/1 border border-neutral-200 dark:border-neutral-800 rounded-xl p-2.5 shadow-xs"
    >
        <div
            class="flex items-center gap-2.5 p-2 cursor-pointer rounded-lg hover:bg-white/50 dark:hover:bg-white/2"
            @click="expanded = !expanded"
        >
            <div class="bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-white/5 rounded-md w-6 h-6 flex items-center justify-center p-1">
                <x-laravel-exceptions-renderer::icons.alert class="w-2.5 h-2.5 text-orange-500 dark:text-orange-400" />
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="text-base font-semibold text-neutral-900 dark:text-white truncate">
                    Caused by <span class="text-orange-600 dark:text-orange-400">{{ $previous->class() }}</span>
                </h3>
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

        <div class="px-2 pb-1">
            <p class="text-sm text-neutral-600 dark:text-neutral-400">{{ $previous->message() }}</p>
        </div>

        <div x-show="expanded" x-cloak class="flex flex-col gap-1.5">
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
