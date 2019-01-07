<?php

namespace Illuminate\Container;

trait DecoratorsTrait
{
    /**
     * All of the decorators for method calls.
     *
     * @var array
     */
    protected $decorators = [];

    /**
     * All of the decorator names and definitions.
     *
     * @var array
     */
    protected $decorations = [];

    /**
     * Defines a new decorator with name.
     *
     * @param  string  $name
     * @param  callable  $callback
     * @return void
     */
    public function defineDecorator($name, $callback)
    {
        $this->decorators[$name] = $callback;
    }

    /**
     * Calls a class@method with it's specified decorators.
     *
     * @param  string $callback
     * @param  array  $parameters
     * @param  string|null $defaultMethod
     * @return mixed
     */
    public function callWithDecorators($callback, array $parameters = [], $defaultMethod = null)
    {
        if (is_array($callback)) {
            $callback = $this->normalizeMethod($callback);
        }

        $decorations = $this->decorations[$callback] ?? [];

        foreach ($decorations as $decoratorName => $_) {
            $decorator = $this->decorators[$decoratorName];
            $callback = $decorator($this, $callback);
        }

        return BoundMethod::call($this, $callback, $parameters, $defaultMethod);
    }

    /**
     * Decorates a callable with a defined decorator name.
     *
     * @param  string  $decorated
     * @param  string  $decorator
     * @return void
     */
    public function decorateWith($decorated, $decorator)
    {
        $this->decorations[$decorated][$decorator] = null;
    }

    public function unDecorate($decorated, $decorator = null)
    {
        if (is_null($decorator)) {
            unset($this->decorations[$decorated]);
        } else {
            unset($this->decorations[$decorated][$decorator]);
        }
    }

    private function normalizeMethod($callback)
    {
        $class = is_string($callback[0]) ? $callback[0] : get_class($callback[0]);

        return "{$class}@{$callback[1]}";
    }
}
