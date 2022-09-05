<?php

namespace Illuminate\Console\Process\Results\Concerns;

use Illuminate\Console\Exceptions\ProcessFailedException;

/**
 * @mixin \Illuminate\Console\Contracts\ProcessResult
 */
trait Throwable
{
    /**
     * {@see \Illuminate\Console\Contracts\ProcessResult::throw}.
     */
    public function throw($callback = null)
    {
        $this->wait();

        if ($this->failed()) {
            throw new ProcessFailedException($this->process, $this);
        }

        return $this;
    }

    /**
     * {@see \Illuminate\Console\Contracts\ProcessResult::throwIf}.
     */
    public function throwIf($condition)
    {
        return $condition ? $this->throw() : $this;
    }

    /**
     * {@see \Illuminate\Console\Contracts\ProcessResult::throwUnless}.
     */
    public function throwUnless($condition)
    {
        return $condition ? $this : $this->throw();
    }
}
