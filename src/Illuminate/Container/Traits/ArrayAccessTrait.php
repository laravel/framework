<?php

namespace Illuminate\Container\Traits;

trait ArrayAccessTrait
{
    /**
     * Set the value at a given offset
     *
     * @param  string $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if (is_string($value)) {
            $this->bindPlain($offset, $value);
        } else {
            $this->bindService($offset, $value);
        }
    }

    /**
     * Get the value at a given offset
     *
     * @param  string $offset
     * @return mixed
     */
	public function offsetGet($offset)
    {
        return $this->resolve($offset);
    }

    /**
     * Unset the value at a given offset
     *
     * @param  string $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->bindings[$offset]);
    }

    /**
     * Determine if a given offset exists
     *
     * @param  string $offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return isset($this->bindings[$offset]);
    }
}
