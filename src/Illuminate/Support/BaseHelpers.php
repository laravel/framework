<?php

namespace Illuminate\Support;

abstract class BaseHelpers
{
    /**
     * Handle calls to missing methods on the helper.
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        throw new BadMethodCallException(sprintf(
            'Method %s::%s does not exist.', static::class, $method
        ));
    }

    /**
     * @return New Instance Class.
     */
    public static function instance() {
        return new static();
    }
    
}

?>