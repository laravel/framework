<div class="relative" x-data="{
    menu: false
}" @click.outside="menu = false">
    <button x-cloak
        class="relative border-l border-gray-200/80 dark:border-gray-950/95 rounded-r-full bg-white p-2 dark:bg-gray-800 sm:col-span-1 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer transition-colors duration-200 ease-in-out"
        @click="menu = ! menu">
        <x-laravel-exceptions-renderer::icons.chevron-down class="h-5 w-5" />
    </button>

    <div x-show="menu"
        class="absolute mt-1 right-0 z-10 flex origin-top-right flex-col rounded-md bg-white shadow-xl ring-1 ring-gray-900/5 dark:bg-gray-800"
        style="display: none" @click="menu = false">
        <a href="https://chatgpt.com/?hints=search&q={{ $uriEncodedExceptionMarkdown }}" target="_blank"
            rel="noopener noreferrer"
            class="flex items-center gap-3 px-4 py-2 whitespace-nowrap hover:rounded-t-md hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-900 dark:text-gray-100">
            <x-laravel-exceptions-renderer::icons.chatgpt-logo class="h-5 w-5" />
            Ask ChatGPT
        </a>
        <a href="https://claude.ai/new?q={{ $uriEncodedExceptionMarkdown }}" target="_blank" rel="noopener noreferrer"
            class="flex items-center gap-3 px-4 py-2 whitespace-nowrap hover:rounded-b-md hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-900 dark:text-gray-100">
            <x-laravel-exceptions-renderer::icons.claude-logo class="h-5 w-5" />
            Ask Claude
        </a>
    </div>
</div>
