<?php

namespace Illuminate\Routing;

use Illuminate\Http\RedirectResponse;

class RedirectController extends Controller
{
    /**
     * Invoke the controller method.
     *
     * @param  string  $destination
     * @param  int  $status
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke($destination, $status = 301)
    {
        return new RedirectResponse($destination, $status);
    }
}
