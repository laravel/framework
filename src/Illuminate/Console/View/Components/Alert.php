<?php

namespace Illuminate\Console\View\Components;

use Symfony\Component\Console\Output\OutputInterface;
use function Termwind\render;
use function Termwind\renderUsing;

class Alert
{
    /**
     * Renders the component using the given arguments.
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @param  string  $string
     * @param  int  $verbosity
     * @return void
     */
    public static function renderUsing($output, $string, $verbosity = OutputInterface::VERBOSITY_NORMAL)
    {
        renderUsing($output);

        render(view('illuminate.console::alert', [
            'content' => $string,
        ]), $verbosity);
    }
}
