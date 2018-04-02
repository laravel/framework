<?php

namespace Illuminate\Database\Eloquent\Relations\Traits;

use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Trait FirstOrFail.
 *
 * @property $related
 */
trait FirstOrFail
{
    /**
     * Execute the query and get the first result or throw an exception.
     *
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Model|static
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function firstOrFail($columns = ['*'])
    {
        if (! is_null($model = $this->first($columns))) {
            return $model;
        }

        throw (new ModelNotFoundException)->setModel(get_class($this->related));
    }
}
