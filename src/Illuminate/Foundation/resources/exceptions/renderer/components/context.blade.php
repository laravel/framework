<x-laravel-exceptions-renderer::card class="mt-6 overflow-x-auto">

    <div>
        <span class="text-xl lg:text-2xl font-bold">Request</span>
    </div>

    <div class="mt-2">
        <span>{{ $exception->request()->method() }}</span>
        <span class="text-gray-500">{{ $exception->request()->httpHost() }}</span>
    </div>

    <div class="mt-4">
        <span class="font-semibold text-gray-900 dark:text-white">Headers</span>
    </div>

    <dl class="mt-1 grid grid-cols-1 border rounded dark:border-gray-800">
        @forelse ($exception->requestHeaders() as $key => $value)
            <div class="flex items-center gap-2 {{ $loop->first ? '' : 'border-t' }} dark:border-gray-800">
                <span data-tippy-content="{{ $key }}" class="flex-none w-[8rem] lg:w-[12rem] truncate px-5 py-3 border-r dark:border-gray-800 text-sm lg:text-md cursor-pointer">{{ $key }}</span>
                <span class="flex-grow min-w-0" style="-webkit-mask-image: linear-gradient(90deg,transparent 0,#000 1rem,#000 calc(100% - 3rem),transparent calc(100% - 1rem))">
                    <pre class="overflow-y-hidden scrollbar-hidden text-xs lg:text-sm"><code
                        data-highlighted="yes"
                        class="px-5 py-3 overflow-y-hidden scrollbar-hidden max-h-32 overflow-x-scroll scrollbar-hidden-x"
                    >{{ $value }}</code></pre>
            </div>
        @empty
            <div class="flex items-center gap-2">
                <span class="px-5 py-3 text-sm lg:text-md">No headers data</span>
            </div>
        @endforelse
    </dl>

    <div class="mt-4">
        <span class="font-semibold text-gray-900 dark:text-white">Body</span>
    </div>

    <dl class="mt-1 grid grid-cols-1 border rounded dark:border-gray-800">
        @if ($payload = $exception->requestBody())
            <div class="flex items-center>
                <span class="flex-grow min-w-0" style="-webkit-mask-image: linear-gradient(90deg,transparent 0,#000 1rem,#000 calc(100% - 3rem),transparent calc(100% - 1rem))">
                    <pre class="mx-5 my-3 overflow-y-hidden scrollbar-hidden text-xs lg:text-sm"><code
                            data-highlighted="yes"
                            class="overflow-y-hidden scrollbar-hidden overflow-x-scroll scrollbar-hidden-x"
                        >{!! $payload !!}</code></pre>
                </span>
            </div>
        @else
            <div class="flex items-center gap-2">
                <span class="px-5 py-3 text-sm lg:text-md">No body data</span>
            </div>
        @endif
    </dl>

    <div class="mt-4">
        <span class="font-semibold text-gray-900 dark:text-white">
            Queries
        </span>
        <span class="text-xs text-gray-500 dark:text-gray-400">
            @if (count($exception->listener()->queries()) === 100)
                only the first 100 queries are displayed
            @endif
        </span>

    </div>

    <dl class="mt-1 grid grid-cols-1 border rounded dark:border-gray-800">
        @forelse ($exception->listener()->queries() as ['connectionName' => $connectionName, 'sql' => $sql, 'time' => $time])
            <div class="flex items-center gap-2 {{ $loop->first ? '' : 'border-t' }} dark:border-gray-800">
                <div class="flex-none w-[8rem] lg:w-[12rem] truncate px-5 py-3 border-r dark:border-gray-800 text-sm lg:text-md">
                    <span>{{ $connectionName }}</span>
                    <span class="text-gray-500 hidden lg:inline-block text-xs">({{ $time }} ms)</span>
                </div>
                <span class="flex-grow min-w-0" style="-webkit-mask-image: linear-gradient(90deg,transparent 0,#000 1rem,#000 calc(100% - 3rem),transparent calc(100% - 1rem))">
                    <pre class="overflow-y-hidden scrollbar-hidden text-xs lg:text-sm"><code
                        data-highlighted="yes"
                        class="px-5 py-3 overflow-y-hidden scrollbar-hidden max-h-32 overflow-x-scroll scrollbar-hidden-x"
                    >{{ $sql }}</code></pre>
                </span>
            </div>
        @empty
            <div class="flex items-center gap-2">
                <span class="px-5 py-3">No query data</span>
            </div>
        @endforelse
    </dl>
</x-laravel-exceptions-renderer::card>
