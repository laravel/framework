<?php

namespace Illuminate\Testing;

use Illuminate\Foundation\Testing;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ParallelTesting
{
    /**
     * The token resolver callback.
     *
     * @var \Closure|null
     */
    protected $tokenResolver;

    /**
     * All of the registered "setUp" callbacks.
     *
     * @var array
     */
    protected $setUpCallbacks = [];

    /**
     * All of the registered "beforeProcessDestroyed" callbacks.
     *
     * @var array
     */
    protected $beforeProcessDestroyedCallbacks = [];

    /**
     * Set a callback that should be used when resolving the unique process token.
     *
     * @param  \Closure|null  $callback
     * @return void
     */
    public function resolveTokenUsing($resolver)
    {
        $this->tokenResolver = $resolver;
    }

    /**
     * Register a callback to run before process gets destroyed.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function beforeProcessDestroyed($callback)
    {
        $this->beforeProcessDestroyedCallbacks[] = $callback;

        return $this;
    }

    /**
     * Register a callback to run on test setup.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function setUp($callback)
    {
        $this->setUpCallbacks[] = $callback;

        return $this;
    }

    /**
     * Call all of the "beforeProcessDestroyed" callbacks.
     *
     * @return void
     */
    public function callBeforeProcessDestroyedCallbacks()
    {
        $this->whenRunningInParallel(function () {
            foreach ($this->beforeProcessDestroyedCallbacks as $callback) {
                $callback();
            }
        });
    }

    /**
     * Call all of the "setUp" callbacks.
     *
     * @param  \Illuminate\Foundation\Testing\TestCase  $testCase
     * @return void
     */
    public function callSetUpCallbacks($testCase)
    {
        $this->whenRunningInParallel(function () use ($testCase) {
            foreach ($this->setUpCallbacks as $callback) {
                $callback($testCase);
            }
        });
    }

    /**
     * Gets an unique test token.
     *
     * @return int|false
     */
    public function token()
    {
        return $token = $this->tokenResolver
            ? call_user_func($this->tokenResolver)
            : ($_SERVER['TEST_TOKEN'] ?? false);
    }

    /**
     * Apply the callback if tests are running in parallel.
     *
     * @param  callable $callback
     * @return void
     */
    protected function whenRunningInParallel($callback)
    {
        if ($this->inParallel()) {
            $callback();
        }
    }

    /**
     * Indicates if the current tests are been run in Parallel.
     *
     * @return bool
     */
    protected function inParallel()
    {
        return ! empty($_SERVER['LARAVEL_PARALLEL_TESTING']) && $this->token();
    }
}
