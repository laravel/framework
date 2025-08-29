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
    <div class="flex items-center gap-3">
        <div class="bg-blue-600 rounded h-[25px] px-2">
            <span class="text-[13px] font-mono">{{ $request->method() }}</span>
        </div>
        <div class="opacity-60 w-3 h-3">
            <svg class="w-full h-full" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4.083 9h1.946c.089-1.546.383-2.97.837-4.118A6.004 6.004 0 004.083 9zM10 2a8 8 0 100 16 8 8 0 000-16zm0 2c-.076 0-.232.032-.465.262-.238.234-.497.623-.737 1.182-.389.907-.673 2.142-.766 3.556h3.936c-.093-1.414-.377-2.649-.766-3.556-.24-.56-.5-.948-.737-1.182C10.232 4.032 10.076 4 10 4zm3.971 5c-.089-1.546-.383-2.97-.837-4.118A6.004 6.004 0 0115.917 9h-1.946zm-2.003 2H8.032c.093 1.414.377 2.649.766 3.556.24.56.5.948.737 1.182.233.23.389.262.465.262.076 0 .232-.032.465-.262.238-.234.498-.623.737-1.182.389-.907.673-2.142.766-3.556zm1.166 4.118c.454-1.147.748-2.572.837-4.118h1.946a6.004 6.004 0 01-2.783 4.118zm-6.268 0C6.412 13.97 6.118 12.546 6.03 11H4.083a6.004 6.004 0 002.783 4.118z" clip-rule="evenodd" />
            </svg>
        </div>
        <div class="text-sm font-light">
            {{ $request->fullUrl() }}
        </div>
    </div>
    <button
        @click="copyToClipboard()"
        class="bg-white/[0.05] rounded-md w-6 h-6 flex items-center justify-center cursor-pointer transition-colors hover:bg-white/10"
    >
        <svg x-show="!copied" class="w-3 h-3 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
        </svg>
        <svg x-show="copied" class="w-3 h-3 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
    </button>
</div>
