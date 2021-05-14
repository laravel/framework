<?php

namespace Illuminate\Foundation\Testing\Concerns;

trait CallsPrivateMethods
{
    /**
     * Calls a private/protected method on the given object.
     *
     * @param  object  $object
     * @param  string  $method
     * @param  array  $parameters
     *
     * @return mixed
     */
    protected function callPrivate($object, $method, $parameters = [])
    {
        return (function () use ($method, $parameters) {
            return $this->$method(...$parameters);
        })->call($object);
    }
}
