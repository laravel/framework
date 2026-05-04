<?php

namespace Illuminate\Bus\ExecutionContext;

use Illuminate\Bus\Events\StepCompleted;
use Illuminate\Bus\Events\StepFailed;
use Illuminate\Bus\Events\StepStarting;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Workflow\ExecutionRepository as ExecutionRepositoryContract;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Throwable;

use function array_key_exists;

class ExecutionContext
{
    protected ExecutionState $state;

    /**
     * @param  ExecutionRepositoryContract  $executionRepository
     * @param  Dispatcher|null  $eventDispatcher
     * @param  mixed|null  $id
     * @param  array{ttl?: \DateTimeInterface|\DateInterval|int|null|(\Closure(): \DateTimeInterface|\DateInterval|int|null)}  $options
     */
    public function __construct(
        protected ExecutionRepositoryContract $executionRepository,
        protected ?Dispatcher $eventDispatcher = null,
        mixed $id = null,
        protected $options = [],
    ) {
        $this->options = $this->normalizeOptions($options);

        if ($id instanceof ExecutionState) {
            $this->state = $id;
        } else {
            $this->state = $this->executionRepository->find($id)
                ?? $this->executionRepository->create($id ?? Str::random(32), $this->options);
        }
    }

    /**
     * @template TReturn
     *
     * @param  string  $name
     * @param  (callable(): TReturn)  $callback
     * @param  array{ttl?:  \DateTimeInterface|\DateInterval|int|null}  $options
     * @return TReturn
     *
     * @throws \Throwable
     */
    public function step(string $name, callable $callback, $options = []): mixed
    {
        if ($this->state->hasCompletedStep($name)) {
            return $this->state->resultFor($name);
        }

        $stepResult = $this->executionRepository->getStep($this->state, $name);

        if ($stepResult !== null) {
            $this->state->recordStepResult($stepResult);

            return $stepResult->result;
        }

        $this->eventDispatcher?->dispatch(new StepStarting($this->state, $name));
        try {
            $result = $callback();
        } catch (Throwable $e) {
            $this->eventDispatcher?->dispatch(new StepFailed($this->state, $name, $e));

            throw $e;
        }

        $stepResult = new ExecutionStepResult($this->state->id(), $name, Carbon::now()->getTimestamp(), $result);

        $this->state->recordStepResult($stepResult);
        $this->executionRepository->saveStep($this->state, $stepResult, value($options['ttl'] ?? null, $stepResult));

        $this->eventDispatcher?->dispatch(new StepCompleted($this->state, $name, $stepResult));

        return $result;
    }

    /**
     * Forget an individual step so it can be reassessed.
     *
     * @param  string  $name
     * @return void
     */
    public function forgetStep(string $name): void
    {
        $this->executionRepository->deleteStep($this->state, $name);

        $this->state->forgetStep($name);
    }

    /**
     * Delete the ExecutionState.
     *
     * @return void
     */
    public function delete()
    {
        $this->executionRepository->delete($this->state);
    }

    public function getState(): ExecutionState
    {
        return $this->state;
    }

    protected function normalizeOptions(array $options): array
    {
        if (array_key_exists('ttl', $options)) {
            $options['ttl'] = value($options['ttl']);
        }

        return $options;
    }
}
