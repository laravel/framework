<?php

namespace Illuminate\Console\Process;

class Factory
{
    /**
     * Create a new pending process associated with this factory.
     *
     * @return \Illuminate\Console\Process\PendingProcess
     */
    protected function newPendingProcess()
    {
        return new PendingProcess($this);
    }

    /**
     * Dynamically proxy methods to a new pending process instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->newPendingProcess()->{$method}(...$parameters);
    }
}
