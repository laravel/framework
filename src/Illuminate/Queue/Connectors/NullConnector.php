<?php namespace Illuminate\Queue\Connectors;

use Illuminate\Queue\NullQueue;
use Illuminate\Contracts\Queue\Connector as ConnectorContract;

class NullConnector implements ConnectorContract {

	/**
	 * Establish a queue connection.
	 *
	 * @param  array  $config
	 * @return \Illuminate\Contracts\Queue\Queue
	 */
	public function connect(array $config)
	{
		return new NullQueue;
	}

}
