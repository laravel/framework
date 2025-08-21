@props(['frames'])

@use('Illuminate\Support\Str')

<div class="group rounded-lg border bg-white dark:border-white/5 dark:bg-white/5">
    <div class="flex h-11 cursor-pointer items-center gap-2.5 rounded-lg pr-2.5 pl-4 hover:bg-white/50 dark:hover:bg-white/2">
        {{-- Folder --}}

        <div class="flex-1 font-mono text-xs leading-3 text-neutral-600 dark:text-neutral-400">
            {{ count($frames)}} vendor {{ Str::plural('frame', count($frames))}}
        </div>

        {{-- Expand button --}}
    </div>

    <div class="flex flex-col divide-y divide-neutral-100 border-t border-neutral-100 dark:divide-white/5 dark:border-white/5">
        @foreach ($frames as $frame)
            <div class="flex flex-col divide-y divide-neutral-100 border-t border-neutral-100 dark:divide-white/5 dark:border-white/5">
                <x-laravel-exceptions-renderer-new::vendor-frame :$frame />
            </div>
        @endforeach
    </div>
</div>
