<?php

namespace Illuminate\Routing\Contracts;

interface MiddlewareAwareController
{
    /**
     * Get the middleware assigned to the controller.
     *
     * @return array
     */
    public function getMiddleware();
}
