<?php

namespace Illuminate\Console\View\Components;

use Illuminate\View\Component;

class LineComponent extends Component
{
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
     * @param  string  $bgColor
     * @param  string  $fgColor
     * @param  string  $title
     * @return void
     */
    public function __construct($bgColor, $fgColor, $title)
    {
        $this->bgColor = $bgColor;
        $this->fgColor = $fgColor;
        $this->title = $title;
    }

    /**
     * Get the view / view contents that represent the component.
     *
     * @return void
     */
    public function render()
    {
        return <<<'blade'
            <div class="mx-2 my-1">
                <span class="px-1 bg-{{ $bgColor }} text-{{ $fgColor }} uppercase">{{ $title }}</span>
                <span class="ml-1">
                    {{ $slot }}
                </span>
            </div>
        blade;
    }
}
