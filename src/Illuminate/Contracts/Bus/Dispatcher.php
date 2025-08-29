<?php

namespace Illuminate\Contracts\Bus;

interface Dispatcher
{
    /**
     * Dispatch a command to its appropriate handler.
     */
    public function dispatch($command);

    /**
     * Dispatch a command to its appropriate handler in the current process.
     *
     * Queueable jobs will be dispatched to the "sync" queue.
     */
    public function dispatchSync($command, $handler = null);

    /**
     * Dispatch a command to its appropriate handler in the current process.
     */
    public function dispatchNow($command, $handler = null);

    /**
     * Determine if the given command has a handler.
     *
     * @return bool
     */
    public function hasCommandHandler($command);

    /**
     * Retrieve the handler for a command.
     */
    public function getCommandHandler($command);

    /**
     * Set the pipes commands should be piped through before dispatching.
     *
     * @return $this
     */
    public function pipeThrough(array $pipes);

    /**
     * Map a command to a handler.
     *
     * @return $this
     */
    public function map(array $map);
}
