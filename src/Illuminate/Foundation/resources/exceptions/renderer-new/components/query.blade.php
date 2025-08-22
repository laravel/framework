@props(['queries'])

@use('Phiki\Phiki')
@use('Phiki\Grammar\Grammar')
@use('Phiki\Theme\Theme')

@php
    function highlight(string $sql) {
        return (new Phiki)->codeToHtml($sql, Grammar::Sql, Theme::OneDarkPro);
    }
@endphp

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
        <div class="bg-white/[0.03] rounded-md h-10 flex items-center gap-8 px-4">
            <div class="flex items-center gap-4 flex-1">
                <div class="flex items-center gap-2">
                    <svg class="w-3 h-3 text-neutral-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" />
                    </svg>
                    <span class="text-xs font-mono text-neutral-400">{{ $connectionName }}</span>
                </div>
                <div class="text-xs font-mono text-neutral-200 overflow-hidden text-ellipsis [&_pre]:bg-transparent!">
                    {!! highlight($sql) !!}
                </div>
            </div>
            <div class="text-xs font-mono text-neutral-200 w-[65px] text-right">{{ $time }}ms</div>
        </div>
        @empty
        <x-laravel-exceptions-renderer-new::empty-state message="No queries executed" />
        @endforelse
    </div>
</div>
