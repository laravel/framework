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
     * The current step being worked.
     *
     * @var string
     */
    public string $working;

    public function __construct(
        protected Container $container,
    ) {
    }

    public function withState($state): self
    {
        $this->state = $state;

        return $this;
    }

    public function withStep(string $stepName, callable $step, bool $replace = true): self
    {
        if (isset($this->stepNameToCallback[$stepName]) && $replace === false) {
            throw new \RuntimeException(sprintf('The step [%s] already exists.', $stepName));
        }

        $this->stepNameToCallback[$stepName] = $step;

        return $this;
    }

    /**
     * @throws \LogicException
     */
    public function handle(string $step): void
    {
        $this->working = $step;

        if (! isset($this->stepNameToCallback[$step])) {
            throw new \InvalidArgumentException(sprintf('The operation [%s] does not exist', $step));
        }

        $newState = $this->container->call(
            $this->stepNameToCallback[$step],
            // @todo this needs some kind of trait that determines the types I guess? Or to pass named parameters or whatever
            ['state' => &$this->state, $this]
        );

        if ($newState !== null) {
            $this->state = $newState;
        }
    }
}
