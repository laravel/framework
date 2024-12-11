<?php

namespace Illuminate\Database\Events;

class ModelPruningFinished
{
    /**
     * Create a new event instance.
     *
     * @param  array<class-string>  $models  The class names of the models that were pruned.
     * @return void
     */
    public function __construct(
        public $models,
    ) {
    }
}
