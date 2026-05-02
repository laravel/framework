<?php

namespace Illuminate\Queue;

use Illuminate\Bus\ExecutionContext\ExecutionContext;

trait ResumableTrait
{
    protected ExecutionContext $context;

    /**
     * @return string|\Illuminate\Bus\ExecutionContext\ExecutionContext
     */
    public function executionContextId(): mixed
    {
        return $this->job?->uuid() ?? null;
    }

    public function setExecutionContext(ExecutionContext $context)
    {
        $this->context = $context;

        return $this;
    }

    protected function step(string $name, callable $callback, array $options = []): mixed
    {
        return $this->context->step($name, $callback, $options);
    }
}
