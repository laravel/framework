<x-laravel-exceptions-renderer::layout :$exception>
    <div class="dark:bg-gray-950 min-h-screen bg-gray-50">
        <x-laravel-exceptions-renderer::navigation :$exception />

        <main class="px-6 pt-6 pb-12">
            <div class="container mx-auto">
                <x-laravel-exceptions-renderer::header :$exception />

                <x-laravel-exceptions-renderer::trace-and-editor :$exception />
            </div>
        </main>
    </div>
</x-laravel-exceptions-renderer::layout>
