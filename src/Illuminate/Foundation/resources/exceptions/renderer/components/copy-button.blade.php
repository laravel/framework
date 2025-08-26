<script>
    let exceptionAsMarkdown = {{ Illuminate\Support\Js::from($markdown) }}
</script>
<div
    class="relative inline-block text-sm rounded-full bg-white px-3 py-2 dark:bg-gray-800 sm:col-span-1 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer transition-colors duration-200 ease-in-out"
    x-data="{
        copied: false,
        copy() {
            const textarea = document.createElement('textarea');
            textarea.value = exceptionAsMarkdown;
            textarea.style.position = 'absolute';
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);

            this.copied = true;
            setTimeout(() => this.copied = false, 1000);
        }
    }"
    @click="copy"
>
    <span x-text="copied ? 'Copied to clipboard' : 'Copy as Markdown'"></span>
</div>
