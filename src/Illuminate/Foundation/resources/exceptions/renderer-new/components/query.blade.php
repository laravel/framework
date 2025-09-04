@props(['queries'])

<div
    {{ $attributes->merge(['class' => "flex flex-col gap-1 bg-white/[1%] border border-neutral-800 rounded-xl p-[10px]"]) }}
    x-data="{
        totalQueries: {{ count($queries) }},
        currentPage: 1,
        perPage: 10,
        get totalPages() {
            return Math.ceil(this.totalQueries / this.perPage);
        },
        get hasPrevious() {
            return this.currentPage > 1;
        },
        get hasNext() {
            return this.currentPage < this.totalPages;
        },
        goToPage(page) {
            if (page >= 1 && page <= this.totalPages) {
                this.currentPage = page;
            }
        },
        first() {
            this.currentPage = 1;
        },
        last() {
            this.currentPage = this.totalPages;
        },
        previous() {
            if (this.hasPrevious) {
                this.currentPage--;
            }
        },
        next() {
            if (this.hasNext) {
                this.currentPage++;
            }
        },
        get visiblePages() {
            const total = this.totalPages;
            const current = this.currentPage;
            const pages = [];

            if (total <= 7) {
                for (let i = 1; i <= total; i++) {
                    pages.push({ type: 'page', value: i });
                }
            } else {
                if (current <= 4) {
                    for (let i = 1; i <= 5; i++) {
                        pages.push({ type: 'page', value: i });
                    }
                    if (total > 6) {
                        pages.push({ type: 'ellipsis', value: '...', id: 'end' });
                        pages.push({ type: 'page', value: total });
                    }
                } else if (current > total - 4) {
                    pages.push({ type: 'page', value: 1 });
                    if (total > 6) {
                        pages.push({ type: 'ellipsis', value: '...', id: 'start' });
                    }
                    for (let i = Math.max(total - 4, 2); i <= total; i++) {
                        pages.push({ type: 'page', value: i });
                    }
                } else {
                    pages.push({ type: 'page', value: 1 });
                    pages.push({ type: 'ellipsis', value: '...', id: 'start' });
                    for (let i = current - 1; i <= current + 1; i++) {
                        pages.push({ type: 'page', value: i });
                    }
                    pages.push({ type: 'ellipsis', value: '...', id: 'end' });
                    pages.push({ type: 'page', value: total });
                }
            }
            return pages;
        }
    }"
>
    <div class="flex items-center justify-between p-2">
        <div class="flex items-center gap-2.5">
            <div class="bg-neutral-800 rounded-md w-6 h-6 flex items-center justify-center p-1">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" />
                </svg>
            </div>
            <h3 class="text-base font-semibold">Queries</h3>
        </div>
        <div x-show="totalQueries > 0" class="text-sm text-neutral-400">
            <span x-text="`${((currentPage - 1) * perPage) + 1}-${Math.min(currentPage * perPage, totalQueries)} of ${totalQueries}`"></span>
        </div>
    </div>

    <div class="flex flex-col gap-1">
        @forelse ($queries as $index => ['connectionName' => $connectionName, 'sql' => $sql, 'time' => $time])
        <div
            class="bg-white/[0.03] rounded-md h-10 flex items-center gap-4 px-4 text-xs font-mono"
            x-show="Math.floor({{ $index }} / perPage) === (currentPage - 1)"
        >
            <div class="flex items-center gap-2 shrink-0">
                <svg class="w-3 h-3 text-neutral-400" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" />
                </svg>
                <span class="text-neutral-400">{{ $connectionName }}</span>
            </div>
            <div class="min-w-0 flex-1">
                <x-laravel-exceptions-renderer-new::tooltip side="left">
                    <x-slot:trigger>
                        <x-laravel-exceptions-renderer-new::syntax-highlight :code="$sql" grammar="sql" truncate />
                    </x-slot>

                    <span>{{ $sql }}</span>
                </x-laravel-exceptions-renderer-new::tooltip>
            </div>
            <div class="text-neutral-200 text-right flex-shrink-0">{{ $time }}ms</div>
        </div>
        @empty
        <x-laravel-exceptions-renderer-new::empty-state message="No queries executed" />
        @endforelse
    </div>

    <!-- Pagination Controls -->
    <div x-cloak x-show="totalPages > 1" class="flex items-center justify-center gap-1 py-4 font-mono">
        <!-- First Button -->
        <button
            @click="first()"
            :disabled="!hasPrevious"
            :class="hasPrevious ? 'text-neutral-300 hover:text-white hover:bg-white/[0.05]' : 'text-neutral-600 cursor-not-allowed'"
            class="flex items-center justify-center w-8 h-8 rounded-md transition-colors"
        >
            <x-laravel-exceptions-renderer-new::icons.chevrons-left class="w-3 h-3" />
        </button>

        <!-- Previous Button -->
        <button
            @click="previous()"
            :disabled="!hasPrevious"
            :class="hasPrevious ? 'text-neutral-300 hover:text-white hover:bg-white/[0.05]' : 'text-neutral-600 cursor-not-allowed'"
            class="flex items-center justify-center w-8 h-8 rounded-md transition-colors"
        >
            <x-laravel-exceptions-renderer-new::icons.chevron-left class="w-3 h-3" />
        </button>

        <!-- Page Numbers -->
        <template x-for="(page, index) in visiblePages" :key="`page-${page.type}-${page.value}-${page.id || index}`">
            <div>
                <template x-if="page.type === 'ellipsis'">
                    <span class="flex items-center justify-center w-8 h-8 text-neutral-500">...</span>
                </template>
                <template x-if="page.type === 'page'">
                    <button
                        @click="goToPage(page.value)"
                        :class="currentPage === page.value ? 'bg-blue-600 text-white' : 'text-neutral-300 hover:text-white hover:bg-white/[0.05]'"
                        class="flex items-center justify-center w-8 h-8 rounded-md text-sm font-medium transition-colors"
                        x-text="page.value"
                    ></button>
                </template>
            </div>
        </template>

        <!-- Next Button -->
        <button
            @click="next()"
            :disabled="!hasNext"
            :class="hasNext ? 'text-neutral-300 hover:text-white hover:bg-white/[0.05]' : 'text-neutral-600 cursor-not-allowed'"
            class="flex items-center justify-center w-8 h-8 rounded-md transition-colors"
        >
            <x-laravel-exceptions-renderer-new::icons.chevron-right class="w-3 h-3" />
        </button>

        <!-- Last Button -->
        <button
            @click="last()"
            :disabled="!hasNext"
            :class="hasNext ? 'text-neutral-300 hover:text-white hover:bg-white/[0.05]' : 'text-neutral-600 cursor-not-allowed'"
            class="flex items-center justify-center w-8 h-8 rounded-md transition-colors"
        >
            <x-laravel-exceptions-renderer-new::icons.chevrons-right class="w-3 h-3" />
        </button>
    </div>
</div>
