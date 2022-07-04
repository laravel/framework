<?php

namespace Illuminate\Console\View\Components;

use Symfony\Component\Console\Output\OutputInterface;
use function Termwind\render;
use function Termwind\renderUsing;

class Detail
{
    use Concerns\EnsureNoPunctuation,
        Concerns\EnsureRelativePaths,
        Concerns\Highlightable;

    /**
     * Renders the component using the given arguments.
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @param  string  $left
     * @param  string|null  $right
     * @param  int  $verbosity
     * @return void
     */
    public static function renderUsing($output, $left, $right = null, $verbosity = OutputInterface::VERBOSITY_NORMAL)
    {
        renderUsing($output);

        $left = self::highlightDynamicContent($left);
        $left = self::ensureNoPunctuation($left);
        $left = self::ensureRelativePaths($left);

        $right = self::highlightDynamicContent($right);
        $right = self::ensureNoPunctuation($right);
        $right = self::ensureRelativePaths($right);

        render(view('illuminate.console::detail', [
            'left' => $left,
            'right' => $right,
        ]), $verbosity);
    }
}
