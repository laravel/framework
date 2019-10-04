<?php

namespace Illuminate\Contracts\Foundation\Testing;

interface WithoutMiddleware
{
    /**
     * Prevent all middleware from being executed for this test class.
     *
     * @throws \Exception
     */
    public function disableMiddlewareForAllTests();
}
