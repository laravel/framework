<?php

namespace Illuminate\Console\View\Components;

use Illuminate\Console\Contracts\NewLineAware;
use Illuminate\View\Component;
use Symfony\Component\Console\Output\OutputInterface;
use function Termwind\render;
use function Termwind\renderUsing;

class Line extends Component
{
    use Concerns\EnsurePunctuation,
        Concerns\EnsureRelativePaths,
        Concerns\Highlightable;

    /**
     * The margin top that should be applied.
     *
     * @var int
     */
    public $marginTop;

    /**
     * The line background color.
     *
     * @var string
     */
    public $bgColor;

    /**
     * The line foreground color.
     *
     * @var string
     */
    public $fgColor;

    /**
     * The line title.
     *
     * @var string|null
     */
    public $title;

    /**
     * Create a new component instance.
     *
     * @param  bool  $newLine
     * @param  string  $bgColor
     * @param  string  $fgColor
     * @param  string|null  $title
     * @return void
     */
    public function __construct($newLine, $bgColor, $fgColor, $title = null)
    {
        $this->marginTop = $newLine ? 1 : 0;
        $this->bgColor = $bgColor;
        $this->fgColor = $fgColor;
        $this->title = $title;
    }

    /**
     * Renders the component using the given arguments.
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @param  string  $string
     * @param  string|null  $style
     * @param  int  $verbosity
     * @return void
     */
    public static function renderUsing($output, $string, $style, $verbosity = OutputInterface::VERBOSITY_NORMAL)
    {
        $style = $style ?: 'raw';

        $string = self::highlightDynamicContent($string);
        $string = self::ensurePunctuation($string);
        $string = self::ensureRelativePaths($string);

        renderUsing($output);
        render(view('illuminate.console::lines.'.$style, [
            'content' => $string,
            'newLine' => $output instanceof NewLineAware
                ? $output->newLineWritten() == false
                : true,
        ]), $verbosity);
    }

    /**
     * Get the view / view contents that represent the component.
     *
     * @return void
     */
    public function render()
    {
        return view('illuminate.console::lines.line', [
            'marginTop' => $this->marginTop,
            'title' => $this->title,
            'bgColor' => $this->bgColor,
            'fgColor' => $this->fgColor,
        ]);
    }
}
