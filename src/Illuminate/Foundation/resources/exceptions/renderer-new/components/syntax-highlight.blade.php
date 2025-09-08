@props([
    'code',
    'grammar',
    'theme' => 'dark-plus',
    'withGutter' => false,
    'startingLine' => 1,
    'highlightedLine' => null,
    'truncate' => false,
])

@use('Phiki\Phiki')
@use('Phiki\Grammar\Grammar')
@use('Phiki\Theme\Theme')
@use('Phiki\Transformers\Decorations\LineDecoration')
@use('Phiki\Transformers\Decorations\PreDecoration')

@php
    $highlightedCode = (new Phiki)->codeToHtml($code, $grammar, $theme)
        ->withGutter($withGutter)
        ->startingLine($startingLine)
        ->decoration(
            PreDecoration::make()->class('bg-transparent!', $truncate ? ' truncate' : ''),
        );

    if ($highlightedLine !== null) {
        $highlightedCode->decoration(
            LineDecoration::forLine($highlightedLine)->class('dark:bg-rose-700/30!'),
        );
    }
@endphp

<div
    {{ $attributes }}
>
    {!! $highlightedCode !!}
</div>
