<?php

namespace Illuminate\Bus\ExecutionContext;

use Illuminate\Contracts\Cache\Factory;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Workflow\ExecutionRepository as ExecutionRepositoryContract;

use function Illuminate\Support\enum_value;

class CacheExecutionRepository implements ExecutionRepositoryContract
{
    /**
     * The cache store that should be used.
     *
     * @var string|null
     */
    protected $store = null;

    /**
     * @param  Factory  $cache  The cache factory instance
     */
    public function __construct(
        protected Factory $cache,
    ) {
    }

    #[\Override]
    public function find(mixed $id)
    {
        $id = $id instanceof ExecutionState ? $id->id() : $id;

        return $this->getCache()->get($this->determineCacheKey($id));
    }

    /**
     * @param  \Illuminate\Bus\ExecutionContext\ExecutionState|string  $id
     * @param  \DateTimeInterface|\DateInterval|int|null  $ttl
     */
    #[\Override]
    public function create(mixed $id, $ttl = null)
    {
        $executionState = $id instanceof ExecutionState ? $id : new ExecutionState($id);

        $this->getCache()->put($this->determineCacheKey($id), $executionState, $ttl);

        return $executionState;
    }

    #[\Override]
    public function saveStep($state, string $step, $ttl = null): void
    {
        $this->getCache()->put($this->determineCacheKey($state->id()), $step, $ttl);
        $currentSteps = $this->getExecutionSteps($state);
        if (! in_array($step, $currentSteps, true)) {
            $currentSteps[] = $step;
        }

        $this->getCache()->put($this->determineCacheKey($state->id()), $state, $ttl);
    }

    #[\Override]
    public function deleteStep($stateId, string $name): void
    {
        $this->deleteSteps($stateId, [$name]);
    }

    /**
     * Delete the step-list and steps.
     *
     * @param  \Illuminate\Bus\ExecutionContext\ExecutionState|string  $id
     */
    #[\Override]
    public function delete($id): void
    {
        $stepsToDelete = $this->getExecutionSteps($id);

        if (! empty($stepsToDelete)) {
            $this->deleteSteps($id, $stepsToDelete);
        }

        $this->getCache()->forget($this->getExecutionStepsCacheKey($id));
    }

    /**
     * @param  \Illuminate\Bus\ExecutionContext\ExecutionState|string  $stateId
     * @param  array<array-key, string>  $steps
     * @return void
     */
    #[\Override]
    public function deleteSteps($stateId, array $steps): void
    {
        $this->getCache()->deleteMultiple(array_map(
            fn ($stepName) => $this->determineStepCacheKey($stateId, $stepName),
            $steps)
        );
    }

    public function saveExecutionSteps($stateId, array $steps): void
    {
        $this->getCache()->put($this->determineCacheKey($stateId), $steps, );
    }

    /**
     * @param  \Illuminate\Bus\ExecutionContext\ExecutionState|string  $stateId
     * @return array
     */
    protected function getExecutionSteps($stateId): array
    {
        return $this->getCache()->get($this->getExecutionStepsCacheKey($stateId), []);
    }

    protected function getExecutionStepsCacheKey($stateId): string
    {
        return $this->determineCacheKey($stateId).':steps';
    }

    protected function getCache(): Repository
    {
        return $this->cache->store($this->store);
    }

    public function setStore($store): static
    {
        $this->store = enum_value($store);

        return $this;
    }

    /**
     * @param  \Illuminate\Bus\ExecutionContext\ExecutionState|string  $id
     * @return string
     */
    protected function determineCacheKey($id): string
    {
        $id = $id instanceof ExecutionState ? $id->id() : $id;

        return 'execution:'.$id;
    }

    /**
     * @param  \Illuminate\Bus\ExecutionContext\ExecutionState|string  $stateId
     * @param  string  $name
     */
    protected function determineStepCacheKey($stateId, $name): string
    {
        return $this->determineCacheKey($stateId).':'.$name;
    }
}
