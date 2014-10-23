<?php namespace Illuminate\Http\Middleware;

use Closure;
use Illuminate\Contracts\Routing\Middleware;

class FrameGuard implements Middleware {

	/**
	 * Handle the given request and get the response.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return \Illuminate\Http\Response
	 */
	public function handle($request, Closure $next)
	{
		$response = $next($request);

		$response->headers->set('X-Frame-Options', 'SAMEORIGIN', false);

		return $response;
	}

}
