<?php namespace Illuminate\Queue\Connectors;

use Illuminate\Contracts\Queue\Connector as ConnectorContract;
use Illuminate\Queue\SyncQueue;

class SyncConnector implements ConnectorContract {

	/**
	 * Establish a queue connection.
	 *
	 * @param  array  $config
	 * @return \Illuminate\Contracts\Queue\Queue
	 */
	public function connect(array $config)
	{
		return new SyncQueue;
	}

}
