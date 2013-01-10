<?php namespace Illuminate\Log;

use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonologLogger;
use Monolog\Handler\RotatingFileHandler;

class Writer {

	/**
	 * The Monolog logger instance.
	 *
	 * @var Monolog\Logger
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
	 * Create a new log writer instance.
	 *
	 * @param  Monolog\Logger  $monolog
	 * @return void
	 */
	public function __construct(MonologLogger $monolog)
	{
		$this->monolog = $monolog;
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
	 * @return Monolog\Logger
	 */
	public function getMonolog()
	{
		return $this->monolog;
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
			$method = 'add'.ucfirst($method);

			return call_user_func_array(array($this->monolog, $method), $parameters);
		}

		throw new \BadMethodCallException("Method [$method] does not exist.");
	}

}