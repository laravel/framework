<script src="//cdn.tailwindcss.com"></script>

<script src="//cdnjs.cloudflare.com/ajax/libs/alpinejs/3.13.8/cdn.js" integrity="sha512-hN6ogT3v4Qd7huxKH/Pg0ZomVLJ1cxvjeZyLfuuq8CgYs+VwrFsbyTE9gHKQEw7gQQNeAZCGumF2XHPdx7BL7A==" crossorigin="anonymous" referrerpolicy="no-referrer" defer ></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js" integrity="sha512-D9gUyxqja7hBtkWpPWGt9wfbfaMGVt9gnyCvYa+jojwwPHLCzUm5i8rpk7vD7wNee9bA35eYIjobYPaQuKS1MQ==" crossorigin="anonymous" referrerpolicy="no-referrer" defer></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/highlightjs-line-numbers.js/2.8.0/highlightjs-line-numbers.min.js" integrity="sha512-axd5V66bnXpNVQzm1c7u1M614TVRXXtouyWCE+eMYl8ALK8ePJEs96Xtx7VVrPBc0UraCn63U1+ARFI3ofW+aA==" crossorigin="anonymous" referrerpolicy="no-referrer" defer></script>

<script defer>
    document.addEventListener('DOMContentLoaded', (event) => {

        document.querySelectorAll('#frame-{{ $exception->defaultFrame() }}' + ' code').forEach((el) => {
            hljs.highlightElement(el)
            hljs.initLineNumbersOnLoad()

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
