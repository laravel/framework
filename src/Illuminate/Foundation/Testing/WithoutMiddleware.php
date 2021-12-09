<?php

namespace Illuminate\Foundation\Testing;

use Exception;
use Illuminate\Support\Hooks\Hook;

trait WithoutMiddleware
{
    /**
     * Register test case hook.
     *
     * @return \Illuminate\Support\Hooks\Hook
     */
    public function registerWithoutMiddlewareHook(): Hook
    {
        return new Hook('setUp', fn () => $this->disableMiddlewareForAllTests(), 65);
    }

    /**
     * Prevent all middleware from being executed for this test class.
     *
     * @throws \Exception
     */
    public function disableMiddlewareForAllTests()
    {
        if (method_exists($this, 'withoutMiddleware')) {
            $this->withoutMiddleware();
        } else {
            throw new Exception('Unable to disable middleware. MakesHttpRequests trait not used.');
        }
    }
}
