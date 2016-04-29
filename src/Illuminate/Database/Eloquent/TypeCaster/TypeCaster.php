<?php

namespace Illuminate\Database\Eloquent\TypeCaster;

use Str;
use Closure;
use BadMethodCallException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Container\Container;

class TypeCaster
{
    /**
     * The container instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * The Model to perform Type Casting on.
     *
     * @var array
     */
    protected $model;

    /**
     * All of the custom Type Casting extensions.
     *
     * @var array
     */
    protected $extensions = [];

    /**
     * Create a new Type Caster instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function __construct(Model $data)
    {
        $this->model = $model;
    }

    /**
     * Run the Type Caster's rule against an attribute.
     *
     * @return void
     */
    public function cast()
    {
        
    }

    /**
     * Determine if an cast has been set on an attribute.
     *
     * @param  string  $attribute
     * @return bool
     */
    public function hasCast($attribute)
    {
        return ! is_null($this->getCast($attribute));
    }

    /**
     * Get a cast type and its parameters for a given attribute.
     *
     * @param  string  $attribute
     * @return array|null
     */
    protected function getCast($attribute)
    {
        
    }

    /**
     * Get the array of the custom Type Caster extensions.
     *
     * @return array
     */
    public function getExtensions()
    {
        return $this->extensions;
    }

    /**
     * Register an array of custom Type Caster extensions.
     *
     * @param  array  $extensions
     * @return void
     */
    public function addExtensions(array $extensions)
    {
        if ($extensions) {
            $keys = array_map('\Illuminate\Support\Str::snake', array_keys($extensions));

            $extensions = array_combine($keys, array_values($extensions));
        }

        $this->extensions = array_merge($this->extensions, $extensions);
    }

    /**
     * Register a custom Type Caster extension.
     *
     * @param  string  $rule
     * @param  \Closure|string  $extension
     * @return void
     */
    public function addExtension($rule, $extension)
    {
        $this->extensions[Str::snake($rule)] = $extension;
    }

    /**
     * Set the IoC container instance.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return void
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Call a custom validator extension.
     *
     * @param  string  $rule
     * @param  array   $parameters
     * @return bool|null
     */
    protected function callExtension($rule, $parameters)
    {
        $callback = $this->extensions[$rule];

        if ($callback instanceof Closure) {
            return call_user_func_array($callback, $parameters);
        } elseif (is_string($callback)) {
            return $this->callClassBasedExtension($callback, $parameters);
        }
    }

    /**
     * Call a class based validator extension.
     *
     * @param  string  $callback
     * @param  array   $parameters
     * @return bool
     */
    protected function callClassBasedExtension($callback, $parameters)
    {
        list($class, $method) = explode('@', $callback);

        return call_user_func_array([$this->container->make($class), $method], $parameters);
    }

    /**
     * Handle dynamic calls to class methods.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        $rule = Str::snake(substr($method, 8));

        if (isset($this->extensions[$rule])) {
            return $this->callExtension($rule, $parameters);
        }

        throw new BadMethodCallException("Method [$method] does not exist.");
    }
}
