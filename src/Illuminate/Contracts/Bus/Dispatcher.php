<?php namespace Illuminate\Contracts\Bus;

use Closure;

interface Dispatcher {

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
