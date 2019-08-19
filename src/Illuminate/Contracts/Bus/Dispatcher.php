<?php

namespace Illuminate\Contracts\Bus;

interface Dispatcher
{
    /**
     * Dispatch a command to its appropriate handler.
     *
     * @param  mixed  $command
     * @return mixed
     */
    public function dispatch($command);

    /**
     * Dispatch a command to its appropriate handler in the current process.
     *
     * @param  mixed  $command
     * @param  mixed  $handler
     * @return mixed
     */
    public function dispatchNow($command, $handler = null);

    /**
     * Determine if the given command has a handler.
     *
     * @param  mixed  $command
     * @return bool
     */
    public function hasCommandHandler($command);

    /**
     * Retrieve the handler for a command.
     *
     * @param  mixed  $command
     * @return bool|mixed
     */
    public function getCommandHandler($command);

    /**
     * Set the pipes through which commands should be piped before dispatching.
     *
     * @param  object[]  $pipes
     * @return $this
     */
    public function withPipes(array $pipes);

    /**
     * Get the pipes through which commands should be piped before dispatching.
     *
     * @return object[]
     */
    public function pipes();

    /**
     * Add pipes through which commands should be piped before dispatching.
     *
     * @param  object[]|object  $pipes
     * @return $this
     */
    public function pipeThrough($pipes);

    /**
     * Map a command to a handler.
     *
     * @param  array  $map
     * @return $this
     */
    public function map(array $map);
}
