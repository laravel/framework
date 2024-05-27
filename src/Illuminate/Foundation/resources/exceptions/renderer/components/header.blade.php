<x-laravel-exceptions-renderer::card>
    <div class="md:flex md:items-center md:justify-between md:gap-2">
        <div>
            <div class="inline-block rounded-full bg-red-500/20 px-3 py-2 dark:bg-red-500/20">
                <span class="hidden text-sm font-bold leading-5 text-red-500 md:inline-block lg:text-base">
                    {{ $exception->class() }}
                </span>
                <span class="text-sm font-bold leading-5 text-red-500 md:hidden lg:text-base">
                    {{ implode(' ', array_slice(explode('\\', $exception->class()), -1)) }}
                </span>
            </div>
            <div class="mt-4 text-lg font-semibold text-gray-900 dark:text-white lg:text-2xl">{{ $exception->message() }}</div>
        </div>

        <div class="hidden text-right md:block md:min-w-64">
            <div>
                <span class="rounded-full bg-gray-200 px-3 py-2 text-sm leading-5 text-gray-900 dark:bg-gray-800 dark:text-white">
                    {{ $exception->request()->method() }} {{ $exception->request()->httpHost() }}
                </span>
            </div>
            <div class="mt-4 px-4">
                <span class="text-sm text-gray-500 dark:text-gray-400">PHP {{ PHP_VERSION }} — Laravel {{ app()->version() }}</span>
            </div>
        </div>
    </div>
</x-laravel-exceptions-renderer::card>
