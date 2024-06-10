<div class="hidden overflow-x-auto sm:col-span-1 lg:block">
    <div
        class="h-[35.5rem] scrollbar-hidden trace text-sm text-gray-400 dark:text-gray-300"
    >
        <div class="mb-2 inline-block rounded-full bg-red-500/20 px-3 py-2 dark:bg-red-500/20 sm:col-span-1">
            <button
                @click="includeVendorFrames = !includeVendorFrames"
                class="inline-flex items-center font-bold leading-5 text-red-500"
            >
                <span x-show="includeVendorFrames">Collapse</span>
                <span
                    x-cloak
                    x-show="!includeVendorFrames"
                    >Expand</span
                >
                <span class="ml-1">vendor frames</span>

                <div class="flex flex-col ml-1 -mt-2" x-cloak x-show="includeVendorFrames">
                    <x-laravel-exceptions-renderer::icons.chevron-down />
                    <x-laravel-exceptions-renderer::icons.chevron-up />
                </div>

                <div class="flex flex-col ml-1 -mt-2" x-cloak x-show="! includeVendorFrames">
                    <x-laravel-exceptions-renderer::icons.chevron-up />
                    <x-laravel-exceptions-renderer::icons.chevron-down />
                </div>
            </button>
        </div>

        <div class="mb-12 space-y-2">
            @foreach ($exception->frames() as $frame)
                @if (! $frame->isFromVendor())
                    @php
                        $vendorFramesCollapsed = $exception->frames()->take($loop->index)->reverse()->takeUntil(fn ($frame) => ! $frame->isFromVendor());
                    @endphp

                    <div x-show="! includeVendorFrames">
                        @if ($vendorFramesCollapsed->isNotEmpty())
                            <div class="text-gray-500">
                                {{ $vendorFramesCollapsed->count() }} vendor frame{{ $vendorFramesCollapsed->count() > 1 ? 's' : '' }} collapsed
                            </div>
                        @endif
                    </div>
                @endif

                <button
                    class="w-full text-left dark:border-gray-900"
                    x-show="{{ $frame->isFromVendor() ? 'includeVendorFrames' : 'true' }}"
                    @click="index = {{ $loop->index }}"
                >
                    <div
                        x-bind:class="
                            index === {{ $loop->index }}
                                ? 'rounded-r-md bg-gray-100 dark:bg-gray-800 border-l dark:border dark:border-gray-700 border-l-red-500 dark:border-l-red-500'
                                : 'hover:bg-gray-100/75 dark:hover:bg-gray-800/75'
                        "
                    >
                        <div class="scrollbar-hidden overflow-x-auto border-l-2 border-transparent p-2">
                            <div class="nowrap text-gray-900 dark:text-gray-300">
                                <span class="inline-flex items-baseline">
                                    <span class="text-gray-900 dark:text-gray-300">{{ $frame->source() }}</span>
                                    <span class="font-mono text-xs">:{{ $frame->line() }}</span>
                                </span>
                            </div>
                            <div class="text-gray-500 dark:text-gray-400">
                                {{ $exception->frames()->get($loop->index + 1)?->callable() }}
                            </div>
                        </div>
                    </div>
                </button>

                @if (! $frame->isFromVendor() && $exception->frames()->slice($loop->index + 1)->filter(fn ($frame) => ! $frame->isFromVendor())->isEmpty())
                    @if ($exception->frames()->slice($loop->index + 1)->count())
                        <div x-show="! includeVendorFrames">
                            <div class="text-gray-500">
                                {{ $exception->frames()->slice($loop->index + 1)->count() }} vendor
                                frame{{ $exception->frames()->slice($loop->index + 1)->count() > 1 ? 's' : '' }} collapsed
                            </div>
                        </div>
                    @endif
                @endif
            @endforeach
        </div>
    </div>
</div>
