<?php

namespace Illuminate\Foundation\Testing;

use BadMethodCallException;

trait FallbackToParent
{
    /**
     * Object to fallback to if a method is not found in the current class.
     *
     * @var mixed
     */
    private $fallback;

    /**
     * Set object to fallback to if the method is not found in the current class.
     *
     * @param  mixed  $fallback
     */
    public function setFallback($fallback)
    {
        $this->fallback = $fallback;
    }

    /**
     * Redirect the call to the fallback object.
     *
     * @param  string  $method
     * @param  array  $params
     * @return mixed
     * @throws \BadMethodCallException
     */
    public function __call($method, $params = [])
    {
        if (method_exists($this->fallback, $method)) {
            return $this->fallback->$method(...$params);
        }

        throw new BadMethodCallException("The method [{$method}] does not exist.");
    }
}
