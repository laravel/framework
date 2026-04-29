<?php

namespace Illuminate\Queue;

trait ResumableTrait
{
    /**
     * @var array<string, mixed>
     */
    public array $checkpointData = [];

    /**
     * @var array<string, \Closure>
     */
    public array $stepToCallback = [];

    /**
     * @param  array<string, mixed>  $checkpointData
     * @return $this
     */
    public function setCheckpointData(array $checkpointData): static
    {
        $this->checkpointData = $checkpointData;

        return $this;
    }

    public function withStep(string $stepName, \Closure $callback): static
    {
        if (isset($this->stepNameToCallback[$stepName])) {
            throw new \InvalidArgumentException("Step name [$stepName] already defined.");
        }

        $this->stepToCallback[$stepName] = $callback;

        return $this;
    }
}
