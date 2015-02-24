<?php namespace Illuminate\Contracts\Bus;

interface PipingDispatcher extends Dispatcher {

	/**
	 * Set the pipes commands should be piped through before dispatching.
	 *
	 * @param  array  $pipes
	 * @return $this
	 */
	public function pipeThrough(array $pipes);

}
