<?php namespace Illuminate\Queue\Connectors;

use Aws\Sqs\SqsClient;
use Illuminate\Queue\SqsQueue;

class SqsConnector implements ConnectorInterface {

	/**
	 * Establish a queue connection.
	 *
	 * @param  array  $config
	 * @return \Illuminate\Queue\QueueInterface
	 */
	public function connect(array $config)
	{
		$sqsConfig = array_only($config, array('key', 'secret', 'region', 'default_cache_config'));

		$sqs = SqsClient::factory($sqsConfig);

		return new SqsQueue($sqs, $config['queue']);
	}

}
