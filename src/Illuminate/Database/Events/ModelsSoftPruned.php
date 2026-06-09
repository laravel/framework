<?php

namespace Illuminate\Database\Events;

class ModelsSoftPruned
{
    /**
     * Create a new event instance.
     *
     * @param  string  $model  The class name of the model that was soft pruned.
     * @param  int  $count  The number of soft pruned records.
     */
    public function __construct(
        public $model,
        public $count,
    ) {
    }
}
