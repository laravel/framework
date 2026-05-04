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
     * @param  string  $name
     * @param  callable  $callback
     * @param  array{ttl?:  \DateTimeInterface|\DateInterval|int|null}  $options
     * @return mixed
     *
     * @throws \Throwable
     */
    public function step(string $name, callable $callback, $options = []): mixed
    {
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

    public function forgetStep(string $name): void
    {
        if ($this->executionRepository->getStep($this->state, $name) === null) {
            return;
        }

        $this->executionRepository->deleteStep($this->state, $name);
    }

    /**
     * Delete the ExecutionState.
     *
     * @return void
     */
    public function delete()
    {
        // @todo get steps and delete

        $this->executionRepository->delete($this->state->id());
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
