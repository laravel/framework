<?php

namespace Illuminate\Contracts\Queue;

use Illuminate\Bus\ExecutionContext\ExecutionContext;

interface Resumable
{
    /**
     * @return string|\Illuminate\Bus\ExecutionContext\ExecutionContext|null
     */
    public function executionContextId(): mixed;

    /**
     * @param  ExecutionContext  $context
     * @return static
     */
    public function setExecutionContext(ExecutionContext $context);
}
