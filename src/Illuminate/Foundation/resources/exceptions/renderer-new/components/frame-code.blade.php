@props(['code', 'highlightedLine'])

@use('Phiki\Adapters\Laravel\Facades\Phiki')
@use('Phiki\Grammar\Grammar')
@use('Phiki\Theme\Theme')
@use('Phiki\Transformers\Decorations\LineDecoration')

@php
    $code = Phiki::codeToHtml($code, Grammar::Php, Theme::OneDarkPro)
        ->withGutter()
        ->startingLine($highlightedLine - 5)
        ->decoration(
            LineDecoration::forLine(5)->class('dark:bg-rose-700/30!'),
        );
@endphp

<div
    class="bg-neutral-50 border-t border-neutral-100 [&_pre]:bg-transparent! [&_.line]:block [&_.line]:px-4 [&_.line]:py-1 [&_.line-number]:mr-6 [&_.line]:even:dark:bg-white/2 [&_.line]:odd:dark:bg-white/4 dark:bg-neutral-900 dark:border-white/10"
>
    {!! $code !!}
</div>
