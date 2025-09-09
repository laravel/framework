@props(['body'])

<div class="flex flex-col gap-3">
    <h2 class="text-lg font-semibold">Body</h2>
    @if($body)
    <div class="bg-white/[2%] border border-neutral-200 dark:border-neutral-800 rounded-md overflow-x-auto p-5 text-sm font-mono shadow-sm">
        <x-laravel-exceptions-renderer-new::syntax-highlight :code="$body" grammar="json" />
    </div>
    @else
    <x-laravel-exceptions-renderer-new::empty-state message="No request body" />
    @endif
</div>
