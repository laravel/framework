<script src="//cdn.tailwindcss.com"></script>
<script src="//unpkg.com/alpinejs" defer></script>

<script src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
<script src="//cdn.jsdelivr.net/npm/highlightjs-line-numbers.js@2.8.0/dist/highlightjs-line-numbers.min.js"></script>

<script>
    hljs.initLineNumbersOnLoad()

    document.addEventListener('DOMContentLoaded', (event) => {
        document.querySelectorAll('#frame-{{ $exception->defaultFrame() }}' + ' code').forEach((el) => {
            hljs.highlightElement(el)

            hljs.highlightAll()
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
