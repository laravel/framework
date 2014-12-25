<?php namespace Illuminate\Contracts\Queue;

interface Connector {

	/**
	 * Establish a queue connection.
	 *
	 * @param  array  $config
	 * @return \Illuminate\Contracts\Queue\Queue
	 */
	public function connect(array $config);

}
