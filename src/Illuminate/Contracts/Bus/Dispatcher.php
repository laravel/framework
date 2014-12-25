<?php namespace Illuminate\Contracts\Bus;

interface Dispatcher {

	/**
	 * Dispatch a command to its appropriate handler.
	 *
	 * @param  mixed  $command
	 * @return mixed
	 */
	public function dispatch($command);

	/**
	 * Dispatch a command to its appropriate handler in the current process.
	 *
	 * @param  mixed  $command
	 * @return mixed
	 */
	public function dispatchNow($command);

}
