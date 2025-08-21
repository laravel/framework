@props(['code', 'highlightedLine'])

<div class="px-5 py-3 bg-neutral-50 border-t border-neutral-100 dark:bg-neutral-900 dark:border-white/10">
    <pre class="text-xs lg:text-sm"><code class="max-h-32 overflow-y-hidden overflow-x-scroll scrollbar-hidden">{{ $code }}</code></pre>
</div>
