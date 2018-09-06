<?php

namespace Illuminate\Routing;

use Illuminate\Http\RedirectResponse;

class RedirectController extends Controller
{
    /**
     * Invoke the controller method.
     *
     * @param  array  $args
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(...$args)
    {
        [$destination, $status] = array_slice($args, -2);

        return new RedirectResponse($destination, $status);
    }
}
