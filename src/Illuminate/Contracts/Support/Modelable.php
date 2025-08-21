<?php

namespace Illuminate\Contracts\Support;

use Illuminate\Database\Eloquent\Model;

interface Modelable
{
    /**
     * Convert the object to an Eloquent Model instance.
     *
     * @param  class-string $class
     * @return Model
     */
    public function toModel(string $class): Model;
}
