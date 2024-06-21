<x-laravel-exceptions-renderer::card>
    <div class="md:flex md:items-center md:justify-between md:gap-2">
        <div class="min-w-0">
            <div class="inline-block rounded-full bg-red-500/20 px-3 py-2 max-w-full text-sm font-bold leading-5 text-red-500 truncate lg:text-base dark:bg-red-500/20">
                <span class="hidden md:inline">
                    {{ $exception->class() }}
                </span>
                <span class="md:hidden">
                    {{ implode(' ', array_slice(explode('\\', $exception->class()), -1)) }}
                </span>
            </div>
            <div class="mt-4 text-lg font-semibold text-gray-900 break-words dark:text-white lg:text-2xl">
                {{ $exception->message() }}
            </div>
        </div>

        <div class="hidden text-right shrink-0 md:block md:min-w-64 md:max-w-80">
            <div>
                <span class="inline-block rounded-full bg-gray-200 px-3 py-2 text-sm leading-5 text-gray-900 max-w-full truncate dark:bg-gray-800 dark:text-white">
                    {{ $exception->request()->method() }} {{ $exception->request()->httpHost() }}
                </span>
            </div>
            <div class="px-4">
                <span class="text-sm text-gray-500 dark:text-gray-400">PHP {{ PHP_VERSION }} — Laravel {{ app()->version() }}</span>
            </div>
        </div>
    </div>
</x-laravel-exceptions-renderer::card>
