<?php

namespace Illuminate\Routing;

use Illuminate\Http\RedirectResponse;

class RedirectController extends Controller
{
    public function __invoke($destination, $status = 301)
    {
        return new RedirectResponse($destination, $status);
    }
}
