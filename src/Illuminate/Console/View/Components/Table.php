<?php

namespace Illuminate\Console\View\Components;

use Illuminate\Contracts\Support\Arrayable;
use Symfony\Component\Console\Helper\Table as SymfonyTable;
use Symfony\Component\Console\Helper\TableStyle;

class Table extends Component
{
    /**
     * Renders the component using the given arguments.
     *
     * @param  array  $headers
     * @param  \Illuminate\Contracts\Support\Arrayable|array  $rows
     * @return void
     */
    public function render($headers, $rows)
    {
        if ($rows instanceof Arrayable) {
            $rows = $rows->toArray();
        }

        (new SymfonyTable($this->output))
            ->setStyle($this->tableStyle())
            ->setHeaders((array) $headers)
            ->setRows($rows)
            ->render();
    }

    /**
     * Return a custom table style for Laravel.
     *
     * @return \Symfony\Component\Console\Helper\TableStyle
     */
    protected function tableStyle()
    {
        return (new TableStyle())
            ->setHorizontalBorderChars('')
            ->setVerticalBorderChars(' ')
            ->setDefaultCrossingChar('')
            ->setCellHeaderFormat('<fg=green;options=bold>%s</>');
    }
}
