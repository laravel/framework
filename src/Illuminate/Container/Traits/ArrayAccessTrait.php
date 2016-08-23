<?php

namespace Illuminate\Container\Traits;

trait ArrayAccessTrait
{
    public function offsetSet($offset, $value)
    {
        if (is_string($value)) {
            $this->bindPlain($offset, $value);
        } else {
            $this->bindService($offset, $value);
        }
    }

	public function offsetGet($offset)
    {
        return $this->resolve($offset);
    }

    public function offsetUnset($offset)
    {
        unset($this->bindings[$offset]);
    }

    public function offsetExists($offset)
    {
        return isset($this->bindings[$offset]);
    }
}
