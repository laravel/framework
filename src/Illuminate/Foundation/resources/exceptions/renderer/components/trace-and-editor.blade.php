<x-laravel-exceptions-renderer::card class="mt-6 overflow-x-auto">
    <div x-data="{ includeVendorFrames: false, index: {{ $exception->defaultFrame() }} }">
        <div
            class="grid grid-cols-1 gap-6 sm:grid-cols-3"
            style="height: 500px"
        >
            <x-laravel-exceptions-renderer::trace :$exception />
            <x-laravel-exceptions-renderer::editor :$exception />
        </div>
    </div>
</x-laravel-exceptions-renderer::card>
