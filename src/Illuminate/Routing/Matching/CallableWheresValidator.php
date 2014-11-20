<?php namespace Illuminate\Routing\Matching;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use \Closure;

class CallableWheresValidator implements ValidatorInterface {

	/**
	 * Validate a given rule against a route and request.
	 *
	 * @param  \Illuminate\Routing\Route  $route
	 * @param  \Illuminate\Http\Request   $request
	 * @return bool
	 */
	public function matches(Route $route, Request $request){
		foreach($route->getCallableWheres() as $condition){
			$route->bindParameters($request);

			$result = $this->callCondition($route, $request, $condition);

			if( ! $result) return $result;
		}


		return true;
	}

	/**
	 * @param  Route          $route
	 * @param  Request        $request
	 * @param  string|Closure $condition
	 * @return bool
	 */
	protected function callCondition(Route $route, Request $request, $condition)
	{
		if (is_string($condition)) {
			// condition is a class name with, ex callable:PageExistsWhere
			$className = ltrim($condition, 'callable:');
			$result    = (new $className)->check($route, $request);

			return $result;
		}

		// condition is a closure
		$result = $condition($route, $request);
		return $result;
	}
}
