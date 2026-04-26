<?php

namespace Illuminate\Queue;

trait ResumableTrait
{
    /**
     * @var array<string, mixed>
     */
    public array $checkpointData = [];

    /**
     * @param  array<string, mixed>  $checkpointData
     * @return $this
     */
    public function setCheckpointData(array $checkpointData): static
    {
        $this->checkpointData = $checkpointData;

        return $this;
    }
}
