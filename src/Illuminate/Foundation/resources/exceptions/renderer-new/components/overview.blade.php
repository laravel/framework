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
            <x-laravel-exceptions-renderer-new::badge type="error" variant="solid">
                <x-laravel-exceptions-renderer-new::icons.alert class="w-2.5 h-2.5" />
                500
            </x-laravel-exceptions-renderer-new::badge>
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
