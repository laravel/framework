<?php namespace Illuminate\Contracts\Routing;

use Closure;

interface Middleware {

	/**
	 * Handle an incoming request.
	 *
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @param  \Closure  $next
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function handle($request, Closure $next);

}
