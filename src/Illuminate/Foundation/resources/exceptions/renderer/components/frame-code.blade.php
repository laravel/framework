@props(['code', 'highlightedLine'])

<div
    class="text-sm rounded-b-lg bg-neutral-50 border-t border-neutral-100 [&_.line]:block [&_.line]:px-4 [&_.line]:py-1 [&_.line]:even:bg-white [&_.line]:odd:bg-white/2 [&_.line]:even:dark:bg-white/2 [&_.line]:odd:dark:bg-white/4 dark:bg-neutral-900 dark:border-white/10"
    {{ $attributes }}
>
    <x-laravel-exceptions-renderer::syntax-highlight
        :code="$code"
        grammar="php"
        with-gutter
        :starting-line="max(1, $highlightedLine - 5)"
        :highlighted-line="min(5, $highlightedLine - 1)"
        class="overflow-x-auto"
    />
</div>
