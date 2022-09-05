<?php

namespace Illuminate\Console\Process\Results\Concerns;

/**
 * @mixin \Illuminate\Console\Contracts\ProcessResult
 */
trait Stringable
{
    /**
     * {@see \Illuminate\Console\Contracts\ProcessResult::toString()}.
     */
    public function toString()
    {
        return $this->output();
    }

    /**
     * {@see \Illuminate\Console\Contracts\ProcessResult::__toString()}.
     */
    public function __toString()
    {
        return $this->toString();
    }
}
