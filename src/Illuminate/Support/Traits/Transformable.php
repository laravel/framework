<?php

namespace Illuminate\Support\Traits;

trait Transformable
{
    /**
     * Transform this instance.
     *
     * @template TReturn of mixed
     * @template TDefault of mixed
     *
     * @param  callable(self): TReturn  $callback
     * @param  TDefault|callable(self): TDefault|null  $default
     * @return (self is empty ? (self is null ? null : TDefault) : TReturn)
     */
    public function transform(callable $callback, $default = null)
    {
        return transform($this, $callback, $default);
    }
}
