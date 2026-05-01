<?php

namespace Illuminate\Bus\Workflow;

use Illuminate\Contracts\Container\Container;
use Illuminate\Pipeline\Pipeline;
use InvalidArgumentException;
use LogicException;

class Workflow
{
    public array $steps = [];
    public array $orderedSteps = [];

    public ExecutionState $state;

    protected \Closure $persistenceCallback;

    protected \Closure $clearStateCallback;

    public function __construct(
        protected Container $container,
    ) {
    }

    public function isComplete(): bool
    {
        return count($this->orderedSteps) <= $this->state->stepIndex;
    }

    protected function buildStepName(): string
    {
        return 'workflow_step'.count($this->steps);
    }

    /**
     * @param  (\Closure(ExecutionState): void)  $callback
     * @return $this
     */
    public function persistenceCallback(\Closure $callback): static
    {
        $this->persistenceCallback = $callback;

        return $this;
    }

    /**
     * @param  (\Closure(ExecutionState): void)  $callback
     * @return $this
     */
    public function clearStateCallback(\Closure $callback): static
    {
        $this->clearStateCallback = $callback;

        return $this;
    }

    /**
     * @param  (\Closure(ExecutionState): mixed)|null  $callback
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function step(\Closure|null $callback = null, ?string $name = null): static
    {
        if ($callback === null && $name === null) {
            throw new InvalidArgumentException('Either callback or name is required.');
        }

        $name ??= $this->buildStepName();
        $this->orderedSteps[] = $name;
        if ($callback) {
            $this->steps[$name] = $callback;
        }

        return $this;
    }

    public function withState(ExecutionState $resumeState): static
    {
        $this->state = $resumeState;

        return $this;
    }

    /**
     * @return ExecutionState|mixed
     * @throws \LogicException
     */
    public function execute()
    {
        $this->state ??= new ExecutionState();

        $pipeline = new Pipeline($this->container);
        $pipeline->send($this->state);

        // Figure out which pipe we are on
        $remainingSteps = array_slice($this->orderedSteps, $this->state->stepIndex);

        if ($remainingSteps === []) {
            // This should never happen
            throw new LogicException('There are no remaining steps.');
        }

        foreach ($remainingSteps as $stepName) {
            $fn = $this->steps[$stepName];

            $pipeline->pipe(function (ExecutionState $carry, $next) use ($fn) {
                $fn($carry);
                $carry->stepIndex++;

                call_user_func($this->persistenceCallback, $carry);

                return $next($carry);
            });
        }

        $result = $pipeline->thenReturn();

        $state = $result instanceof ExecutionState ? $result : $this->state;
        call_user_func($this->clearStateCallback, $state);

        return $result;
    }
}
