<?php

namespace Illuminate\Console\View\Components;

use Symfony\Component\Console\Output\OutputInterface;

class TwoColumnDetail extends Component
{
    /**
     * Renders the component using the given arguments.
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @param  string  $left
     * @param  string|null  $right
     * @param  int  $verbosity
     * @return void
     */
    public static function render($output, $left, $right = null, $verbosity = OutputInterface::VERBOSITY_NORMAL)
    {
        $component = static::fromOutput($output);

        $left = $component->mutate($left, [
            Mutators\EnsureDynamicContentIsHighlighted::class,
            Mutators\EnsureNoPunctuation::class,
            Mutators\EnsureRelativePaths::class,
        ]);

        $right = $component->mutate($right, [
            Mutators\EnsureDynamicContentIsHighlighted::class,
            Mutators\EnsureNoPunctuation::class,
            Mutators\EnsureRelativePaths::class,
        ]);

        $component->renderView('two-column-detail', [
            'left' => $left,
            'right' => $right,
        ], $verbosity);
    }
}
