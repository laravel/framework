<?php

namespace Illuminate\Contracts\Queue;

use Illuminate\Support\Collection;

interface QueueAcceptsMetadata
{
    /**
     * Add metadata to the payload that will be queued.
     *
     * @param  \Illuminate\Support\Collection  $metadata
     * @return void
     */
    public function setMetadata(Collection $metadata);
}
