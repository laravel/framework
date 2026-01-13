<?php

namespace Illuminate\Support;

use Closure;
use Illuminate\Support\Traits\ForwardsCalls;
use ValueError;

/**
 * @template TObject
 */
class Wrap
{
    use ForwardsCalls;

    /**
     * Create a new Proxyable instance.
     *
     * @param  TObject  $object
     * @param  array<string, \Closure>  $methods
     */
    public function __construct(protected $object, protected $methods = [])
    {
        //
    }

    /**
     * Captures a method call and executes a callback with the arguments and object.
     *
     * @param  string  $method
     * @param  \Closure  $callback
     * @return  $this
     */
    public function capture($method, Closure $callback)
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
     * Dynamically handle removing a property value
     *
     * @param  string  $name
     * @return void
     */
    public function __unset($name)
    {
        unset($this->object->{$name});
    }

    /**
     * Dynamically handle removing a property value
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
     * Create a new Proxyable instance using an object.
     *
     * @template TProxied of object
     *
     * @param  TProxied  $object
     * @return static<TProxied>
     */
    public static function object(object $object): static
    {
        return new static($object);
    }
}
