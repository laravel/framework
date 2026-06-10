<div class="bg-neutral-50 dark:bg-white/1 border border-neutral-200 dark:border-neutral-800 rounded-xl p-2.5 shadow-xs flex flex-col gap-2.5">
    <div class="flex items-center gap-2.5 p-2">
        <div class="bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-white/5 rounded-md w-6 h-6 flex items-center justify-center p-1">
            <svg class="w-3 h-3 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
            </svg>
        </div>
        <h3 class="text-base font-semibold text-neutral-900 dark:text-white">Suggested solutions</h3>
    </div>

    <div class="flex flex-col gap-1.5">
        @foreach ($solutions as $solution)
            <div class="rounded-lg bg-white dark:bg-white/3 border border-neutral-200 dark:border-white/10 shadow-xs overflow-hidden">
                <div class="p-4">
                    <p class="text-sm font-medium text-neutral-900 dark:text-white">
                        {{ $solution->title() }}
                    </p>
                    @if ($solution->description())
                        <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400 whitespace-pre-line">{!! preg_replace('/`([^`]+)`/', '<code class="px-1.5 py-0.5 rounded bg-neutral-100 dark:bg-white/10 text-xs font-mono text-neutral-800 dark:text-neutral-200">$1</code>', e($solution->description())) !!}</p>
                    @endif
                </div>

                @if (! empty($solution->links()))
                    <div class="px-4 pb-4 flex flex-wrap gap-3">
                        @foreach ($solution->links() as $label => $url)
                            <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-xs text-neutral-500 dark:text-neutral-400 hover:text-blue-500 dark:hover:text-emerald-500 transition-colors">
                                {{ $label }}
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                </svg>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
