<?php

namespace Illuminate\Contracts\Queue;

use Illuminate\Bus\ExecutionContext\ExecutionContext;

interface Resumable
{
    /**
     * @return string|\Illuminate\Bus\ExecutionContext\ExecutionContext|null
     */
    public function executionContextId(): mixed;

    public function setExecutionContext(ExecutionContext $context);
}
