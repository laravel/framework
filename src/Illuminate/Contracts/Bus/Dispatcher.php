<?php

namespace Illuminate\Contracts\Bus;

interface Dispatcher
{
    /**
     * Dispatch a command to its appropriate handler.
     *
     * @param  mixed  $command
     * @param  \Closure|null  $afterResolving
     * @return mixed
     */
    public function dispatch($command);

    /**
     * Dispatch a command to its appropriate handler in the current process.
     *
     * @param  mixed  $command
     * @param  \Closure|null  $afterResolving
     * @return mixed
     */
    public function dispatchNow($command);

    /**
     * Set the pipes commands should be piped through before dispatching.
     *
     * @param  array  $pipes
     * @return $this
     */
    public function pipeThrough(array $pipes);
}
