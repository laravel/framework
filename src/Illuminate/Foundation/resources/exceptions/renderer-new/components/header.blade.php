@props(['exception'])

<div class="flex flex-col gap-8">
    <div class="flex flex-col gap-6">
        <h1 class="text-3xl font-semibold">{{ $exception->class() }}</h1>
        <p class="text-xl font-light text-neutral-300">
            {{ $exception->message() }}
        </p>
    </div>

    <div class="flex items-start gap-2">
        <div class="bg-white/[0.03] border border-white/10 divide-x divide-white/10 rounded-md shadow-sm flex items-center gap-0.5">
            <div class="flex items-center gap-1.5 h-6 px-[6px] font-mono text-[13px]">
                <span class="text-neutral-500">LARAVEL</span>
                <span class="text-neutral-300">{{ app()->version() }}</span>
            </div>
            <div class="flex items-center gap-1.5 h-6 px-[6px] font-mono text-[13px]">
                <span class="text-neutral-500">PHP</span>
                <span class="text-neutral-300">{{ PHP_VERSION }}</span>
            </div>
        </div>
        <div class="text-white bg-rose-600 border border-rose-500 rounded-md h-6 flex items-center gap-1.5 px-1.5">
            <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.178 2.625-1.516 2.625H3.72c-1.337 0-2.19-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
            </svg>
            <span class="text-[13px] font-mono">UNHANDLED</span>
        </div>
        <div class="text-rose-100 bg-rose-950 border border-rose-900 rounded-md h-6 flex items-center gap-1.5 px-1.5">
            <span class="text-[13px] font-mono">CODE {{ $exception->code() }}</span>
        </div>
    </div>
</div>
