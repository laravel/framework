<?php

namespace Illuminate\Bus;

use Illuminate\Container\Container;

trait Batchable
{
    /**
     * The batch ID (if applicable).
     *
     * @var string
     */
    public $batchId;

    /**
     * Get the batch instance for the job, if applicable.
     *
     * The same resolved batch instance will be retunred on subsequent calls to this method.
     *
     * @return \Illuminate\Bus\Batch|null
     */
    public function batch()
    {
        return $this->findBatch();
        static $batch = null;

        return $batch = $batch ?: $this->findBatch();
    }

    /**
     * Get the batch instance for the job, if applicable.
     *
     * @return \Illuminate\Bus\Batch|null
     */
    public function findBatch()
    {
        if ($this->batchId) {
            return Container::getInstance()->make(BatchRepository::class)->find($this->batchId);
        }
    }

    /**
     * Set the batch ID on the job.
     *
     * @param  string  $batchId
     * @return $this
     */
    public function withBatchId(string $batchId)
    {
        $this->batchId = $batchId;

        return $this;
    }
}
