<?php namespace Illuminate\Queue\Connectors;

use Aws\Sqs\SqsClient;
use Illuminate\Queue\SqsQueue;

class SqsConnector implements ConnectorInterface {

	/**
	 * Establish a queue connection.
	 *
	 * @param  array  $config
	 * @return \Illuminate\Contracts\Queue\Queue
	 */
	public function connect(array $config)
	{
		// Adjust configuration for V3 of the AWS SDK.
		if (defined('Aws\Sdk::VERSION')) {
			$config += [
				'version' => 'latest',
				'credentials' => [
					'key'    => $config['key'],
					'secret' => $config['secret'],
				],
			];
			unset($config['key'], $config['secret']);
		}

		$sqs = SqsClient::factory($config);

		return new SqsQueue($sqs, $config['queue']);
	}

}
