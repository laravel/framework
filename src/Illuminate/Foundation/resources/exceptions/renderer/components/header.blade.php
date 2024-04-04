<x-laravel-exceptions-renderer::card>
    <div class="flex items-center justify-between gap-2">
        <span class="inline-block rounded-md bg-red-100 px-2 py-1 text-xs font-medium leading-5 text-gray-800 dark:bg-red-800 dark:text-red-100">
            {{ $exception->class() }}
        </span>
        <div>
            <div>
                <span class="rounded-md bg-gray-200 px-2 py-1 text-xs font-medium leading-5 text-gray-900 dark:bg-gray-800 dark:text-gray-300">
                    {{ $exception->request()->method() }} {{ $exception->request()->url() . $exception->request()->path() }}
                </span>
            </div>
            <div>
                <span class="text-xs text-gray-500 dark:text-gray-400">PHP {{ PHP_VERSION }} - Laravel {{ app()->version() }}</span>
            </div>
        </div>
    </div>

    <h1 class="mt-4 text-2xl font-semibold text-gray-900 dark:text-gray-300">{{ $exception->message() }}</h1>
</x-laravel-exceptions-renderer::card>
