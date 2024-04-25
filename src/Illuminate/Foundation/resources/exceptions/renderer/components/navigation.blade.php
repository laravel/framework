<header class="sm:mt-10 mt-3 px-5">
    <div class="py-3 dark:border-gray-900 sm:py-5">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="p-4 rounded-full bg-red-500/20 dark:bg-red-500/20">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 fill-red-500 text-gray-50 dark:text-gray-950">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                </div>

                <span class="ml-3 text-2xl text-dark dark:text-white sm:text-3xl font-bold">
                    {{ $exception->title() }}
                </span>
            </div>

            <div class="flex items-center gap-3 sm:gap-6">
                <x-laravel-exceptions-renderer::theme-swicher />
            </div>
        </div>
    </div>
</header>
