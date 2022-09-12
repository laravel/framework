<?php

namespace Illuminate\Console\View\Components;

use Symfony\Component\Console\Output\OutputInterface;

class TwoColumnDetail extends Component
{
    /**
     * The counter of class instances.
     *
     * @var int
     */
    private static $counter = 0;

    /**
     * The list of separators.
     *
     * @var array
     */
    private $separators = ['.', '-'];

    /**
     * Renders the component using the given arguments.
     *
     * @param  string  $first
     * @param  string|null  $second
     * @param  int  $verbosity
     * @return void
     */
    public function render($first, $second = null, $verbosity = OutputInterface::VERBOSITY_NORMAL)
    {
        $first = $this->mutate($first, [
            Mutators\EnsureDynamicContentIsHighlighted::class,
            Mutators\EnsureNoPunctuation::class,
            Mutators\EnsureRelativePaths::class,
        ]);

        $second = $this->mutate($second, [
            Mutators\EnsureDynamicContentIsHighlighted::class,
            Mutators\EnsureNoPunctuation::class,
            Mutators\EnsureRelativePaths::class,
        ]);

        self::$counter++;
        $separator = $this->separators[(self::$counter % 2)];

        $this->renderView('two-column-detail', [
            'first' => $first,
            'second' => $second,
            'separator' => $separator,
        ], $verbosity);
    }
}
