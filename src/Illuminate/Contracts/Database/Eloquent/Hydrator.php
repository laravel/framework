<?php

namespace Illuminate\Contracts\Database\Eloquent;

use Illuminate\Database\Eloquent\Model;

interface Hydrator
{
    /**
     * Fill a new Eloquent model instance with raw attributes returned from the query builder.
     *
     * @param Model $model
     * @param array $attributes
     * @param array $options
     * @return \Illuminate\Contracts\Database\Eloquent\Hydratable|\Illuminate\Database\Eloquent\Model
     */
    public function hydrate(Model $model, array $attributes = [], array $options = []);
}
