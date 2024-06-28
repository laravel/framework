@use('Illuminate\Support\Str')
<x-laravel-exceptions-renderer::card class="mt-6 overflow-x-auto">
    <div>
        <span class="text-xl font-bold lg:text-2xl">Request</span>
    </div>

    <div class="mt-2">
        <span>{{ $exception->request()->method() }}</span>
        <span class="text-gray-500">{{ Str::start($exception->request()->path(), '/') }}</span>
    </div>

    <div class="mt-4">
        <span class="font-semibold text-gray-900 dark:text-white">Headers</span>
    </div>

    <dl class="mt-1 grid grid-cols-1 rounded border dark:border-gray-800">
        @forelse ($exception->requestHeaders() as $key => $value)
            <div class="flex items-center gap-2 {{ $loop->first ? '' : 'border-t' }} dark:border-gray-800">
                <span
                    data-tippy-content="{{ $key }}"
                    class="lg:text-md w-[8rem] flex-none cursor-pointer truncate border-r px-5 py-3 text-sm dark:border-gray-800 lg:w-[12rem]"
                >
                    {{ $key }}
                </span>
                <span
                    class="min-w-0 flex-grow"
                    style="
                        -webkit-mask-image: linear-gradient(90deg, transparent 0, #000 1rem, #000 calc(100% - 3rem), transparent calc(100% - 1rem));
                    "
                >
                    <pre class="scrollbar-hidden overflow-y-hidden text-xs lg:text-sm"><code class="px-5 py-3 overflow-y-hidden scrollbar-hidden max-h-32 overflow-x-scroll scrollbar-hidden-x">{{ $value }}</code></pre>
                </span>
            </div>
        @empty
            <span
                class="min-w-0 flex-grow"
                style="-webkit-mask-image: linear-gradient(90deg, transparent 0, #000 1rem, #000 calc(100% - 3rem), transparent calc(100% - 1rem))"
            >
                <pre class="scrollbar-hidden mx-5 my-3 overflow-y-hidden text-xs lg:text-sm"><code class="overflow-y-hidden scrollbar-hidden overflow-x-scroll scrollbar-hidden-x">No headers data</code></pre>
            </span>
        @endforelse
    </dl>

    <div class="mt-4">
        <span class="font-semibold text-gray-900 dark:text-white">Body</span>
    </div>

    <div class="mt-1 rounded border dark:border-gray-800">
        <div class="flex items-center">
            <span
                class="min-w-0 flex-grow"
                style="-webkit-mask-image: linear-gradient(90deg, transparent 0, #000 1rem, #000 calc(100% - 3rem), transparent calc(100% - 1rem))"
            >
                <pre class="scrollbar-hidden mx-5 my-3 overflow-y-hidden text-xs lg:text-sm"><code class="overflow-y-hidden scrollbar-hidden overflow-x-scroll scrollbar-hidden-x">{!! $exception->requestBody() ?: 'No body data' !!}</code></pre>
            </span>
        </div>
    </div>

</x-laravel-exceptions-renderer::card>

<x-laravel-exceptions-renderer::card class="mt-6 overflow-x-auto">
    <div>
        <span class="text-xl font-bold lg:text-2xl">Application</span>
    </div>

    <div class="mt-4">
        <span class="font-semibold text-gray-900 dark:text-white"> Routing </span>
    </div>

    <dl class="mt-1 grid grid-cols-1 rounded border dark:border-gray-800">
        @forelse ($exception->applicationRouteContext() as $name => $value)
            <div class="flex items-center gap-2 {{ $loop->first ? '' : 'border-t' }} dark:border-gray-800">
                <span
                    data-tippy-content="{{ $name }}"
                    class="lg:text-md w-[8rem] flex-none cursor-pointer truncate border-r px-5 py-3 text-sm dark:border-gray-800 lg:w-[12rem]"
                    >{{ $name }}</span
                >
                <span
                    class="min-w-0 flex-grow"
                    style="
                        -webkit-mask-image: linear-gradient(90deg, transparent 0, #000 1rem, #000 calc(100% - 3rem), transparent calc(100% - 1rem));
                    "
                >
                    <pre class="scrollbar-hidden overflow-y-hidden text-xs lg:text-sm"><code class="px-5 py-3 overflow-y-hidden scrollbar-hidden max-h-32 overflow-x-scroll scrollbar-hidden-x">{{ $value }}</code></pre>
                </span>
            </div>
        @empty
            <span
                class="min-w-0 flex-grow"
                style="-webkit-mask-image: linear-gradient(90deg, transparent 0, #000 1rem, #000 calc(100% - 3rem), transparent calc(100% - 1rem))"
            >
                <pre class="scrollbar-hidden mx-5 my-3 overflow-y-hidden text-xs lg:text-sm"><code class="overflow-y-hidden scrollbar-hidden overflow-x-scroll scrollbar-hidden-x">No routing data</code></pre>
            </span>
        @endforelse
    </dl>

    @if ($routeParametersContext = $exception->applicationRouteParametersContext())
        <div class="mt-4">
            <span class="text-gray-900 dark:text-white text-sm"> Routing Parameters </span>
        </div>

        <div class="mt-1 rounded border dark:border-gray-800">
            <div class="flex items-center">
                <span
                    class="min-w-0 flex-grow"
                    style="-webkit-mask-image: linear-gradient(90deg, transparent 0, #000 1rem, #000 calc(100% - 3rem), transparent calc(100% - 1rem))"
                >
                    <pre class="scrollbar-hidden mx-5 my-3 overflow-y-hidden text-xs lg:text-sm"><code class="overflow-y-hidden scrollbar-hidden overflow-x-scroll scrollbar-hidden-x">{!! $routeParametersContext !!}</code></pre>
                </span>
            </div>
        </div>
    @endif

    <div class="mt-4">
        <span class="font-semibold text-gray-900 dark:text-white"> Database Queries </span>
        <span class="text-xs text-gray-500 dark:text-gray-400">
            @if (count($exception->applicationQueries()) === 100)
                only the first 100 queries are displayed
            @endif
        </span>
    </div>

    <dl class="mt-1 grid grid-cols-1 rounded border dark:border-gray-800">
        @forelse ($exception->applicationQueries() as ['connectionName' => $connectionName, 'sql' => $sql, 'time' => $time])
            <div class="flex items-center gap-2 {{ $loop->first ? '' : 'border-t' }} dark:border-gray-800">
                <div class="lg:text-md w-[8rem] flex-none truncate border-r px-5 py-3 text-sm dark:border-gray-800 lg:w-[12rem]">
                    <span>{{ $connectionName }}</span>
                    <span class="hidden text-xs text-gray-500 lg:inline-block">({{ $time }} ms)</span>
                </div>
                <span
                    class="min-w-0 flex-grow"
                    style="
                        -webkit-mask-image: linear-gradient(90deg, transparent 0, #000 1rem, #000 calc(100% - 3rem), transparent calc(100% - 1rem));
                    "
                >
                    <pre class="scrollbar-hidden overflow-y-hidden text-xs lg:text-sm"><code class="px-5 py-3 overflow-y-hidden scrollbar-hidden max-h-32 overflow-x-scroll scrollbar-hidden-x">{{ $sql }}</code></pre>
                </span>
            </div>
        @empty
            <span
                class="min-w-0 flex-grow"
                style="-webkit-mask-image: linear-gradient(90deg, transparent 0, #000 1rem, #000 calc(100% - 3rem), transparent calc(100% - 1rem))"
            >
                <pre class="scrollbar-hidden mx-5 my-3 overflow-y-hidden text-xs lg:text-sm"><code class="overflow-y-hidden scrollbar-hidden overflow-x-scroll scrollbar-hidden-x">No query data</code></pre>
            </span>
        @endforelse
    </dl>
</x-laravel-exceptions-renderer::card>
