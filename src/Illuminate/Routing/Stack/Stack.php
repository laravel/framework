<?php namespace Illuminate\Routing\Stack;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Container\Container;

class Stack {

	/**
	 * The container instance.
	 *
	 * @var \Illuminate\Container\Container
	 */
	protected $container;

	/**
	 * The stack "core" application callback.
	 *
	 * @var \Closure
	 */
	protected $app;

	/**
	 * The middleware stack.
	 *
	 * @var array
	 */
	protected $middlewares = array();

	/**
	 * Create a new Stack instance.
	 *
	 * @param  \Closure  $app
	 * @param  array  $middlewares
	 * @return void
	 */
	public function __construct(Closure $app, array $middlewares = array())
	{
		$this->app = $app;
		$this->middlewares = $middlewares;
	}

	/**
	 * Run the stack with the given request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return mixed
	 */
	public function run(Request $request)
	{
		$this->container = $this->container ?: new Container;

		return call_user_func(array_reduce(array_reverse($this->middlewares),
		function($stack, $middleware)
		{
			return function($request) use ($stack, $middleware)
			{
				return $this->container->make($middleware)->handle($request, $stack);
			};
		},
		function() use ($request)
		{
			return call_user_func($this->app, $request);
		}),
		$request);
	}

	public function setContainer(Container $container)
	{
		$this->container = $container;
		return $this;
	}

	public function terminate($request, $response)
	{
		//
	}

}
