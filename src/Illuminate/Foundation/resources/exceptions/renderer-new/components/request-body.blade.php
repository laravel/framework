@props(['body'])

<div class="flex flex-col gap-3">
    <h2 class="text-lg font-semibold">Body</h2>
    @if($body)
    <div class="bg-white/[0.02] border border-white/5 rounded-md shadow-[0px_16px_32px_-8px_rgba(12,12,13,0.4)] overflow-hidden p-5 text-sm font-mono [&_pre]:bg-transparent!">
        <x-phiki::code grammar="json" theme="one-dark-pro">{!! $body !!}</x-phiki::code>
    </div>
    @else
    <x-laravel-exceptions-renderer-new::empty-state message="No request body" />
    @endif
</div>
