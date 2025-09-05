@props(['frame'])

<div
    x-data="{
        expanded: {{ $frame->isMain() ? 'true' : 'false' }},
        hasCode: {{ $frame->snippet() ? 'true' : 'false' }}
    }"
    class="group rounded-lg border"
    :class="expanded ? 'dark:border-white/10' : 'dark:border-white/5'"
>
    <div
        class="flex h-11 items-center gap-3 bg-white pr-2.5 pl-4 dark:bg-white/3"
        :class="{
            'cursor-pointer hover:bg-white/50 dark:hover:bg-white/5 hover:[&_svg]:stroke-emerald-500': hasCode,
            'dark:bg-white/5': expanded,
            'dark:bg-white/3': !expanded
        }"
        @click="hasCode && (expanded = !expanded)"
    >
        {{-- Dot --}}
        <div class="flex size-3 items-center justify-center flex-shrink-0">
          <div class="size-2 rounded-full bg-neutral-400 dark:bg-neutral-400"></div>
        </div>

        <div class="flex flex-1 items-center justify-between gap-6 min-w-0">
            <div class="flex-shrink-0">
                <x-laravel-exceptions-renderer-new::formatted-source :$frame />
            </div>
            <div class="flex-1 min-w-0">
                <x-laravel-exceptions-renderer-new::file-with-line :file="$frame->file()" :line="$frame->line()" />
            </div>
        </div>

        <div class="flex-shrink-0">
            <button
                x-cloak
                type="button"
                class="flex h-6 w-6 cursor-pointer items-center justify-center rounded-md border dark:border-white/8 group-hover:text-emerald-500"
                :class="{
                    'text-emerald-500 dark:bg-white/5': expanded,
                    'text-neutral-500 dark:bg-white/3': !expanded,
                }"
            >
                <x-laravel-exceptions-renderer-new::icons.chevrons-down-up x-show="expanded" />
                <x-laravel-exceptions-renderer-new::icons.chevrons-up-down x-show="!expanded" />
            </button>
        </div>
    </div>

    @if($snippet = $frame->snippet())
        <x-laravel-exceptions-renderer-new::frame-code :code="$snippet" :highlightedLine="$frame->line()" x-show="expanded" />
    @endif
</div>
