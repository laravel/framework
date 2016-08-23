<?php

namespace Illuminate\Foundation\Http;

use Illuminate\Http\RedirectResponse;

class RedirectController
{
    /**
     * Handle a redirect.
     *
     * @param  string  $destination
     * @param  int  $status
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handle($destination, $status)
    {
        return new RedirectResponse($destination, $status);
    }

    /**
     * Extract the redirect data from the route and call the handler.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @param  \Illuminate\Routing\Route $route
     * @return \Illuminate\Http\RedirectResponse
     */
    public function callAction($method, $parameters, $route)
    {
        return $this->handle($route->getData('destination'), $route->getData('status'));
    }
}
