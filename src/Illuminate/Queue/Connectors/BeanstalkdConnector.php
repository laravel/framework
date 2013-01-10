<?php namespace Illuminate\Queue\Connectors;

use Pheanstalk;
use Illuminate\Queue\BeanstalkdQueue;

class BeanstalkdConnector implements ConnectorInterface {

	/**
	 * Establish a queue connection.
	 *
	 * @param  array  $config
	 * @return Illuminate\Queue\QueueInterface
	 */
	public function connect(array $config)
	{
		$pheanstalk = new Pheanstalk($config['host']);

		return new BeanstalkdQueue($pheanstalk, $config['queue']);
	}

}