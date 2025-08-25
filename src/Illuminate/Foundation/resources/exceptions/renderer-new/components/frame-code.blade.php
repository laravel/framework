@props(['code', 'highlightedLine'])

<div
    class="bg-neutral-50 border-t border-neutral-100 [&_.line]:block [&_.line]:px-4 [&_.line]:py-1 [&_.line-number]:mr-6 [&_.line]:even:dark:bg-white/2 [&_.line]:odd:dark:bg-white/4 dark:bg-neutral-900 dark:border-white/10"
>
    <x-laravel-exceptions-renderer-new::syntax-highlight
        :code="$code"
        grammar="php"
        :with-gutter="true"
        :starting-line="max(1, $highlightedLine - 5)"
        :highlighted-line="min(5, $highlightedLine - 1)"
    />
</div>
