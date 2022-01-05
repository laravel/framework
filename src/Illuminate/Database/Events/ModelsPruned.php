<?php

namespace Illuminate\Database\Events;

class ModelsPruned
{
    /**
     * The class name of the model that was pruned.
     *
     * @var string
     */
    public $model;

    /**
     * The number of pruned records.
     *
     * @var int
     */
    public $count;

    /**
     * Create a new event instance.
     *
     * @param  string  $model
     * @param  int  $count
     * @return void
     */
    public function __construct($model, $count)
    {
        $this->model = $model;
        $this->count = $count;
    }
}
