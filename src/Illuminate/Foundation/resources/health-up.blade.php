<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

        <!-- Styles -->
        <script src="https://cdn.tailwindcss.com"></script>

        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        fontFamily: {
                            sans: ['Figtree', 'ui-sans-serif', 'system-ui', 'sans-serif', "Apple Color Emoji", "Segoe UI Emoji"],
                        }
                    }
                }
            }
        </script>
    </head>
    <body class="antialiased">
        <div class="relative sm:flex sm:justify-center sm:items-center min-h-screen bg-gray-100 selection:bg-red-500 selection:text-white">
            <div class="w-full sm:w-3/4 xl:w-1/2 mx-auto p-6">
                <div class="px-6 py-4 bg-white from-gray-700/50 via-transparent rounded-lg shadow-2xl shadow-gray-500/20 flex items-center focus:outline focus:outline-2 focus:outline-red-500">
                    <div class="relative flex h-3 w-3">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-3 w-3 bg-green-400"></span>
                    </div>

                    <div class="ml-6">
                        <h2 class="text-xl font-semibold text-gray-900">Application up</h2>

                        <p class="mt-2 text-gray-500 dark:text-gray-400 text-sm leading-relaxed">
                            HTTP request received.

                            @if (defined('LARAVEL_START'))
                                Response successfully rendered in {{ round((microtime(true) - LARAVEL_START) * 1000) }}ms.
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
