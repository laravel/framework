<?php namespace Illuminate\Contracts\Bus;

interface QueueingDispatcher extends Dispatcher {

	/**
	 * Dispatch a command to its appropriate handler behind a queue.
	 *
	 * @param  mixed  $command
	 * @return mixed
	 */
	public function dispatchToQueue($command);

}
