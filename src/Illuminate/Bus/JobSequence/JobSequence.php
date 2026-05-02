<?php

namespace Illuminate\Bus\JobSequence;

use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Pipeline\Pipeline;
use InvalidArgumentException;
use LogicException;

class JobSequence
{
    public array $steps = [];
    public array $orderedSteps = [];

    public JobSequenceExecutionStateOG $state;

    /**
     * @var (\Closure(\Illuminate\Bus\JobSequence\JobSequenceExecutionStateOG): void)
     */
    protected Closure $persistenceCallback;

    /**
     * @var (\Closure(\Illuminate\Bus\JobSequence\JobSequenceExecutionStateOG): void)
     */
    protected Closure $clearStateCallback;

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
     * @param  (\Closure(\Illuminate\Bus\JobSequence\JobSequenceExecutionStateOG): void)  $callback
     * @return $this
     */
    public function persistenceCallback(\Closure $callback): static
    {
        $this->persistenceCallback = $callback;

        return $this;
    }

    /**
     * @param  (\Closure(\Illuminate\Bus\JobSequence\JobSequenceExecutionStateOG): void)  $callback
     * @return $this
     */
    public function clearStateCallback(\Closure $callback): static
    {
        $this->clearStateCallback = $callback;

        return $this;
    }

    /**
     * @param  (\Closure(\Illuminate\Bus\JobSequence\JobSequenceExecutionStateOG): mixed)|null  $callback
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

    public function withState(JobSequenceExecutionStateOG $resumeState): static
    {
        $this->state = $resumeState;

        return $this;
    }

    /**
     * @return JobSequenceExecutionStateOG|mixed
     * @throws \LogicException
     */
    public function execute()
    {
        $this->state ??= new JobSequenceExecutionStateOG();

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

            $pipeline->pipe(function (JobSequenceExecutionStateOG $carry, $next) use ($fn) {
                $fn($carry);
                $carry->stepIndex++;

                call_user_func($this->persistenceCallback, $carry);

                return $next($carry);
            });
        }

        $result = $pipeline->thenReturn();

        $state = $result instanceof JobSequenceExecutionStateOG ? $result : $this->state;
        call_user_func($this->clearStateCallback, $state);

        return $result;
    }
}
