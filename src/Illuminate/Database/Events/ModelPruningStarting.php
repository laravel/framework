<?php

namespace Illuminate\Database\Events;

class ModelPruningStarting
{
    /**
     * Create a new event instance.
     *
     * @param  array<class-string>  $models  The class names of the models that will be pruned.
     * @return void
     */
    public function __construct(
        public $models
    ) {
    }
}
