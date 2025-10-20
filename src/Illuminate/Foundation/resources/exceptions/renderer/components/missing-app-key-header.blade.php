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

    <div class="bg-blue-50 dark:bg-blue-950/50 border border-blue-200 dark:border-blue-800 rounded-lg p-6 mb-8">
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0">
                <x-laravel-exceptions-renderer::icons.info class="w-6 h-6 text-blue-600 dark:text-blue-400" />
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-200 mb-2">
                    Application Key Missing
                </h3>
                <p class="text-sm text-blue-800 dark:text-blue-300 mb-4">
                    Your application needs an encryption key to function properly. You can generate one automatically by clicking the button below or run the command manually.
                </p>
                <div class="flex flex-wrap gap-3">
                    <button
                        type="button"
                        onclick="generateAppKey()"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                        id="generate-key-btn"
                        aria-label="Generate application key"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        <span>Generate Application Key</span>
                    </button>
                    <button
                        type="button"
                        onclick="copyCommand()"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-white hover:bg-neutral-50 dark:bg-neutral-800 dark:hover:bg-neutral-700 border border-neutral-300 dark:border-neutral-700 text-neutral-900 dark:text-neutral-100 text-sm font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-neutral-500 focus:ring-offset-2"
                        aria-label="Copy command to clipboard"
                    >
                        <x-laravel-exceptions-renderer::icons.copy class="w-4 h-4" aria-hidden="true" />
                        <span>Copy Command</span>
                    </button>
                </div>
                <div id="status-message" class="mt-3 text-sm hidden" role="status" aria-live="polite"></div>
            </div>
        </div>
    </div>

    <x-laravel-exceptions-renderer::request-url :$exception :request="$exception->request()" class="relative z-50" />
</div>

<script>
function generateAppKey() {
    const btn = document.getElementById('generate-key-btn');
    const statusDiv = document.getElementById('status-message');
    const csrfToken = document.querySelector('meta[name="csrf-token"]');

    if (!csrfToken) {
        showStatus('CSRF token not found. Please refresh the page.', 'error');
        return;
    }

    // Disable button and show loading state
    btn.disabled = true;
    btn.innerHTML = `
        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span>Generating...</span>
    `;

    // Clear previous status
    statusDiv.className = 'mt-3 text-sm hidden';

    fetch('/__laravel_generate_key', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
            'Accept': 'application/json'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showStatus('Application key generated successfully! Reloading...', 'success');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            throw new Error(data.message || 'Failed to generate key');
        }
    })
    .catch(error => {
        showStatus(error.message, 'error');
        resetButton();
    });
}

function copyCommand() {
    const command = 'php artisan key:generate';
    const statusDiv = document.getElementById('status-message');

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(command)
            .then(() => {
                showStatus('Command copied to clipboard!', 'success');
                setTimeout(() => {
                    statusDiv.className = 'mt-3 text-sm hidden';
                }, 2000);
            })
            .catch(() => {
                showStatus('Failed to copy command. Please copy manually: ' + command, 'error');
            });
    } else {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = command;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        document.body.appendChild(textArea);
        textArea.select();

        try {
            document.execCommand('copy');
            showStatus('Command copied to clipboard!', 'success');
            setTimeout(() => {
                statusDiv.className = 'mt-3 text-sm hidden';
            }, 2000);
        } catch (err) {
            showStatus('Failed to copy command. Please copy manually: ' + command, 'error');
        } finally {
            document.body.removeChild(textArea);
        }
    }
}

function showStatus(message, type) {
    const statusDiv = document.getElementById('status-message');
    const colorClass = type === 'success'
        ? 'text-emerald-600 dark:text-emerald-400'
        : 'text-rose-600 dark:text-rose-400';

    statusDiv.className = `mt-3 text-sm ${colorClass}`;
    statusDiv.textContent = message;
}

function resetButton() {
    const btn = document.getElementById('generate-key-btn');
    btn.disabled = false;
    btn.innerHTML = `
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        <span>Generate Application Key</span>
    `;
}
</script>
