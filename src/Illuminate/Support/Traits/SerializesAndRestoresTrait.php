<?php

namespace Illuminate\Support\Traits;

trait SerializesAndRestoresTrait
{
    /**
     * Whether items should be serialized and then restored before checking assertions.
     *
     * @var bool
     */
    protected bool $serializeAndRestore = false;

    /**
     * Set if items should serialize and restore before checking assertions.
     *
     * @param  bool  $serializeAndRestore
     * @return $this
     */
    public function serializeAndRestoreItems(bool $serializeAndRestore = true): static
    {
        $this->serializeAndRestore = $serializeAndRestore;

        return $this;
    }

    /**
     * Serialize and then unserialize the item to simulate the queueing process.
     *
     * @param  mixed  $queueable
     * @return mixed
     */
    protected function serializeAndRestoreQueueable($queueable)
    {
        return unserialize(serialize($queueable));
    }
}
