<?php

namespace Illuminate\Console\Process;

use Illuminate\Support\Traits\Macroable;

/**
 * @method \Illuminate\Console\Process\Response run(iterable|string $arguments)
 */
class Factory
{
    use Macroable {
        __call as macroCall;
    }

    /**
     * Create a new pending process instance for this factory.
     *
     * @return \Illuminate\Console\Process\PendingProcess
     */
    protected function newPendingProcess()
    {
        return new PendingProcess();
    }

    /**
     * Execute a method against a new pending request instance.
     *
     * @param  string  $method
     * @param  iterable<array-key, string>  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        return tap($this->newPendingProcess(), function ($request) {
            // ..
        })->{$method}(...$parameters);
    }
}
