<?php

namespace Illuminate\Support;

use Closure;
use Illuminate\Support\Traits\ForwardsCalls;

/**
 * @template TObject
 */
class Wrap
{
    use ForwardsCalls;

    /**
     * Create a new Wrap instance.
     *
     * @param  TObject  $object
     * @param  array<string, \Closure>  $methods
     */
    public function __construct(protected $object, protected $methods = [])
    {
        //
    }

    /**
     * Registers a temporary macro to execute for the wrapped object method call.
     *
     * @param  string  $method
     * @param  \Closure  $callback
     * @return  $this
     */
    public function macro($method, Closure $callback)
    {
        $this->methods[$method] = $callback;

        return $this;
    }

    /**
     * Dynamically handle accessing the object properties.
     *
     * @param  string  $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->object->{$name};
    }

    /**
     * Dynamically handle setting the object properties.
     *
     * @param  string  $name
     * @param  mixed  $value
     * @return void
     */
    public function __set($name, $value)
    {
        $this->object->{$name} = $value;
    }

    /**
     * Dynamically handle removing a property value.
     *
     * @param  string  $name
     * @return void
     */
    public function __unset($name)
    {
        unset($this->object->{$name});
    }

    /**
     * Dynamically handle checking a property value.
     *
     * @param  string  $name
     * @return bool
     */
    public function __isset($name): bool
    {
        return isset($this->object->{$name});
    }

    /**
     * Dynamically handle calling the object methods.
     *
     * @param  string  $name
     * @param  array  $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (isset($this->methods[$name])) {
            $result = $this->methods[$name]?->call($this->object, ...$arguments);

            return $result === $this->object ? $this : $result;
        }

        return $this->forwardDecoratedCallTo($this->object, $name, $arguments);
    }

    /**
     * Create a new Wrap instance using an object.
     *
     * @template TProxied of object
     *
     * @param  TProxied  $object
     * @return static<TProxied>
     */
    public static function instance($object): static
    {
        return new static($object);
    }
}
