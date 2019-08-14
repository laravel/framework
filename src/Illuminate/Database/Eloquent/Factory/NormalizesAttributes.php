<?php

namespace Illuminate\Database\Eloquent\Factory;

use Illuminate\Database\Eloquent\Model;

trait NormalizesAttributes
{
    /**
     * Ensure a query result is returned as a collection.
     *
     * @param  mixed  $result
     * @return \Illuminate\Support\Collection
     */
    protected function collect($result)
    {
        if ($result instanceof Model) {
            $result = [$result];
        }

        return collect($result);
    }

    /**
     * Ensure a query result is returned as a model.
     *
     * @param  mixed  $results
     * @return Model
     */
    protected function collectModel($results)
    {
        return $this->collect($results)->first();
    }

    /**
     * Ensure a subject is a callable.
     *
     * @param  mixed  $arg
     * @return callable
     */
    protected function wrapCallable($arg)
    {
        if (! is_callable($arg) || is_string($arg)) {
            return function () use ($arg) {
                return $arg;
            };
        }

        return $arg;
    }
}
