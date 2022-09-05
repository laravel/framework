<?php

namespace Illuminate\Console\Process\Results\Concerns;

/**
 * @mixin \Illuminate\Console\Contracts\ProcessResult
 */
trait Exitable
{
    /**
     * {@see \Illuminate\Console\Contracts\ProcessResult::ok()}.
     */
    public function ok()
    {
        $this->wait();

        return $this->exitCode() == 0;
    }

    /**
     * {@see \Illuminate\Console\Contracts\ProcessResult::failed()}.
     */
    public function failed()
    {
        return ! $this->ok();
    }
}
