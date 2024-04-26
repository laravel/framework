<script src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/highlightjs-line-numbers.js/2.8.0/highlightjs-line-numbers.min.js"></script>

<script src="//cdnjs.cloudflare.com/ajax/libs/alpinejs/3.13.10/cdn.min.js" defer></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.8/umd/popper.min.js" defer></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/tippy.js/6.3.7/tippy.umd.min.js" defer></script>

<script>
    document.addEventListener('DOMContentLoaded', (event) => {
        document.querySelectorAll('#frame-{{ $exception->defaultFrame() }}' + ' code').forEach((el) => {
            hljs.highlightElement(el)
            hljs.highlightAll()

            hljs.initLineNumbersOnLoad()
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

        tippy('[data-tippy-content]', {
            trigger: 'click',
            theme: 'material',
        })
    })
</script>
