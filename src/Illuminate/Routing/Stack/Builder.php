<?php namespace Illuminate\Routing\Stack;

use Closure;

class Builder {

	/**
	 * All of the registered middlewares.
	 *
	 * @var array
	 */
	protected $middlewares = [];

	/**
	 * Push a new middleware onto the stack.
	 *
	 * @param  array|string  $middleware
	 * @return $this
	 */
	public function middleware($middleware)
	{
		$this->middlewares = array_merge($this->middlewares, (array) $middleware);

		return $this;
	}

	/**
	 * Terminate the stack build and return the Stack.
	 *
	 * @param  \Closure  $app
	 * @return \Illuminate\Routing\Stack\Stack
	 */
	public function then(Closure $app)
	{
		return new Stack($app, $this->middlewares);
	}

}
