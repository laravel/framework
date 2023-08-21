<?php

namespace Illuminate\Support\Traits;

use Illuminate\Support\Testing\Fakes\QueueFake;

trait SerializesAndRestoresTrait
{
    /**
     * Whether jobs should be serialized and then restored before checking assertions.
     *
     * @var bool
     */
    protected bool $serializeAndRestore = false;

    /**
     * Set if the job should serialize and restore before checking assertions.
     *
     * @param  bool  $serializeAndRestore
     * @return $this
     */
    public function serializeAndRestoreJobs(bool $serializeAndRestore = true): static
    {
        $this->serializeAndRestore = $serializeAndRestore;

        return $this;
    }

    /**
     * Serialize and then unserialize the job to simulate the queueing process.
     *
     * @param  mixed  $job
     * @return mixed
     */
    protected function serializeAndRestore($job)
    {
        return unserialize(serialize($job));
    }
}
