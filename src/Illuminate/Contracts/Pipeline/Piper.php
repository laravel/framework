<?php

namespace Illuminate\Contracts\Pipeline;

interface Piper
{
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
     * @return array
     */
    public function pipes();

    /**
     * Add pipes through which commands should be piped before dispatching.
     *
     * @param  object[]|object  $pipes
     * @return $this
     */
    public function pipeThrough($pipes);
}
