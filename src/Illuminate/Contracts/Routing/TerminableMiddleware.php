<?php namespace Illuminate\Contracts\Routing;

interface TerminableMiddleware extends Middleware {

	/**
	 * Perform any final actions for the request lifecycle.
	 *
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @param  \Symfony\Component\HttpFoundation\Response  $response
	 * @return void
	 */
	public function terminate($request, $response);

}
