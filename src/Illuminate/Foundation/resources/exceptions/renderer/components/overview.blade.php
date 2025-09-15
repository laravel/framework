@props(['request'])

<div class="flex flex-col gap-3">
    <h2 class="text-lg font-semibold text-neutral-900 dark:text-white">Overview</h2>
    <div class="flex flex-col">
        <!-- Date Row -->
        <div class="flex items-center gap-2 h-10">
            <div class="text-sm font-mono text-neutral-500 dark:text-neutral-400 uppercase">DATE</div>
            <div class="flex-1 h-3 border-b-2 border-dotted border-neutral-300 dark:border-white/20"></div>
            <div class="text-sm font-mono text-neutral-900 dark:text-white">
                {{ now()->format('Y/m/d H:i:s.v') }} <span class="text-neutral-500">UTC</span>
            </div>
        </div>
        <!-- Status Code Row -->
        <div class="flex items-center gap-2 h-10">
            <div class="text-sm font-mono text-neutral-500 dark:text-neutral-400 uppercase">STATUS CODE</div>
            <div class="flex-1 h-3 border-b-2 border-dotted border-neutral-300 dark:border-white/20"></div>
            <x-laravel-exceptions-renderer::badge type="error" variant="solid">
                <x-laravel-exceptions-renderer::icons.alert class="w-2.5 h-2.5" />
                500
            </x-laravel-exceptions-renderer::badge>
        </div>
        <!-- Method Row -->
        <div class="flex items-center gap-2 h-10">
            <div class="text-sm font-mono text-neutral-500 dark:text-neutral-400 uppercase">METHOD</div>
            <div class="flex-1 h-3 border-b-2 border-dotted border-neutral-300 dark:border-white/20"></div>
            <x-laravel-exceptions-renderer::http-method method="{{ $request->method() }}" />
        </div>
    </div>
</div>
