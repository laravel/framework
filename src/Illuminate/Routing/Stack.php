<?php namespace Illuminate\Routing;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Contracts\Container\Container;

class Stack {

	/**
	 * The container instance.
	 *
	 * @var \Illuminate\Contracts\Container\Container
	 */
	protected $container;

	/**
	 * The request instance.
	 *
	 * @var \Illuminate\Http\Request
	 */
	protected $request;

	/**
	 * The middleware stack.
	 *
	 * @var array
	 */
	protected $middleware = array();

	/**
	 * Create a new Stack instance.
	 *
	 * @param  \Closure  $app
	 * @param  array  $middlewares
	 * @return void
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	/**
	 * Set the request being sent through the stack.
	 *
	 * @param  \Illuminate\Http\Request
	 * @return $this
	 */
	public function send(Request $request)
	{
		$this->request = $request;

		return $this;
	}

	/**
	 * Set the layers / middleware of the stack.
	 *
	 * @param  array  $middleware
	 * @return $this
	 */
	public function through(array $middleware)
	{
		$this->middleware = $middleware;

		return $this;
	}

	/**
	 * Run the stack with the given request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return mixed
	 */
	public function then(Closure $app)
	{
		$firstSlice = $this->getInitialSlice($app);

		$middleware = array_reverse($this->middleware);

		return call_user_func(
			array_reduce($middleware, $this->getSlice(), $firstSlice), $this->request
		);
	}

	/**
	 * Get a Closure that represents a slice of the application onion.
	 *
	 * @return \Closure
	 */
	protected function getSlice()
	{
		return function($stack, $middleware)
		{
			return function($request) use ($stack, $middleware)
			{
				return $this->container->make($middleware)->handle($request, $stack);
			};
		};
	}

	/**
	 * Get the initial slice to begin the stack call.
	 *
	 * @param  \Closure  $app
	 * @return \Closure
	 */
	protected function getInitialSlice(Closure $app)
	{
		return function() use ($app)
		{
			return call_user_func($app, $this->request);
		};
	}

}
