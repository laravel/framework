<?php namespace Illuminate\Routing\Matching;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;

class UriValidator implements ValidatorInterface {

	/**
	 * Validate a given rule against a route and request.
	 *
	 * @param  \Illuminate\Routing\Route  $route
	 * @param  \Illuminate\Http\Request  $request
	 * @return bool
	 */
	public function matches(Route $route, Request $request)
	{
		//dd($route->uriExpression());
		//return dd(preg_match('#^(\/)$|^(?:([a-zA-Z0-9\.\-_%=]+))?$#u', 'foo/bar'));
		return preg_match($route->uriExpression(), $request->path());
	}

}