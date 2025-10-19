@props(['exception'])

<div class="flex flex-col pt-8 sm:pt-16 overflow-x-auto">
    <div class="flex flex-col gap-5 mb-8">
        <h1 class="text-3xl font-semibold text-neutral-950 dark:text-white">{{ $exception->class() }}</h1>
        <x-laravel-exceptions-renderer::file-with-line :frame="$exception->frames()->first()" class="-mt-3 text-xs" />
        <p class="text-xl font-light text-neutral-800 dark:text-neutral-300">
            {{ $exception->message() }}
        </p>
    </div>

    <div class="flex items-start gap-2 mb-8 sm:mb-16">
        <div class="bg-white dark:bg-white/[3%] border border-neutral-200 dark:border-white/10 divide-x divide-neutral-200 dark:divide-white/10 rounded-md shadow-xs flex items-center gap-0.5">
            <div class="flex items-center gap-1.5 h-6 px-[6px] font-mono text-[13px]">
                <span class="text-neutral-400 dark:text-neutral-500">LARAVEL</span>
                <span class="text-neutral-500 dark:text-neutral-300">{{ app()->version() }}</span>
            </div>
            <div class="flex items-center gap-1.5 h-6 px-[6px] font-mono text-[13px]">
                <span class="text-neutral-400 dark:text-neutral-500">PHP</span>
                <span class="text-neutral-500 dark:text-neutral-300">{{ PHP_VERSION }}</span>
            </div>
        </div>
        <x-laravel-exceptions-renderer::badge type="error">
            <x-laravel-exceptions-renderer::icons.alert class="w-2.5 h-2.5" />
            UNHANDLED
        </x-laravel-exceptions-renderer::badge>
        <x-laravel-exceptions-renderer::badge type="error" variant="solid">
            CODE {{ $exception->code() }}
        </x-laravel-exceptions-renderer::badge>
    </div>

    <!-- Custom section for MissingAppKeyException -->
    <div class="bg-blue-100 dark:bg-blue-950 dark:border-blue-800 border border-blue-200 rounded-md p-6 mb-8">
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0">
                <x-laravel-exceptions-renderer::icons.info class="w-6 h-6 text-blue-600 dark:text-blue-400" />
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-300 mb-2">
                    Application Key Missing
                </h3>
                <p class="text-blue-800 dark:text-blue-200 mb-4">
                    Your application needs an encryption key to function properly. You can generate one automatically by clicking the button below.
                </p>
                <div class="flex gap-3">
                    <button 
                        onclick="generateAppKey()" 
                        class="inline-flex items-center gap-2 px-4 py-2 bg-blue-700 dark:border-blue-600 dark:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 hover:bg-blue-800 dark:hover:bg-blue-600"
                        id="generate-key-btn"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Generate Application Key
                    </button>
                    <button 
                        onclick="copyCommand()" 
                        class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-100 text-neutral-900 text-sm font-medium rounded-md transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-neutral-500 focus:ring-offset-2 hover:bg-neutral-50 dark:hover:bg-neutral-700 border border-neutral-200"
                    >
                        <x-laravel-exceptions-renderer::icons.copy class="w-4 h-4" />
                        Copy Command
                    </button>
                </div>
                <div id="status-message" class="mt-3 text-sm hidden"></div>
            </div>
        </div>
    </div>

    <x-laravel-exceptions-renderer::request-url :$exception :request="$exception->request()" class="relative z-50" />
</div>

<script>
function generateAppKey() {
    const btn = document.getElementById('generate-key-btn');
    const statusDiv = document.getElementById('status-message');
    
    // Disable button and show loading state
    btn.disabled = true;
    btn.innerHTML = `
        <svg class="animate-spin w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
        </svg>
        Generating...
    `;
    
    // Clear previous status
    statusDiv.className = 'mt-3 text-sm hidden';
    
    fetch('/__laravel_generate_key', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            statusDiv.className = 'mt-3 text-sm text-emerald-600 dark:text-emerald-400';
            statusDiv.textContent = 'Application key generated successfully! The page will reload in 2 seconds...';
            
            // Reload page after 2 seconds
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            throw new Error(data.message || 'Failed to generate key');
        }
    })
    .catch(error => {
        statusDiv.className = 'mt-3 text-sm text-rose-600 dark:text-rose-400';
        statusDiv.textContent = 'Error: ' + error.message;
        
        // Reset button
        btn.disabled = false;
        btn.innerHTML = `
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Generate Application Key
        `;
    });
}

function copyCommand() {
    const command = 'php artisan key:generate';
    navigator.clipboard.writeText(command).then(() => {
        const statusDiv = document.getElementById('status-message');
        statusDiv.className = 'mt-3 text-sm text-emerald-600 dark:text-emerald-400';
        statusDiv.textContent = 'Command copied to clipboard!';
        
        setTimeout(() => {
            statusDiv.className = 'mt-3 text-sm hidden';
        }, 3000);
    }).catch(() => {
        const statusDiv = document.getElementById('status-message');
        statusDiv.className = 'mt-3 text-sm text-rose-600 dark:text-rose-400';
        statusDiv.textContent = 'Failed to copy command to clipboard';
        
        setTimeout(() => {
            statusDiv.className = 'mt-3 text-sm hidden';
        }, 3000);
    });
}
</script>