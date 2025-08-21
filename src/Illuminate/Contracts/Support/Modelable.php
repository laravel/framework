<?php

namespace Illuminate\Contracts\Support;

use Illuminate\Database\Eloquent\Model;

interface Modelable
{
    /**
     * Convert the object to an Eloquent Model instance.
     *
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $class
     * @return Model
     */
    public function toModel(string $class): Model;
}
