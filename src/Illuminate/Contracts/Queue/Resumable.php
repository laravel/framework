<?php

namespace Illuminate\Contracts\Queue;

interface Resumable
{
    public function setCheckpointData(array $checkpointData): static;
}
