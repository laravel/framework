@props([
    'code',
    'language',
    'editor' => false,
    'startingLine' => 1,
    'highlightedLine' => null,
    'truncate' => false,
])

@use('Illuminate\Support\Js')

@php
    $fallback = $truncate ? '<pre class="truncate"><code>' : '<pre><code>';

    if ($editor) {
        $lines = explode("\n", $code);

        foreach ($lines as $index => $line) {
            $lineNumber = $startingLine + $index;
            $highlight = $highlightedLine === $index;
            $lineClass = implode(' ', [
                'block px-4 py-1 h-7 even:bg-white odd:bg-white/2 even:dark:bg-white/2 odd:dark:bg-white/4',
                $highlight ? 'bg-rose-200! dark:bg-rose-900!' : '',
            ]);
            $lineNumberClass = implode(' ', [
                'mr-6 text-neutral-500! dark:text-neutral-600!',
                $highlight ? 'dark:text-white!' : '',
            ]);

            $fallback .= '<span class="' . $lineClass . '">';
            $fallback .= '<span class="' . $lineNumberClass . '">' . $lineNumber . '</span>';
            $fallback .= htmlspecialchars($line);
            $fallback .= '</span>';
        }

    } else {
        $fallback .= htmlspecialchars($code);
    }

    $fallback .= '</code></pre>';
@endphp

<div
    x-data="{ highlightedCode: null }"
    x-init="
        highlightedCode = window.highlight(
            {{ Js::from($code) }},
            {{ Js::from($language) }},
            {{ Js::from($truncate) }},
            {{ Js::from($editor) }},
            {{ Js::from($startingLine) }},
            {{ Js::from($highlightedLine) }}
        );
    "
    {{ $attributes }}
>
    <div
        x-cloak
        x-html="highlightedCode"
    ></div>
    <div x-show="!highlightedCode">{!! $fallback !!}</div>
</div>
