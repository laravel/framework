<?php namespace Illuminate\Queue\Connectors;

use Aws\Sns\SnsClient;
use Aws\Sqs\SqsClient;
use Illuminate\Http\Request;
use Illuminate\Queue\SqsQueue;

class SqsConnector implements ConnectorInterface {

	/**
	 * The current request instance.
	 *
	 * @var \Illuminate\Http\Request;
	 */
	protected $request;

	/**
	 * Create a new Sqs connector instance.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return void
	 */
	public function __construct(Request $request)
	{
		$this->request = $request;
	}

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

		$sns = SnsClient::factory($sqsConfig);

		return new SqsQueue($sqs, $sns, $this->request, $config['queue'], $config['account']);
	}

}
