<script>
    (function () {
        setDarkClass = () => {
            const isDark = localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)

            if (isDark) {
                document.documentElement.setAttribute("data-theme", "dark");
            } else {
                document.documentElement.removeAttribute("data-theme");
            }
        }

        setDarkClass()

        console.log('Current theme:', localStorage.theme)

        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', setDarkClass)
    })();
</script>

<div
    x-data="{
        theme: localStorage.theme,
        darkMode() {
            this.theme = 'dark'
            localStorage.theme = 'dark'
            setDarkClass()
        },
        lightMode() {
            this.theme = 'light'
            localStorage.theme = 'light'
            setDarkClass()
        },
        systemMode() {
            this.theme = undefined
            localStorage.removeItem('theme')
            setDarkClass()
        },
        toggleTheme() {
            switch (this.theme) {
                case 'dark':
                    this.lightMode()
                    break
                case 'light':
                    this.systemMode()
                    break
                default:
                    this.darkMode()
                    break
            }
        },
    }"
>

    <button
        x-cloak
        @class([
                "rounded-md w-8 h-8 flex flex-shrink-0 items-center justify-center cursor-pointer border transition-colors duration-200 ease-in-out",
                "bg-white/5 border-neutral-200 hover:bg-neutral-100 dark:bg-white/5 dark:border-white/10 dark:hover:bg-white/10 text-neutral-600 dark:text-neutral-400",
            ])
        @click="toggleTheme()"
    >
        <x-laravel-exceptions-renderer::icons.computer-desktop class="w-4 h-4" x-show="!theme" />
        <x-laravel-exceptions-renderer::icons.sun class="w-4 h-4" x-show="theme === 'light'" />
        <x-laravel-exceptions-renderer::icons.moon class="w-4 h-4" x-show="theme === 'dark'" />
    </button>
</div>
