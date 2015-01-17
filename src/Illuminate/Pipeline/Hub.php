<?php namespace Illuminate\Pipeline;

use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Pipeline\Hub as HubContract;

class Hub implements HubContract {

	/**
	 * The container implementation.
	 *
	 * @var \Illuminate\Contracts\Container\Container
	 */
	protected $container;

	/**
	 * All of the available pipelines.
	 *
	 * @var array
	 */
	protected $pipelines = [];

	/**
	 * Create a new Depot instance.
	 *
	 * @param  \Illuminate\Contracts\Container\Container  $container
	 * @return void
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	/**
	 * Define the default named pipeline.
	 *
	 * @param  \Closure  $callback
	 * @return void
	 */
	public function defaults(Closure $callback)
	{
		return $this->pipeline('default', $callback);
	}

	/**
	 * Define a new named pipeline.
	 *
	 * @param  string  $name
	 * @param  \Closure  $callback
	 * @return void
	 */
	public function pipeline($name, Closure $callback)
	{
		$this->pipelines[$name] = $callback;
	}

	/**
	 * Send an object through one of the available pipelines.
	 *
	 * @param  mixed  $object
	 * @param  string|null  $pipeline
	 * @return mixed
	 */
	public function pipe($object, $pipeline = null)
	{
		$pipeline = $pipeline ?: 'default';

		return call_user_func(
			$this->pipelines[$pipeline], new Pipeline($this->container), $object
		);
	}

}
