<?php

namespace Illuminate\Console\Process\Results\Concerns;

/**
 * @mixin \Illuminate\Console\Contracts\ProcessResult
 */
trait Arrayable
{
    /**
     * {@see \Illuminate\Console\Contracts\ProcessResult::toArray()}
     */
    public function toArray()
    {
        return str($this->output())->explode("\n")->toArray();
    }
}
