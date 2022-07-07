<?php

namespace Illuminate\Console\View\Components;

use Illuminate\Console\Contracts\NewLineAware;
use Symfony\Component\Console\Output\OutputInterface;

class Line extends Component
{
    /**
     * The possible line styles.
     *
     * @var array<string, array<string, string>>
     */
    protected static $styles = [
        'info' => [
            'bgColor' => 'blue',
            'fgColor' => 'white',
            'title' => 'info',
        ],
        'warning' => [
            'bgColor' => 'yellow',
            'fgColor' => 'black',
            'title' => 'warn',
        ],
        'error' => [
            'bgColor' => 'red',
            'fgColor' => 'white',
            'title' => 'error',
        ],
        'raw' => [
            'bgColor' => 'default',
            'fgColor' => 'default',
        ],
    ];

    /**
     * Renders the component using the given arguments.
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @param  string  $string
     * @param  string|null  $style
     * @param  int  $verbosity
     * @return void
     */
    public static function render($output, $string, $style, $verbosity = OutputInterface::VERBOSITY_NORMAL)
    {
        $component = static::fromOutput($output);

        $style = $style ?: 'raw';

        $string = $component->mutate($string, [
            Mutators\EnsureDynamicContentIsHighlighted::class,
            Mutators\EnsurePunctuation::class,
            Mutators\EnsureRelativePaths::class,
        ]);

        $component->renderView('line', array_merge(static::$styles[$style], [
            'marginTop' => ($output instanceof NewLineAware && $output->newLineWritten()) ? 0 : 1,
            'content' => $string,
        ]), $verbosity);
    }
}
