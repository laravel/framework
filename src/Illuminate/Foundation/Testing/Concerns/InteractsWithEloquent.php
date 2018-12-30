<?php

namespace Illuminate\Foundation\Testing\Concerns;

use Illuminate\Database\Eloquent\Model;

trait InteractsWithEloquent
{
    /**
     * Return whether a given model still exists.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return bool
     */
    private function modelExists(Model $model)
    {
        return boolval($model::find($model->getRouteKey()));
    }

    /**
     * Assert that a given model still exists.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return $this
     */
    protected function assertModelExists(Model $model)
    {
        $this->assertTrue($this->modelExists($model), 'The ['.get_class($model).'] model doesn\'t exist anymore.');
        return $this;
    }

    /**
     * Assert that a given model doesn't exist anymore.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return $this
     */
    protected function assertModelDeleted(Model $model)
    {
        $this->assertFalse($this->modelExists($model), 'The ['.get_class($model).'] model still exists.');
        return $this;
    }
}
