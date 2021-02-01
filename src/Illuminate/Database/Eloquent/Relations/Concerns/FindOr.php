<?php

namespace Illuminate\Database\Eloquent\Relations\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Collection;

trait FindOr
{
    /**
     * Find a related model by its primary key or call a callback.
     *
     * @param  int|array  $ids
     * @param  \Closure|array  $columns
     * @param  \Closure|null  $callback
     * @return \Illuminate\Database\Eloquent\Model|static|mixed
     */
    public function findOr($ids, $columns = ['*'], Closure $callback = null)
    {
        if ($columns instanceof Closure) {
            $callback = $columns;
            $columns = ['*'];
        }

        $model = $this->find($ids, $columns);

        if ($model instanceof Collection && $model->isNotEmpty()) {
            return $model;
        }

        if ($model && !$model instanceof Collection) {
            return $model;
        }

        if ($callback) {
            return $callback();
        }

        return null;
    }
}
