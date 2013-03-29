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
		$sqs = SqsClient::factory(array(

			'key' => $config['key'], 'secret' => $config['secret'], 'region' => $config['region'],

		));

		return new SqsQueue($sqs, $config['queue']);
	}

}