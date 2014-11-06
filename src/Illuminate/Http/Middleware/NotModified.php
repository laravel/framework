<?php namespace Illuminate\Http\Middleware;

use Closure;
use Illuminate\Contracts\Routing\Middleware;

class NotModified implements Middleware {

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

		if ( ! $response->headers->has('Etag'))
		{
			$response->setEtag(md5($response->getContent()));
		}

		$response->isNotModified($request);

		return $response;
	}

}
