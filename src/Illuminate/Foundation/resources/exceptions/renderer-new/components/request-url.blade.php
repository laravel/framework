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
    <div class="flex items-center gap-3 w-full">
        <x-laravel-exceptions-renderer-new::badge type="error" variant="solid">
            <x-laravel-exceptions-renderer-new::icons.globe class="w-2.5 h-2.5" />
            {{ $request->method() }}
        </x-laravel-exceptions-renderer-new::badge>
        <div class="flex-1 text-sm font-light truncate">
            <span data-tippy-content="{{ $request->fullUrl() }}">
                {{ $request->fullUrl() }}
            </span>
        </div>
        <button
            x-cloak
            @click="copyToClipboard()"
            class="bg-white/5 rounded-md w-6 h-6 flex flex-shrink-0 items-center justify-center cursor-pointer transition-colors hover:bg-white/10"
        >
            <x-laravel-exceptions-renderer-new::icons.copy class="w-3 h-3 text-neutral-400" x-show="!copied" />
            <x-laravel-exceptions-renderer-new::icons.check class="w-3 h-3 text-emerald-500" x-show="copied" />
        </button>
    </div>
</div>
