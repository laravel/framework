<?php

namespace Illuminate\Queue;

use Illuminate\Bus\ExecutionContext\ExecutionContext;

trait ResumableTrait
{
    protected ExecutionContext $context;

    /**
     * @return string|\Illuminate\Bus\ExecutionContext\ExecutionContext|null
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

    /**
     * @template TReturn
     *
     * @param  string  $name
     * @param  callable(): TReturn  $callback
     * @params array{ttl?:  \DateTimeInterface|\DateInterval|int|null|(\Closure(ExecutionStepResult): \DateTimeInterface|\DateInterval|int|null)}|\Illuminate\Contracts\Support\Arrayable  $options
     * @return TReturn
     */
    protected function step($name, callable $callback, $options = []): mixed
    {
        return $this->context->step($name, $callback, $options);
    }
}
