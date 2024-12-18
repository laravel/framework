<?php

namespace Illuminate\Bus\Events;

use Illuminate\Bus\Batch;

class BatchDispatched
{
    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Bus\Batch  $batch  The batch instance.
     * @return void
     */
    public function __construct(
        public Batch $batch,
    ) {
    }
}
