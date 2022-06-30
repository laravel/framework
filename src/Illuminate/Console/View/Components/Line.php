<?php

namespace Illuminate\Console\View\Components;

use Illuminate\Console\Contracts\NewLineAware;
use Illuminate\View\Component;
use function Termwind\render;
use function Termwind\renderUsing;

class Line extends Component
{
    use Concerns\Highlightable;

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
     * @var string
     */
    public $title;

    /**
     * Create a new component instance.
     *
     * @param  bool  $newLine
     * @param  string  $bgColor
     * @param  string  $fgColor
     * @param  string  $title
     * @return void
     */
    public function __construct($newLine, $bgColor, $fgColor, $title)
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
        // if ($output->isDecorated() == false || is_null($style)) {
        //     return $output->writeln($string, $verbosity);
        // }

        renderUsing($output);

        render(view('illuminate.console::lines.'.$style, [
            'content' => static::highlightDynamicContent($string),
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
        return <<<'blade'
            <div class="mx-2 mb-1 mt-{{ $marginTop }}">
                <span class="px-1 bg-{{ $bgColor }} text-{{ $fgColor }} uppercase">{{ $title }}</span>
                <span class="ml-1">
                    {{ $slot }}
                </span>
            </div>
        blade;
    }
}
