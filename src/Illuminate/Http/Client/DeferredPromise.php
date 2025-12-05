<?php

namespace Illuminate\Http\Client;

class DeferredPromise
{
    /**
     * Reference to the pool/batch requests array.
     *
     * @var array
     */
    protected $requests;

    /**
     * The key for this request in the pool/batch.
     *
     * @var string|int
     */
    protected $key;

    /**
     * The base promise closure that will execute the request.
     *
     * @var callable
     */
    protected $promiseFactory;

    /**
     * Additional promise transformations to apply.
     *
     * @var array<callable>
     */
    protected $transformations = [];

    /**
     * @param  array  &$requests  Reference to the pool/batch requests array
     * @param  string|int  $key  The key for this request
     * @param  callable  $promiseFactory  Closure that returns a promise
     */
    public function __construct(array &$requests, $key, callable $promiseFactory)
    {
        $this->requests = &$requests;
        $this->key = $key;
        $this->promiseFactory = $promiseFactory;

        // Store the factory in the requests array
        $this->updateRequestsClosure();
    }

    /**
     * Add a promise transformation (like ->then()).
     *
     * @param  callable|null  $onFulfilled
     * @param  callable|null  $onRejected
     * @return $this
     */
    public function then(?callable $onFulfilled = null, ?callable $onRejected = null)
    {
        $this->transformations[] = ['method' => 'then', 'args' => [$onFulfilled, $onRejected]];
        $this->updateRequestsClosure();

        return $this;
    }

    /**
     * Add an otherwise transformation.
     *
     * @param  callable  $onRejected
     * @return $this
     */
    public function otherwise(callable $onRejected)
    {
        $this->transformations[] = ['method' => 'otherwise', 'args' => [$onRejected]];
        $this->updateRequestsClosure();

        return $this;
    }

    /**
     * Update the closure stored in the requests array to include all transformations.
     *
     * @return void
     */
    protected function updateRequestsClosure()
    {
        $promiseFactory = $this->promiseFactory;
        $transformations = $this->transformations;

        $this->requests[$this->key] = function () use ($promiseFactory, $transformations) {
            $promise = $promiseFactory();

            foreach ($transformations as $transformation) {
                $promise = $promise->{$transformation['method']}(...$transformation['args']);
            }

            return $promise;
        };
    }
}
