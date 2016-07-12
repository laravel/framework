<?php namespace Illuminate\Contracts\Routing;

use Closure;
use Illuminate\Http\Request;

interface Middleware {

	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle(Request $request, Closure $next);

}
