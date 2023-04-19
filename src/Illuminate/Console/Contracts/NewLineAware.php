<?php

namespace Illuminate\Console\Contracts;

interface NewLineAware
{
    /**
     * Whether a newline has already been written.
     *
     * @return bool
     *
     * @deprecated use newLinesWritten
     */
    public function newLineWritten();

    /**
     * How many trailing newlines were written.
     *
     * @return int
     */
    public function newLinesWritten();
}
