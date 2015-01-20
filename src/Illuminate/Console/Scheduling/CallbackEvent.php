<?php namespace Illuminate\Console\Scheduling;

use InvalidArgumentException;
use Illuminate\Contracts\Container\Container;

class CallbackEvent extends Event {

	/**
	 * The callback to call.
	 *
	 * @var string
	 */
	protected $callback;

	/**
	 * The parameters to pass to the method.
	 *
	 * @var array
	 */
	protected $parameters;

	/**
	 * Create a new event instance.
	 *
	 * @param  string  $callback
	 * @param  array  $parameters
	 * @return void
	 */
	public function __construct($callback, array $parameters = array())
	{
		$this->callback = $callback;
		$this->parameters = $parameters;

		if ( ! is_string($this->callback) && ! is_callable($this->callback))
		{
			throw new InvalidArgumentException(
				"Invalid scheduled callback event. Must be string or callable."
			);
		}
	}

	/**
	 * Run the given event.
	 *
	 * @param  \Illuminate\Contracts\Container\Container  $container
	 * @return mixed
	 */
	public function run(Container $container)
	{
		return $container->call($this->callback, $this->parameters);
	}

	/**
	 * Get the summary of the event for display.
	 *
	 * @return string
	 */
	public function getSummaryForDisplay()
	{
		if (is_string($this->description)) return $this->description;

		return is_string($this->callback) ? $this->callback : 'Closure';
	}

}
