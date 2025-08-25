@props(['code', 'grammar', 'theme' => 'one-dark-pro', 'withGutter' => false, 'startingLine' => 1, 'highlightedLine' => null])

@use('Phiki\Phiki')
@use('Phiki\Grammar\Grammar')
@use('Phiki\Theme\Theme')
@use('Phiki\Transformers\Decorations\LineDecoration')

@php
    $highlightedCode = (new Phiki)->codeToHtml($code, $grammar, $theme)
        ->withGutter($withGutter)
        ->startingLine($startingLine);

    if ($highlightedLine) {
        $highlightedCode->decoration(
            LineDecoration::forLine($highlightedLine)->class('dark:bg-rose-700/30!'),
        );
    }
@endphp

<div
    {{ $attributes->merge(['class' => '[&_pre]:bg-transparent!']) }}
>
    {!! $highlightedCode !!}
</div>
