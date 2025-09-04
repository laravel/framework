<x-laravel-exceptions-renderer-new::layout>
    <!-- Topbar -->
    <x-laravel-exceptions-renderer-new::section-container class="px-6 py-6">
        <x-laravel-exceptions-renderer-new::topbar :title="$exception->title()" :markdown="$exceptionAsMarkdown" />
    </x-laravel-exceptions-renderer-new::section-container>

    <x-laravel-exceptions-renderer-new::separator />

    <!-- Header Section -->
    <x-laravel-exceptions-renderer-new::section-container class="flex flex-col gap-8">
        <x-laravel-exceptions-renderer-new::header :$exception />
    </x-laravel-exceptions-renderer-new::section-container>

    <x-laravel-exceptions-renderer-new::separator />

    <x-laravel-exceptions-renderer-new::section-container class="flex flex-col gap-8">
        <x-laravel-exceptions-renderer-new::request-url :request="$exception->request()" />

        <x-laravel-exceptions-renderer-new::overview :request="$exception->request()" />

        <x-laravel-exceptions-renderer-new::trace :$exception />

        <x-laravel-exceptions-renderer-new::query :queries="$exception->applicationQueries()" />
    </x-laravel-exceptions-renderer-new::section-container>

    <x-laravel-exceptions-renderer-new::separator />

    <!-- Context -->
    <x-laravel-exceptions-renderer-new::section-container class="flex flex-col gap-12">
        <x-laravel-exceptions-renderer-new::request-header :headers="$exception->requestHeaders()" />

        <x-laravel-exceptions-renderer-new::request-body :body="$exception->requestBody()" />

        <x-laravel-exceptions-renderer-new::routing :routing="$exception->applicationRouteContext()" />

        <x-laravel-exceptions-renderer-new::routing-parameter :routeParameters="$exception->applicationRouteParametersContext()" />
    </x-laravel-exceptions-renderer-new::section-container>

    <x-laravel-exceptions-renderer-new::separator />

    <!-- Footer with ASCII Art -->
    <x-laravel-exceptions-renderer-new::section-container>
        <x-laravel-exceptions-renderer-new::icons.laravel-ascii />
    </x-laravel-exceptions-renderer-new::section-container>
</x-laravel-exceptions-renderer-new::layout>
