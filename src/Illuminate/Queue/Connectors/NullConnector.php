<?php namespace Illuminate\Queue\Connectors;

class NullConnector implements ConnectorInterface {

	/**
	 * Establish a queue connection.
	 *
	 * @param  array  $config
	 * @return \Illuminate\Contracts\Queue\Queue
	 */
	public function connect(array $config)
	{
		return new \Illuminate\Queue\NullQueue;
	}

}
