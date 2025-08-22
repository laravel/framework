@props(['code', 'highlightedLine'])

@use('Phiki\Phiki')
@use('Phiki\Grammar\Grammar')
@use('Phiki\Theme\Theme')

@php
    $code = (new Phiki)->codeToHtml($code, Grammar::Php, Theme::OneDarkPro, withGutter: true, startingLineNumber: $highlightedLine - 5);
@endphp

<div
    class="bg-neutral-50 border-t border-neutral-100 [&_pre]:bg-transparent! [&_.line]:block [&_.line]:px-4 [&_.line]:py-1 [&_.line-number]:mr-6 [&_.line]:even:dark:bg-white/2 [&_.line]:odd:dark:bg-white/4 [&_.line:nth-child(6)]:dark:bg-rose-700/30 dark:bg-neutral-900 dark:border-white/10"
>
    {!! $code !!}
</div>
