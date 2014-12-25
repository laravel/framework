<?php namespace Illuminate\Foundation\Bus;

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

}
