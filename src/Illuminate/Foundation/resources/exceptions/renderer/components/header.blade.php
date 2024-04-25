<x-laravel-exceptions-renderer::card>
    <div class="flex items-center justify-between gap-2">
        <div>
            <div class="inline-block rounded-full px-3 py-2 bg-red-500/20 dark:bg-red-500/20">
                <span class="font-bold leading-5 text-red-500">
                    {{ $exception->class() }}
                </span>
            </div>
            <h1 class="mt-4 text-2xl font-semibold text-gray-900 dark:text-white">{{ $exception->message() }}</h1>
        </div>
        <div class="md:block hidden text-right">
            <div class="rounded-full bg-gray-200 px-3 py-2 dark:bg-gray-800">
                <span class="text-sm leading-5 text-gray-900 dark:text-white">
                    {{ $exception->request()->method() }} {{ $exception->request()->url() }}
                </span>
            </div>
            <div class="mt-4 px-4">
                <span class="text-sm text-gray-500 dark:text-gray-400">PHP {{ PHP_VERSION }} - Laravel {{ app()->version() }}</span>
            </div>
        </div>
    </div>
</x-laravel-exceptions-renderer::card>
