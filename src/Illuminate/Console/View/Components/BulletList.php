<?php

namespace Illuminate\Console\View\Components;

use Symfony\Component\Console\Output\OutputInterface;
use function Termwind\render;
use function Termwind\renderUsing;

class BulletList
{
    use Concerns\EnsureNoPunctuation,
        Concerns\EnsureRelativePaths,
        Concerns\Highlightable;

    /**
     * Renders the component using the given arguments.
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @param  array<int, string>  $elements
     * @param  int  $verbosity
     * @return void
     */
    public static function renderUsing($output, $elements, $verbosity = OutputInterface::VERBOSITY_NORMAL)
    {
        renderUsing($output);

        render(view('illuminate.console::bullet-list', [
            'elements' => collect($elements)->map(function ($string) {
                $string = self::highlightDynamicContent($string);
                $string = self::ensureNoPunctuation($string);
                $string = self::ensureRelativePaths($string);

                return $string;
            }),
        ]), $verbosity);
    }
}
