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

        $metadata = $id === null ? null : $this->getExecutionMetadata($id);

        return $metadata === null ? null : new ExecutionState($id, options: $metadata['options'] ?? []);
    }

    /**
     * @param  \Illuminate\Bus\ExecutionContext\ExecutionState|string  $id
     * @param  array{ttl?: int}  $options
     */
    #[\Override]
    public function create($id, $options = [])
    {
        $executionState = $id instanceof ExecutionState ? $id : new ExecutionState($id, options: $options);

        $this->saveExecutionMetadata($executionState, [
            'options' => $executionState->options(),
        ], $executionState->option('ttl'));

        $this->saveExecutionSteps($executionState, []);

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
        $ttl ??= $this->defaultTtl($state);

        $this->getCache()->put($this->determineStepCacheKey($state, $stepResult->name), $stepResult, $ttl);

        $currentSteps = $this->getExecutionSteps($state);
        if (! in_array($stepResult->name, $currentSteps, true)) {
            $currentSteps[] = $stepResult->name;
            $this->saveExecutionSteps($state, $currentSteps);
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

        $this->getCache()->forget($this->determineCacheKey($id));
        $this->getCache()->forget($this->determineExecutionStepsCacheKey($id));
    }

    /**
     * @param  \Illuminate\Bus\ExecutionContext\ExecutionState|string  $stateId
     * @param  array<array-key, string>  $steps
     * @return void
     */
    #[\Override]
    public function deleteSteps($stateId, $steps)
    {
        $this->getCache()->deleteMultiple(array_map(
            fn ($stepName) => $this->determineStepCacheKey($stateId, $stepName),
            (array) $steps)
        );

        $remainingSteps = array_values(array_diff($this->getExecutionSteps($stateId), (array) $steps));

        $this->saveExecutionSteps($stateId, $remainingSteps);
    }

    protected function saveExecutionSteps($stateId, array $steps): void
    {
        $ttl = $this->defaultTtl($stateId);

        $this->getCache()->put(
            $this->determineExecutionStepsCacheKey($stateId),
            array_values(array_unique($steps)),
            $ttl,
        );
    }

    /**
     * @param  \Illuminate\Bus\ExecutionContext\ExecutionState|string  $stateId
     * @return array
     */
    protected function getExecutionSteps($stateId): array
    {
        return (array) $this->getCache()->get($this->determineExecutionStepsCacheKey($stateId), []);
    }

    protected function getExecutionMetadata($stateId): ?array
    {
        return $this->getCache()->get($this->determineCacheKey($stateId));
    }

    protected function saveExecutionMetadata($stateId, array $metadata, $ttl = null): void
    {
        $this->getCache()->put($this->determineCacheKey($stateId), $metadata, $ttl);
    }

    protected function defaultTtl($stateId)
    {
        if ($stateId instanceof ExecutionState) {
            return $stateId->option('ttl');
        }

        return $this->getExecutionMetadata($stateId)['options']['ttl'] ?? null;
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
