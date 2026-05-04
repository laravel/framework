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
    public function find($id)
    {
        $id = $id instanceof ExecutionState ? $id->id() : $id;

        $steps = $this->getCache()->has($this->determineExecutionStepsCacheKey($id));

        return $steps === false ? null : new ExecutionState($id);
    }

    /**
     * @param  \Illuminate\Bus\ExecutionContext\ExecutionState|string  $id
     * @param  \DateTimeInterface|\DateInterval|int|null  $ttl
     */
    #[\Override]
    public function create($id, $ttl = null)
    {
        $executionState = $id instanceof ExecutionState ? $id : new ExecutionState($id);

        $this->saveExecutionSteps($executionState, [], $ttl);

        return $executionState;
    }

    #[\Override]
    public function getStep($state, $step)
    {
        return $this->getCache()->get($this->determineStepCacheKey($state, $step));
    }

    #[\Override]
    public function saveStep($state, $stepResult, $ttl = null): void
    {
        $this->getCache()->put($this->determineStepCacheKey($state, $stepResult->name), $stepResult, $ttl);

        $currentSteps = $this->getExecutionSteps($state);
        if (! in_array($stepResult->name, $currentSteps, true)) {
            $currentSteps[] = $stepResult->name;
            $this->saveExecutionSteps($state, $currentSteps, $ttl);
        }
    }

    #[\Override]
    public function deleteStep($stateId, $name): void
    {
        $this->deleteSteps($stateId, [$name]);
    }

    /**
     * Delete the ExecutionState and its associated steps.
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

        $this->getCache()->forget($this->determineExecutionStepsCacheKey($id));
    }

    /**
     * @param  \Illuminate\Bus\ExecutionContext\ExecutionState|string  $stateId
     * @param  array<array-key, string>  $steps
     * @return void
     */
    #[\Override]
    public function deleteSteps($stateId, $steps): void
    {
        $this->getCache()->deleteMultiple(array_map(
            fn ($stepName) => $this->determineStepCacheKey($stateId, $stepName),
            (array) $steps)
        );

        $remainingSteps = array_values(array_diff($this->getExecutionSteps($stateId), (array) $steps));

        $this->saveExecutionSteps($stateId, $remainingSteps);
    }

    protected function saveExecutionSteps($stateId, array $steps, $ttl = null): void
    {
        $this->getCache()->put($this->determineExecutionStepsCacheKey($stateId), $steps, $ttl);
    }

    /**
     * @param  \Illuminate\Bus\ExecutionContext\ExecutionState|string  $stateId
     * @return array
     */
    protected function getExecutionSteps($stateId): array
    {
        return (array) $this->getCache()->get($this->determineExecutionStepsCacheKey($stateId), []);
    }

    protected function determineExecutionStepsCacheKey($stateId): string
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
        return $this->determineCacheKey($stateId).':step:'.$name;
    }
}
