@props([
    'code',
    'language',
    'withGutter' => false,
    'startingLine' => 1,
    'highlightedLine' => null,
    'truncate' => false,
])

@php
    // $highlightedCode = (new Phiki)->codeToHtml($code, $grammar, ['light' => $lightTheme, 'dark' => $darkTheme])
    //     ->withGutter($withGutter)
    //     ->startingLine($startingLine)
    //     ->decoration(
    //         PreDecoration::make()->class('bg-transparent!', $truncate ? ' truncate' : ''),
    //         GutterDecoration::make()->class('mr-6 text-neutral-500! dark:text-neutral-600!'),
    //     );

    // if ($highlightedLine !== null) {
    //     $highlightedCode->decoration(
    //         LineDecoration::forLine($highlightedLine)->class('bg-rose-200! [&_.line-number]:dark:text-white! dark:bg-rose-900!'),
    //     );
    // }

    $lines = explode("\n", $code);
    $fallback = $truncate ? '<pre class="truncate"><code>' : '<pre><code>';

    if ($withGutter) {
        foreach ($lines as $index => $line) {
            $lineNumber = $startingLine + $index;
            $lineClass = $highlightedLine === $index
                ? 'line bg-rose-200! [&_.line-number]:dark:text-white! dark:bg-rose-900!'
                : 'line';

            $fallback .= '<span class="' . $lineClass . '">';
            $fallback .= '<span class="line-number mr-6 text-neutral-500! dark:text-neutral-600!">' . $lineNumber . '</span>';
            $fallback .= htmlspecialchars($line);
            $fallback .= '</span>';
        }

    } else {
        $fallback .= htmlspecialchars($code);
    }

    $fallback .= '</code></pre>';
@endphp

<div
    x-data="{
        highlightedCode: null,
        async init() {
            // Wait for window.highlight to be available
            while (typeof window.highlight !== 'function') {
                await new Promise(resolve => setTimeout(resolve, 10));
            }
            this.highlightedCode = window.highlight(
                {{ Illuminate\Support\Js::from($code) }},
                {{ Illuminate\Support\Js::from($language) }},
                {{ Illuminate\Support\Js::from($truncate) }},
                {{ Illuminate\Support\Js::from($startingLine) }},
                {{ Illuminate\Support\Js::from($highlightedLine) }}
            );
        }
    }"
    {{ $attributes }}
>
    <div x-cloak x-html="highlightedCode"></div>
    <div x-show="!highlightedCode">{!! $fallback !!}</div>
    {{-- {!! $fallback !!} --}}
</div>
