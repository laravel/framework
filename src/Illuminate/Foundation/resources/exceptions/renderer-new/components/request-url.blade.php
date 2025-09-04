@props(['request'])

<div
    x-data="{
        copied: false,
        async copyToClipboard() {
            try {
                await navigator.clipboard.writeText('{{ $request->fullUrl() }}');
                this.copied = true;
                setTimeout(() => { this.copied = false }, 3000);
            } catch (err) {
                console.error('Failed to copy the requestURL: ', err);
            }
        }
    }"
    class="backdrop-blur-[6px] bg-white/[0.04] border border-white/5 rounded-lg flex items-center justify-between p-2"
>
    <div class="flex items-center gap-3 min-w-0 w-full">
        <div class="text-white bg-rose-600 border border-rose-500 rounded-md h-6 flex items-center gap-1.5 px-1.5">
            <x-laravel-exceptions-renderer-new::icons.globe class="w-2.5 h-2.5" />
            <span class="text-[13px] font-mono">{{ $request->method() }}</span>
        </div>
        <div class="min-w-0 flex-1">
            <x-laravel-exceptions-renderer-new::tooltip side="left">
                <x-slot:trigger>
                    <div class="text-sm font-light truncate">
                        {{ $request->fullUrl() }}
                    </div>
                </x-slot>

                <span>{{ $request->fullUrl() }}</span>
            </x-laravel-exceptions-renderer-new::tooltip>
        </div>
        <button
            x-cloak
            @click="copyToClipboard()"
            class="bg-white/[0.05] rounded-md w-6 h-6 flex flex-shrink-0 items-center justify-center cursor-pointer transition-colors hover:bg-white/10"
        >
            <x-laravel-exceptions-renderer-new::icons.copy class="w-3 h-3 text-neutral-400" x-show="!copied" />
            <x-laravel-exceptions-renderer-new::icons.check class="w-3 h-3 text-emerald-500" x-show="copied" />
        </button>
    </div>
</div>
