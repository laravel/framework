<?php

namespace Illuminate\Tests\App\Models\Relationships;

class ViolatedLazyLoadingEvent
{
    public $model;
    public $key;

    public function __construct($model, $key)
    {
        $this->model = $model;
        $this->key = $key;
    }
}
