<?php namespace Illuminate\Queue;

use Illuminate\Container;

abstract class Queue {

	/**
	 * The IoC container instance.
	 *
	 * @var Illuminate\Container
	 */
	protected $container;

	/**
	 * Create a payload string from the given job and data.
	 *
	 * @param  string  $job
	 * @param  mixed   $data
	 * @return string
	 */
	protected function createPayload($job, $data = '')
	{
		return serialize(array('job' => $job, 'data' => $data));
	}

	/**
	 * Set the IoC container instance.
	 *
	 * @param  Illuminate\Container  $container
	 * @return void
	 */
	public function setContainer(Container $container)
	{
		$this->container = $container;
	}

}