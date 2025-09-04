@props(['request'])

<div class="flex flex-col gap-3">
    <h2 class="text-lg font-semibold">Overview</h2>
    <div class="flex flex-col">
        <!-- Date Row -->
        <div class="flex items-center gap-2 h-10">
            <div class="text-sm font-mono text-neutral-400 uppercase">DATE</div>
            <div class="flex-1 h-3 border-b-2 border-dotted border-white/20"></div>
            <div class="text-sm font-mono">
                {{ now()->format('Y/m/d H:i:s.v') }} <span class="text-neutral-500">UTC</span>
            </div>
        </div>
        <!-- Status Code Row -->
        <div class="flex items-center gap-2 h-10">
            <div class="text-sm font-mono text-neutral-400 uppercase">STATUS CODE</div>
            <div class="flex-1 h-3 border-b-2 border-dotted border-white/20"></div>
            <div class="bg-rose-500 border border-rose-500 rounded-md h-6 flex items-center gap-1.5 px-1.5">
                <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.178 2.625-1.516 2.625H3.72c-1.337 0-2.19-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                </svg>
                <span class="text-[13px] font-mono text-neutral-300">500</span>
            </div>
        </div>
        <!-- Method Row -->
        <div class="flex items-center gap-2 h-10">
            <div class="text-sm font-mono text-neutral-400 uppercase">METHOD</div>
            <div class="flex-1 h-3 border-b-2 border-dotted border-white/20"></div>
            <div class="text-emerald-400 bg-emerald-950 border border-emerald-800 rounded-md h-6 flex items-center gap-1.5 px-1.5">
                <x-laravel-exceptions-renderer-new::icons.globe class="w-2.5 h-2.5" />
                <span class="text-[13px] font-mono">{{ $request->method() }}</span>
            </div>
        </div>
    </div>
</div>
