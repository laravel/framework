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
    public function saveStep($state, $stepResult, $options = []): void
    {
        $ttl = array_key_exists('ttl', $options) ? $options['ttl'] : $this->defaultTtl($state);

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
        $this->getCache()->forget($this->determineExecutionStepsSetCacheKey($id));
    }

    /**
     * @param  \Illuminate\Bus\ExecutionContext\ExecutionState|string  $stateId
     * @param  array<array-key, string>  $steps
     * @return void
     */
    #[\Override]
    public function deleteSteps($stateId, $steps)
    {
        $this->getCache()->deleteMultiple(
            array_map(fn ($stepName) => $this->determineStepCacheKey($stateId, $stepName), (array) $steps)
        );

        $remainingSteps = array_values(array_diff($this->getExecutionSteps($stateId), (array) $steps));

        $this->saveExecutionSteps($stateId, $remainingSteps);
    }

    protected function saveExecutionSteps($stateId, array $steps): void
    {
        $this->getCache()->put(
            $this->determineExecutionStepsSetCacheKey($stateId),
            array_values(array_unique($steps)),
            $this->defaultTtl($stateId),
        );
    }

    /**
     * Get the set of steps which have results.
     *
     * @param  \Illuminate\Bus\ExecutionContext\ExecutionState|string  $stateId
     * @return array
     */
    protected function getExecutionSteps($stateId): array
    {
        return (array) $this->getCache()->get($this->determineExecutionStepsSetCacheKey($stateId), []);
    }

    /**
     * @param  \Illuminate\Bus\ExecutionContext\ExecutionState|string  $stateId
     * @return array{options?: array{ttl?: int}}|null
     */
    protected function getExecutionMetadata($stateId): ?array
    {
        return $this->getCache()->get($this->determineCacheKey($stateId));
    }

    /**
     * Save the ExecutionState metadata.
     *
     * @param  \Illuminate\Bus\ExecutionContext\ExecutionState|string  $stateId
     * @param  array<array-key, mixed>  $metadata
     * @param  \DateTimeInterface|\DateInterval|int|null  $ttl
     * @return void
     */
    protected function saveExecutionMetadata($stateId, array $metadata, $ttl = null): void
    {
        $this->getCache()->put($this->determineCacheKey($stateId), $metadata, $ttl);
    }

    /**
     * Get the default cache expiration for the ExecutionState.
     *
     * @param  \Illuminate\Bus\ExecutionContext\ExecutionState|string  $stateId
     * @return int|null
     */
    protected function defaultTtl($stateId)
    {
        if ($stateId instanceof ExecutionState) {
            return $stateId->option('ttl');
        }

        return $this->getExecutionMetadata($stateId)['options']['ttl'] ?? null;
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
     * Get the base cache key for the ExecutionState.
     *
     * @param  \Illuminate\Bus\ExecutionContext\ExecutionState|string  $id
     * @return string
     */
    protected function determineCacheKey($id): string
    {
        $id = $id instanceof ExecutionState ? $id->id() : $id;

        return 'execution:'.$id;
    }

    /**
     * The cache key which stores the set of completed steps.
     *
     * @param  \Illuminate\Bus\ExecutionContext\ExecutionState|string  $stateId
     * @return string
     */
    protected function determineExecutionStepsSetCacheKey($stateId): string
    {
        return $this->determineCacheKey($stateId).':steps';
    }

    /**
     * The cache key for an individual step.
     *
     * @param  \Illuminate\Bus\ExecutionContext\ExecutionState|string  $stateId
     * @param  string  $name
     */
    protected function determineStepCacheKey($stateId, $name): string
    {
        return $this->determineCacheKey($stateId).':step:'.$name;
    }
}
