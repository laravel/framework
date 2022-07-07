<?php

namespace Illuminate\Console\View\Components;

use Symfony\Component\Console\Output\OutputInterface;

class Alert extends Component
{
    /**
     * Renders the component using the given arguments.
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @param  string  $string
     * @param  int  $verbosity
     * @return void
     */
    public static function render($output, $string, $verbosity = OutputInterface::VERBOSITY_NORMAL)
    {
        $component = static::fromOutput($output);

        $string = $component->mutate($string, [
            Mutators\EnsureDynamicContentIsHighlighted::class,
            Mutators\EnsurePunctuation::class,
            Mutators\EnsureRelativePaths::class,
        ]);

        $component->renderView('alert', [
            'content' => $string,
        ], $verbosity);
    }
}
