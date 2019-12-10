<?php

namespace Illuminate\Routing;

use Illuminate\Http\Request;

class GoneController extends Controller
{
    /**
     * Invoke the controller method.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $view = "errors::410";
        if(view()->exists($view)){
            return response()->view($view, [], 410);
        }

        return response(__("Gone"), 410);
    }
}
