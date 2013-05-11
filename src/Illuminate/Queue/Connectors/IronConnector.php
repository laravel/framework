<?php namespace Illuminate\Queue\Connectors;

use IronMQ;
use Illuminate\Http\Request;
use Illuminate\Queue\IronQueue;

class IronConnector implements ConnectorInterface {

	/**
	 * The current request instance.
	 *
	 * @var \Illuminate\Http\Request;
	 */
	protected $request;

	/**
	 * Create a new Iron connector instance.
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
		$ironConfig = array('token' => $config['token'], 'project_id' => $config['project']);

		return new IronQueue(new IronMQ($ironConfig), $this->request, $config['queue']);
	}

}