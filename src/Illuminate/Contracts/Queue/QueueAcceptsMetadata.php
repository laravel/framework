<?php

namespace Illuminate\Contracts\Queue;

interface QueueAcceptsMetadata
{
    /**
     * Add metadata to the payload that will be queued.
     *
     * @param  mixed  $metadata
     * @return void
     */
    public function setMetadata($metadata);
}
