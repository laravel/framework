@props(['exception'])

<div class="flex flex-col gap-1 bg-white/[0.01] border border-neutral-800 rounded-xl p-[10px]">
    <div class="flex items-center gap-2.5 p-2">
        <div class="bg-neutral-800 border border-white/5 rounded-md w-6 h-6 flex items-center justify-center p-1">
            <x-laravel-exceptions-renderer-new::icons.alert class="w-2.5 h-2.5 text-emerald-500" />
        </div>
        <h3 class="text-base font-semibold">Exception trace</h3>
    </div>

    <div class="flex flex-col gap-1.5 bg-neutral-50 dark:border-neutral-800 dark:bg-neutral-900">
        @foreach ($exception->frameGroups() as $group)
            @if ($group['vendor'])
                <x-laravel-exceptions-renderer-new::vendor-frames :frames="$group['frames']" />
            @else
                @foreach ($group['frames'] as $frame)
                    <x-laravel-exceptions-renderer-new::frame :$frame />
                @endforeach
            @endif
        @endforeach
    </div>
</div>
