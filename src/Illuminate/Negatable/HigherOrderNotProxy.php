<?php

namespace Illuminate\Negatable;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @property-read TModel $model
 *
 * @mixin TModel
 */
class HigherOrderNotProxy
{
    /**
     * @param  TModel  $origin
     */
    public function __construct(private $origin)
    {
    }

    public function __call($method, $parameters)
    {
        return ! $this->origin->{$method}(...$parameters);
    }

    public function __get(string $name): bool
    {
        return ! $this->origin->{$name};
    }
}
