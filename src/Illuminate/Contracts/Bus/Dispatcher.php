<?php

namespace Illuminate\Contracts\Bus;

use Closure;

interface Dispatcher
{
    /**
     * Dispatch a command to its appropriate handler.
     *
     * @param  mixed $command
     * @param Closure|null $errorCallback
     * @return mixed
     */
    public function dispatch($command, Closure $errorCallback = null);

    /**
     * Dispatch a command to its appropriate handler in the current process.
     *
     * @param  mixed $command
     * @param  mixed $handler
     * @param Closure|null $errorCallback
     * @return mixed
     */
    public function dispatchNow($command, $handler = null, Closure $errorCallback = null);

    /**
     * Set the pipes commands should be piped through before dispatching.
     *
     * @param  array  $pipes
     * @return $this
     */
    public function pipeThrough(array $pipes);
}
