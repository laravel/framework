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
	 * @param  \ArrayAccess  $source
	 * @param  array  $extras
	 * @return mixed
	 */
	public function dispatchFrom($command, ArrayAccess $source, array $extras = []);

	/**
	 * Dispatch a command to its appropriate handler.
	 *
	 * @param  mixed  $command
	 * @param  \Closure|null  $afterResolving
	 * @return mixed
	 */
	public function dispatch($command, Closure $afterResolving = null);

	/**
	 * Dispatch a command to its appropriate handler in the current process.
	 *
	 * @param  mixed  $command
	 * @param  \Closure|null  $afterResolving
	 * @return mixed
	 */
	public function dispatchNow($command, Closure $afterResolving = null);

}
