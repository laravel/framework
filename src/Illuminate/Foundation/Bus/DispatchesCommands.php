<?php namespace Illuminate\Foundation\Bus;

use ArrayAccess;
use ReflectionClass;
use ReflectionParameter;
use Illuminate\Support\Collection;

trait DispatchesCommands {

	/**
	 * Dispatch a command to its appropriate handler.
	 *
	 * @param  mixed  $command
	 * @return mixed
	 */
	protected function dispatch($command)
	{
		return app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($command);
	}

	/**
	 * Marshal a command and dispatch it to its appropriate handler.
	 *
	 * @param  mixed  $command
	 * @param  array  $array
	 * @return mixed
	 */
	protected function dispatchFromArray($command, array $array)
	{
		return app('Illuminate\Contracts\Bus\Dispatcher')->dispatchFromArray($command, $array);
	}

	/**
	 * Marshal a command and dispatch it to its appropriate handler.
	 *
	 * @param  mixed  $command
	 * @param  \ArrayAccess  $source
	 * @param  array  $extras
	 * @return mixed
	 */
	protected function dispatchFrom($command, ArrayAccess $source, $extras = [])
	{
		return app('Illuminate\Contracts\Bus\Dispatcher')->dispatchFrom($command, $source, $extras);
	}

}
