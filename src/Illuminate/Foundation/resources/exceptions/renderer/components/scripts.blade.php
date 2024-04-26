<script src="//unpkg.com/@highlightjs/cdn-assets@11.9.0/highlight.min.js"></script>
<script src="//unpkg.com/highlightjs-line-numbers.js@2.8.0"></script>
<script src="//unpkg.com/alpinejs@3.13.10/dist/cdn.min.js"></script>
<script src="//unpkg.com/@popperjs/core@2"></script>
<script src="//unpkg.com/tippy.js@6"></script>
<script src="//cdn.tailwindcss.com"></script>

<script defer>
    document.addEventListener('DOMContentLoaded', (event) => {
        tippy('[data-tippy-content]', {
            trigger: 'click',
            theme: 'material',
        })

        document.querySelectorAll('#frame-{{ $exception->defaultFrame() }}' + ' code').forEach((el) => {
            hljs.highlightElement(el)
            hljs.highlightAll()

            hljs.initLineNumbersOnLoad()
        })
    })

    tailwind.config = {
        darkMode: 'class',

        theme: {
            extend: {
                fontFamily: {
                    sans: ['Figtree', 'ui-sans-serif', 'system-ui', 'sans-serif', 'Apple Color Emoji', 'Segoe UI Emoji'],
                },
            },
        },
    }
</script>
