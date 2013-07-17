<?php namespace Illuminate\Queue\Jobs;

use WindowsAzure\ServiceBus\Models\BrokeredMessage;
use WindowsAzure\ServiceBus\ServiceBusRestProxy;
use Illuminate\Container\Container;

class AzureJob extends Job {

	/**
	 * The Microsoft Azure client instance.
	 *
	 * @var WindowsAzure\ServiceBus\ServiceBusRestProxy
	 */
	protected $azure;

	/**
	 * The queue URL that the job belongs to.
	 *
	 * @var string
	 */
	protected $queue;

	/**
	 * The Microsoft Azure job instance.
	 *
	 * @var WindowsAzure\ServiceBus\Models\BrokeredMessage
	 */
	protected $job;

	/**
	 * Create a new job instance.
	 *
	 * @param  \Illuminate\Container\Container  $container
	 * @param  \WindowsAzure\Common\ServicesBuilder  $azure
	 * @param  string  $queue
	 * @param  WindowsAzure\ServiceBus\Models\BrokeredMessage   $job
	 * @return void
	 */
	public function __construct(Container $container,
                                ServiceBusRestProxy $azure,
                                BrokeredMessage $job)
	{
		$this->azure = $azure;
		$this->job = $job;
		$this->container = $container;
	}

	/**
	 * Fire the job.
	 *
	 * @return void
	 */
	public function fire()
	{
		$this->resolveAndFire(json_decode($this->job->getBody(), true));
	}

	/**
	 * Delete the job from the queue.
	 *
	 * @return void
	 */
	public function delete()
	{
		$this->azure->deleteMessage($this->job);
	}

	/**
	 * Release the job back into the queue.
	 *
	 * @param  int   $delay
	 * @return void
	 */
	public function release($delay = 0)
	{
		$this->job->setScheduledEnqueueTimeUtc(DATE_ISO8601, time()+$delay);
	}

	/**
	 * Get the number of times the job has been attempted.
	 *
	 * @return int
	 */
	public function attempts()
	{
		return $this->job->getDeliveryCount();
	}

	/**
	 * Get the job identifier.
	 *
	 * @return string
	 */
	public function getJobId()
	{
		return $this->job->getMessageId();
	}

	/**
	 * Get the IoC container instance.
	 *
	 * @return \Illuminate\Container
	 */
	public function getContainer()
	{
		return $this->container;
	}

	/**
	 * Get the underlying Azure service instance.
	 *
	 * @return \WindowsAzure\Common\ServicesBuilder
	 */
	public function getAzure()
	{
		return $this->azure;
	}

	/**
	 * Get the underlying raw Azure job.
	 *
	 * @return array
	 */
	public function getAzureJob()
	{
		return $this->job;
	}

}