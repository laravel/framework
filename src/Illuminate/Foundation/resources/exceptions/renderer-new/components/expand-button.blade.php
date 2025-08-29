@props(['expanded' => false])

<button
    x-data="{ expanded: {{ $expanded ? 'true' : 'false' }} }"
    type="button"
    class="flex h-6 w-6 cursor-pointer items-center justify-center rounded-md border dark:border-white/8"
    :class="{
        'text-emerald-500 dark:bg-white/5': expanded,
        'text-neutral-500 dark:bg-white/3': !expanded,
    }"
    @click="expanded = !expanded; $dispatch('expand-button-clicked', { expanded })"
>
    <x-laravel-exceptions-renderer-new::icons.chevrons-down-up x-show="expanded" />
    <x-laravel-exceptions-renderer-new::icons.chevrons-up-down x-show="!expanded" />
</button>
