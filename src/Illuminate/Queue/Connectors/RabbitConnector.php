<?php namespace Illuminate\Queue\Connectors;

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Illuminate\Http\Request;
use Illuminate\Queue\RabbitQueue;

class RabbitConnector implements ConnectorInterface {

	/**
	 * The current request instance.
	 *
	 * @var \Illuminate\Http\Request;
	 */
	protected $request;

	/**
	 * Create a new Rabbit connector instance.
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
		$rabbitConfig = array_only($config, array('host', 'port', 'username', 'password'));

		$connection = new AMQPConnection($rabbitConfig['host'], $rabbitConfig['port'], $rabbitConfig['username'], $rabbitConfig['password']);

		return new RabbitQueue($connection, $config['queue']);
	}

}
