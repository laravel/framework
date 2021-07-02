<?php

namespace Illuminate\Database\Events;

class ModelsPruned
{
    /**
     * The model that was pruned.
     *
     * @var string
     */
    public $model;

    /**
     * The amount of pruned records.
     *
     * @var int
     */
    public $amount;

    /**
     * Create a new event instance.
     *
     * @param  string  $model
     * @param  int  $amount
     * @return void
     */
    public function __construct($model, $amount)
    {
        $this->model = $model;
        $this->amount = $amount;
    }
}
