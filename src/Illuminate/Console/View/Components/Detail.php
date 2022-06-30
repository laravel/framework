<?php

namespace Illuminate\Console\View\Components;

use function Termwind\render;
use function Termwind\renderUsing;
use Symfony\Component\Console\Output\OutputInterface;

class Detail
{
    use Concerns\Highlightable;

    /**
     * Renders the component using the given arguments.
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @param  string  $left
     * @param  string|null  $right
     * @param  int  $verbosity
     * @return void
     */
    public static function renderUsing($output, $left, $right, $verbosity = OutputInterface::VERBOSITY_NORMAL)
    {
        // if ($output->isDecorated() == false || is_null($style)) {
        //    return $output->writeln($string, $verbosity);
        // }

        renderUsing($output);

        $left = static::highlightDynamicContent($left);
        $right = static::highlightDynamicContent($right);

        render(view('illuminate.console::detail', [
            'left' => static::highlightDynamicContent($left),
            'right' => static::highlightDynamicContent($right),
        ]), $verbosity);
    }
}
