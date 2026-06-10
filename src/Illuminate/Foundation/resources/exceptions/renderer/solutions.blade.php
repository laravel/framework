@use('Illuminate\Foundation\Exceptions\Renderer\Solutions\Contracts\RunnableSolution')

@if (! empty($solutions))
    <section class="w-full max-w-7xl mx-auto p-4 sm:p-14 border-x border-dashed border-neutral-300 dark:border-white/[9%] flex flex-col gap-2.5 pt-8">
        <div class="bg-neutral-50 dark:bg-white/1 border border-neutral-200 dark:border-neutral-800 rounded-xl p-2.5 shadow-xs flex flex-col gap-2.5">
            <div class="flex items-center gap-2.5 p-2">
                <div class="bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-white/5 rounded-md w-6 h-6 flex items-center justify-center p-1">
                    <svg class="w-3 h-3 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
                    </svg>
                </div>
                <h3 class="text-base font-semibold text-neutral-900 dark:text-white">Suggested solutions</h3>
            </div>

            <div class="flex flex-col gap-1.5">
                @foreach ($solutions as $index => $solution)
                    <div class="rounded-lg bg-white dark:bg-white/3 border border-neutral-200 dark:border-white/10 shadow-xs overflow-hidden">
                        <div class="flex items-center justify-between gap-4 p-4">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-neutral-900 dark:text-white">
                                    {{ $solution->title() }}
                                </p>
                                @if ($solution->description())
                                    <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">{{ $solution->description() }}</p>
                                @endif
                            </div>

                            @if ($solution instanceof RunnableSolution)
                                <button
                                    type="button"
                                    onclick="runSolution(this, {{ json_encode($solution->command()) }}, {{ json_encode($solution->commandArguments()) }})"
                                    class="shrink-0 text-sm rounded-md border px-3 h-8 flex items-center gap-2 transition-colors duration-200 ease-in-out cursor-pointer shadow-xs text-neutral-600 dark:text-neutral-400 bg-white/5 border-neutral-200 hover:bg-neutral-100 dark:bg-white/5 dark:border-white/10 dark:hover:bg-white/10"
                                >
                                    <svg class="w-3 h-3 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" />
                                    </svg>
                                    Run
                                </button>
                            @endif
                        </div>

                        @if ($solution instanceof RunnableSolution)
                            <div class="hidden p-4" data-solution-output="{{ $index }}">
                                <pre class="text-xs font-mono p-3 pb-6 rounded-md bg-neutral-950 text-neutral-200 overflow-x-auto max-h-48 overflow-y-auto border border-neutral-800"></pre>
                            </div>
                        @endif

                        @if (! empty($solution->links()))
                            <div class="px-4 pb-4 flex flex-wrap gap-3">
                                @foreach ($solution->links() as $label => $url)
                                    <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-xs text-neutral-500 dark:text-neutral-400 hover:text-blue-500 dark:hover:text-emerald-500 transition-colors">
                                        {{ $label }}
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                        </svg>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <style>
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>

    <script>
        async function runSolution(button, command, args) {
            var card = button.closest('[class*="rounded-lg"]');
            var outputContainer = card.querySelector('[data-solution-output]');
            var pre = outputContainer ? outputContainer.querySelector('pre') : null;

            button.disabled = true;
            button.innerHTML = '<svg class="w-3 h-3" style="animation: spin 1s linear infinite" fill="none" viewBox="0 0 24 24" stroke-width="2.5"><circle cx="12" cy="12" r="10" stroke="#e5e7eb" /><path stroke="#10b981" stroke-linecap="round" d="M12 2a10 10 0 0 1 10 10" /></svg> Running\u2026';

            try {
                var response = await fetch('/_error-solutions/run', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ command: command, arguments: args }),
                });

                var data = await response.json();

                if (outputContainer && pre) {
                    outputContainer.classList.remove('hidden');
                    pre.textContent = data.output || '(no output)';
                }

                if (data.success) {
                    button.innerHTML = '<svg class="w-3 h-3 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg> Done';

                    setTimeout(function() {
                        button.innerHTML = '<svg class="w-3 h-3 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.992 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg> Reload';
                        button.disabled = false;
                        button.onclick = function() { window.location.reload(); };
                    }, 800);
                } else {
                    button.innerHTML = '<svg class="w-3 h-3 text-rose-500" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg> Failed';
                    button.disabled = false;
                }
            } catch (e) {
                button.innerHTML = 'Error';
                button.disabled = false;
            }
        }
    </script>
@endif
