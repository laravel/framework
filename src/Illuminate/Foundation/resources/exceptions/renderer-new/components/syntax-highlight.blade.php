@props([
    'code',
    'grammar',
    'lightTheme' => 'light-plus',
    'darkTheme' => 'dark-plus',
    'withGutter' => false,
    'startingLine' => 1,
    'highlightedLine' => null,
    'truncate' => false,
])

@use('Phiki\Phiki')
@use('Phiki\Grammar\Grammar')
@use('Phiki\Theme\Theme')
@use('Phiki\Transformers\Decorations\GutterDecoration')
@use('Phiki\Transformers\Decorations\LineDecoration')
@use('Phiki\Transformers\Decorations\PreDecoration')

@php
    $highlightedCode = (new Phiki)->codeToHtml($code, $grammar, ['light' => $lightTheme, 'dark' => $darkTheme])
        ->withGutter($withGutter)
        ->startingLine($startingLine)
        ->decoration(
            PreDecoration::make()->class('bg-transparent!', $truncate ? ' truncate' : ''),
            GutterDecoration::make()->class('mr-6 text-neutral-500! dark:text-neutral-600!'),
        );

    if ($highlightedLine !== null) {
        $highlightedCode->decoration(
            LineDecoration::forLine($highlightedLine)->class('bg-rose-200! [&_.line-number]:dark:text-white! dark:bg-rose-900!'),
        );
    }
@endphp

<div
    {{ $attributes }}
>
    {!! $highlightedCode !!}
</div>
