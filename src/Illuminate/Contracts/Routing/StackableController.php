<?php namespace Illuminate\Contracts\Routing;

interface StackableController {

    /**
     * Register middleware on the controller.
     *
     * @param  dynamic  $middleware
     * @return void
     */
    public function middleware($middleware);

    /**
     * Get the middleware assigned to the controller.
     *
     * @return array
     */
    public function getMiddleware();

}
