<?php namespace Illuminate\Log;

use Closure;
use Illuminate\Events\Dispatcher;
use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonologLogger;
use Monolog\Handler\RotatingFileHandler;

class Writer {

	/**
	 * The Monolog logger instance.
	 *
	 * @var \Monolog\Logger
	 */
	protected $monolog;

	/**
	 * All of the error levels.
	 *
	 * @var array
	 */
	protected $levels = array(
		'debug',
		'info',
		'notice',
		'warning',
		'error',
		'critical',
		'alert',
		'emergency',
	);

	/**
	 * The event dispatcher instance.
	 *
	 * @var \Illuminate\Events\Dispacher
	 */
	protected $dispatcher;

	/**
	 * Create a new log writer instance.
	 *
	 * @param  \Monolog\Logger  $monolog
	 * @param  \Illuminate\Events\Dispatcher  $dispatcher
	 * @return void
	 */
	public function __construct(MonologLogger $monolog, Dispatcher $dispatcher = null)
	{
		$this->monolog = $monolog;

		if (isset($dispatcher))
		{
			$this->dispatcher = $dispatcher;
		}
	}

	/**
	 * Register a file log handler.
	 *
	 * @param  string  $path
	 * @param  string  $level
	 * @return void
	 */
	public function useFiles($path, $level = 'debug')
	{
		$level = $this->parseLevel($level);

		$this->monolog->pushHandler(new StreamHandler($path, $level));
	}

	/**
	 * Register a daily file log handler.
	 *
	 * @param  string  $path
	 * @param  int     $days
	 * @param  string  $level
	 * @return void
	 */
	public function useDailyFiles($path, $days = 0, $level = 'debug')
	{
		$level = $this->parseLevel($level);

		$this->monolog->pushHandler(new RotatingFileHandler($path, $days, $level));
	}

	/**
	 * Parse the string level into a Monolog constant.
	 *
	 * @param  string  $level
	 * @return int
	 */
	protected function parseLevel($level)
	{
		switch ($level)
		{
			case 'debug':
				return MonologLogger::DEBUG;

			case 'info':
				return MonologLogger::INFO;

			case 'notice':
				return MonologLogger::NOTICE;

			case 'warning':
				return MonologLogger::WARNING;

			case 'error':
				return MonologLogger::ERROR;

			case 'critical':
				return MonologLogger::CRITICAL;

			case 'alert':
				return MonologLogger::ALERT;

			case 'emergency':
				return MonologLogger::EMERGENCY;

			default:
				throw new \InvalidArgumentException("Invalid log level.");
		}
	}

	/**
	 * Get the underlying Monolog instance.
	 *
	 * @return \Monolog\Logger
	 */
	public function getMonolog()
	{
		return $this->monolog;
	}

	/**
	 * Register a new callback handler for when
	 * a log event is triggered.
	 *
	 * @param  Closure  $callback
	 * @return void
	 */
	public function listen(Closure $callback)
	{
		if ( ! isset($this->dispatcher))
		{
			throw new \RuntimeException("Events dispatcher has not been set.");
		}

		$this->dispatcher->listen('illuminate.log', $callback);
	}

	/**
	 * Get the event dispatcher instance.
	 *
	 * @return \Illuminate\Events\Dispatcher
	 */
	public function getEventDispatcher()
	{
		return $this->dispatcher;
	}

	/**
	 * Set the event dispatcher instance.
	 *
	 * @param  \Illuminate\Events\Dispatcher
	 * @return void
	 */
	public function setEventDispatcher(Dispatcher $dispatcher)
	{
		$this->dispatcher = $dispatcher;
	}

	/**
	 * Fires a log event.
	 *
	 * @param  string  $level
	 * @param  array   $parameters
	 * @return void
	 */
	protected function fireLogEvent($level, $message, array $context = array())
	{
		// If the event dispatcher is set, we will pass along the parameters to the
		// log listeners. These are useful for building profilers or other tools
		// that aggregate all of the log messages for a given "request" cycle.
		if (isset($this->dispatcher))
		{
			$this->dispatcher->fire('illuminate.log', compact('level', 'message', 'context'));
		}
	}

	/**
	 * Dynamically handle error additions.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		if (in_array($method, $this->levels))
		{
			call_user_func_array(array($this, 'fireLogEvent'), array_merge(array($method), $parameters));

			$method = 'add'.ucfirst($method);

			return call_user_func_array(array($this->monolog, $method), $parameters);
		}

		throw new \BadMethodCallException("Method [$method] does not exist.");
	}

}
