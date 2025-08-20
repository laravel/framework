@props(['headers'])

<div class="flex flex-col gap-3">
    <h2 class="text-lg font-semibold">Headers</h2>
    <div class="flex flex-col gap-5">
        <div class="flex flex-col">
            @foreach ($headers as $key => $value)
            <div class="flex items-center gap-2 h-10">
                <div class="text-sm font-mono text-neutral-400">{{ $key }}</div>
                <div class="flex-1 h-3 border-b-2 border-dotted border-white/20"></div>
                <div class="text-sm font-mono max-w-[772px] overflow-hidden text-ellipsis">{{ $value }}</div>
            </div>
            @endforeach
        </div>
    </div>
</div>
