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
     * All of the registered "setUp" test case callbacks.
     *
     * @var array
     */
    protected $setUpTestCaseCallbacks = [];

    /**
     * All of the registered "tearDown" process callbacks.
     *
     * @var array
     */
    protected $tearDownProcessCallbacks = [];

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
     * Register a "setUp" test case callback.
     *
     * @param  callable  $callback
     * @return void
     */
    public function setUpTestCase($callback)
    {
        $this->setUpTestCaseCallbacks[] = $callback;
    }

    /**
     * Register a "tearDown" process callback.
     *
     * @param  callable  $callback
     * @return void
     */
    public function tearDownProcess($callback)
    {
        $this->tearDownProcessCallbacks[] = $callback;
    }

    /**
     * Call all of the "tearDown" process callbacks.
     *
     * @return void
     */
    public function callTearDownProcessCallbacks()
    {
        $this->whenRunningInParallel(function () {
            foreach ($this->tearDownProcessCallbacks as $callback) {
                $callback();
            }
        });
    }

    /**
     * Call all of the "setUp" test case callbacks.
     *
     * @param  \Illuminate\Foundation\Testing\TestCase  $testCase
     * @return void
     */
    public function callSetUpTestCaseCallbacks($testCase)
    {
        $this->whenRunningInParallel(function () use ($testCase) {
            foreach ($this->setUpTestCaseCallbacks as $callback) {
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
