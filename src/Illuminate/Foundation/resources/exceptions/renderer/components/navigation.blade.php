<header class="px-5">
    <div class="container mx-auto border-b border-gray-200 py-3 dark:border-gray-900 sm:py-5">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <span class="ml-2 text-lg font-medium text-gray-700 dark:text-gray-300 sm:text-2xl">
                    {{ $exception->title() }}
                </span>
            </div>

            <div class="flex items-center gap-3 sm:gap-6">
                <x-laravel-exceptions-renderer::theme-swicher />
            </div>
        </div>
    </div>
</header>
