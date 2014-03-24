<?php

namespace Illuminate\Queue\Jobs;

use Illuminate\Container\Container;
use OpenCloud\Queues\Resource\Queue as OpenCloudQueue;
use OpenCloud\Queues\Resource\Message;

class RackspaceJob extends Job {
	/**
	 * The Rackspace OpenCloud Queue instance.
	 *
	 * @var OpenCloudQueue
	 */
	protected $openCloudQueue;

	/**
	 * The message instance.
	 *
	 * @var Message
	 */
	protected $message;

	public function __construct(Container $container, OpenCloudQueue $openCloudQueue, $queue, Message $message)
	{
		$this->openCloudQueue = $openCloudQueue;
		$this->message = $message;
		$this->queue = $queue;
		$this->container = $container;
	}

	/**
	 * Fire the job.
	 *
	 * @return void
	 */
	public function fire()
	{
		$this->resolveAndFire(json_decode($this->getRawBody(), true));
	}

	/**
	 * Release the job back into the queue.
	 *
	 * @param  int $delay
	 * @return void
	 */
	public function release($delay = 0)
	{
		$this->message->delete($this->message->getClaimIdFromHref());
	}

	/**
	 * Get the number of times the job has been attempted.
	 *
	 * @throws \RuntimeException
	 */
	public function attempts()
	{
		throw new \RuntimeException('RackspaceJob::attempts() is unsupported');
	}

	/**
	 * Get the raw body string for the job.
	 *
	 * @return string
	 */
	public function getRawBody()
	{
		return $this->message->getBody();
	}

	/**
	 * Delete the job from the queue.
	 *
	 * @return void
	 */
	public function delete()
	{
		parent::delete();

		$this->openCloudQueue->deleteMessages(array($this->message->getId()));
	}


	/**
	 * Get the job identifier.
	 *
	 * @return string
	 */
	public function getJobId()
	{
		return $this->message->getId();
	}

}
