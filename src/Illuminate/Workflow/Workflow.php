<?php

namespace Illuminate\Workflow;

use Illuminate\Contracts\Container\Container;

/**
 * @template StateT
 */
class Workflow
{
    /**
     * The current state object. This could be an associative array, an object, or even an integer.
     * This is what tells the workflow the current state of the workflow. It is available from
     * step to step in the workflow. We would normally persist information from the workflow
     * in that step.
     *
     * @var StateT
     */
    public mixed $state = null;

    /**
     * The mapping of step name to a callback.
     *
     * @var array<string, callable>
     */
    public array $stepNameToCallback = [];

    /**
     * This is the order of operations to execute. If null, then the step itself must call the workflow
     * to tell it what to perform next.
     *
     * @var array<int, string>|null
     */
    public ?array $orderOfOperations = null;

    /**
     * @var (callable(static): void)|null
     */
    public mixed $persistenceCallback = null;

    /**
     * The next step to perform.
     *
     * @var string|null
     */
    public ?string $next = null;

    public function __construct(
        protected Container $container,
    ) {
    }

    public function withState($state): self
    {
        $this->state = $state;

        return $this;
    }

    public function withStep(string $stepName, callable $step): self
    {
        $this->stepNameToCallback[$stepName] = $step;

        return $this;
    }

    public function withPersistence(?callable $persistenceCallback = null): self
    {
        $this->persistenceCallback = $persistenceCallback;

        return $this;
    }

    /**
     * @throws \LogicException
     */
    public function handle(): void
    {
        if ($this->next === null && empty($this->orderOfOperations)) {
            throw new \LogicException('You must specify the operation to execute');
        }

        $next = $this->next ?? array_pop($this->orderOfOperations);

        if (! isset($this->stepNameToCallback[$next])) {
            throw new \InvalidArgumentException(sprintf('The operation [%s] does not exist', $next));
        }

        $newState = $this->container->call($this->stepNameToCallback[$next], [&$this->state, $this]);

        if ($newState !== null) {
            $this->state = $newState;
        }

        // Should the workflow be responsible for persisting state?
        if ($this->persistenceCallback !== null) {
            call_user_func($this->persistenceCallback, $newState);
        }
    }
}
