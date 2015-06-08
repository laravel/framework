<?php

namespace Illuminate\Contracts\Routing;

/**
 * @deprecated since version 5.1.
 */
interface TerminableMiddleware extends Middleware
{
    /**
     * Perform any final actions for the request lifecycle.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return void
     */
    public function terminate($request, $response);
}
