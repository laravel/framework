<?php

namespace Illuminate\Routing\Contracts;

use Illuminate\Routing\Route;

interface PreparesApplication
{
    /**
     * Prepare the application state for the provided route.
     *
     * @param  \Illuminate\Routing\Route $route
     * @return void
     */
    public function prepareApplication(Route $route);
}
