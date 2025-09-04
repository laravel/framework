@props(['side' => 'center'])

<div
    class="relative min-w-0"
    x-data="{
        open: false,
        timeout: null,
        show() {
            this.timeout = setTimeout(() => { this.open = true }, 500)
        },
        hide() {
            clearTimeout(this.timeout)
            this.open = false
        }
    }"
    @mouseenter="show()"
    @mouseleave="hide()"
>
    <div>
        {{ $trigger }}
    </div>
    <div
        x-cloak
        x-show="open"
        @class([
            'absolute bottom-full w-max px-2 py-1',
            'left-0' => $side === 'left',
            'left-1/2 transform -translate-x-1/2' => $side === 'center',
            'right-0' => $side === 'right',
            'animate-in fade-in-0 zoom-in-95 z-50 overflow-hidden rounded-md border text-xs shadow-md backdrop-blur-md',
            'border-neutral-800 bg-neutral-900 text-white',
            'dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100',
            'data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=closed]:zoom-out-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2'
        ])
    >
        {{ $slot }}
    </div>
</div>
