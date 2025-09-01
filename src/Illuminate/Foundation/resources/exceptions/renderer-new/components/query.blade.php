@props(['queries'])

<div
    {{ $attributes->merge(['class' => "flex flex-col gap-1 bg-white/[0.01] border border-neutral-800 rounded-xl p-[10px]"]) }}
>
    <div class="flex items-center gap-2.5 p-2">
        <div class="bg-neutral-800 rounded-md w-6 h-6 flex items-center justify-center p-1">
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" />
            </svg>
        </div>
        <h3 class="text-base font-semibold">Queries</h3>
    </div>

    <div class="flex flex-col gap-1">
        @forelse ($queries as ['connectionName' => $connectionName, 'sql' => $sql, 'time' => $time])
        <div class="bg-white/[0.03] rounded-md h-10 flex items-center gap-4 px-4 text-xs font-mono">
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
</div>
