<?php

namespace Illuminate\Database\Events;

class ModelPruning
{
    /**
     * The model being pruned.
     *
     * @var string
     */
    public $model;

    /**
     * Create a new event instance.
     *
     * @param  string  $model
     * @return void
     */
    public function __construct($model)
    {
        $this->model = $model;
    }
}
