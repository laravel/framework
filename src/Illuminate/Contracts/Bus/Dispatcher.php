<?php namespace Illuminate\Contracts\Bus;

use Closure;
use ArrayAccess;

interface Dispatcher {

	/**
	 * Marshal a command and dispatch it to its appropriate handler.
	 *
	 * @param  mixed  $command
	 * @param  array  $array
	 * @return mixed
	 */
	public function dispatchFromArray($command, array $array);

	/**
	 * Marshal a command and dispatch it to its appropriate handler.
	 *
	 * @param  mixed  $command
	 * @param  array  $array
	 * @return mixed
	 */
	public function dispatchFrom($command, ArrayAccess $source, $extras = []);

	/**
	 * Dispatch a command to its appropriate handler.
	 *
	 * @param  mixed  $command
	 * @return mixed
	 */
	public function dispatch($command, Closure $afterResolving = null);

	/**
	 * Dispatch a command to its appropriate handler in the current process.
	 *
	 * @param  mixed  $command
	 * @return mixed
	 */
	public function dispatchNow($command, Closure $afterResolving = null);

}
