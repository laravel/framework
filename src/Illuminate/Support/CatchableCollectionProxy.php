<?php

namespace Illuminate\Support;

/**
 * @mixin \Illuminate\Collections\Enumerable
 */
class CatchableCollectionProxy
{
    /**
     * The collection being operated on.
     *
     * @var \Illuminate\Collections\Enumerable
     */
    protected $collection;

    /**
     * The collection methods to handle exceptions for.
     *
     * @var array
     */
    protected $calledMethods = [];

    /**
     * Create a new proxy instance.
     *
     * @param  \Illuminate\Collections\Enumerable  $collection
     * @return void
     */
    public function __construct(Enumerable $collection)
    {
        $this->collection = $collection;
    }

    /**
     * Proxy a method call onto the collection items.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return $this
     */
    public function __call($method, $parameters)
    {
        $this->calledMethods[] = ['name' => $method, 'parameters' => $parameters];

        return $this;
    }

    /**
     * @param \Closure[] $handlers
     *
     * @return \Illuminate\Collections\Enumerable
     */
    public function catch(...$handlers)
    {
        $originalCollection = $this->collection;

        try {
            foreach ($this->calledMethods as $calledMethod) {
                $this->collection = $this->collection->{$calledMethod['name']}(...$calledMethod['parameters']);
            }
        } catch (\Throwable $exception) {
            foreach ($handlers as $callable) {
                $type = $this->exceptionType($callable);
                if ($exception instanceof $type) {
                    return $callable($exception, $originalCollection) ?? $originalCollection;
                }
            }

            throw $exception;
        }

        return $this->collection;
    }

    private function exceptionType($callable)
    {
        $reflection = new \ReflectionFunction($callable);

        if (empty($reflection->getParameters())) {
            return \Throwable::class;
        }

        return optional($reflection->getParameters()[0]->getType())->getName() ?? \Throwable::class;
    }
}
