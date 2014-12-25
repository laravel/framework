<?php namespace Illuminate\Queue\Connectors;

use Aws\Sqs\SqsClient;
use Illuminate\Queue\SqsQueue;
use Illuminate\Contracts\Queue\Connector as ConnectorContract;

class SqsConnector implements ConnectorContract {

	/**
	 * Establish a queue connection.
	 *
	 * @param  array  $config
	 * @return \Illuminate\Contracts\Queue\Queue
	 */
	public function connect(array $config)
	{
		$sqs = SqsClient::factory($config);

		return new SqsQueue($sqs, $config['queue']);
	}

}
