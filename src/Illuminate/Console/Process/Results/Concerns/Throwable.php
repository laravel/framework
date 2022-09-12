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
            $exception = new ProcessFailedException($this);
            if ($callback) {
                $callback($exception);
            }

            throw $exception;
        }

        return $this;
    }

    /**
     * {@see \Illuminate\Console\Contracts\ProcessResult::throwIf}.
     */
    public function throwIf($condition, $callback = null)
    {
        return $condition ? $this->throw($callback) : $this;
    }

    /**
     * {@see \Illuminate\Console\Contracts\ProcessResult::throwUnless}.
     */
    public function throwUnless($condition, $callback = null)
    {
        return $condition ? $this : $this->throw($callback);
    }
}
