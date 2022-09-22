<?php

namespace Illuminate\Console\View\Components;

use Symfony\Component\Console\Output\OutputInterface;

class NewLine extends Component
{
    /**
     * Renders the component using the given arguments.
     *
     * @param  int  $verbosity
     * @return void
     */
    public function render($verbosity = OutputInterface::VERBOSITY_NORMAL)
    {
        $this->output->write("\n", false, $verbosity);
    }
}
