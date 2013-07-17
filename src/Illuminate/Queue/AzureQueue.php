<?php namespace Illuminate\Queue;

use WindowsAzure\ServiceBus\Models\BrokeredMessage;
use WindowsAzure\ServiceBus\Models\QueueInfo;
use WindowsAzure\ServiceBus\Models\ReceiveMessageOptions;
use WindowsAzure\ServiceBus\ServiceBusRestProxy;
use Illuminate\Queue\Jobs\AzureJob;

class AzureQueue extends Queue implements QueueInterface {

	/**
	 * The Microsoft Azure instance.
	 *
	 * @var WindowsAzure\ServiceBus\ServiceBusRestProxy
	 */
	protected $azure;

	/**
	 * The name of the default tube.
	 *
	 * @var string
	 */
	protected $default;

	/**
	 * Create a new Microsoft Azure queue instance.
	 *
	 * @param  WindowsAzure\ServiceBus\ServiceBusRestProxy  $azure
	 * @param  string  $default
	 * @return void
	 */
	public function __construct(ServiceBusRestProxy $azure, $default)
	{
		$this->azure = $azure;
		$this->default = $default;
	}

	/**
	 * Push a new job onto the queue.
	 *
	 * @param  string  $job
	 * @param  mixed   $data
	 * @param  string  $queue
	 * @return void
	 */
	public function push($job, $data = '', $queue = null)
	{
		if (is_null($queue)) $queue = $this->getQueue($queue);
		$payload = $this->createPayload($job, $data);

	    $message = new BrokeredMessage();
	    $message->setBody($payload);

	    $this->azure->sendQueueMessage($queue, $message);
	}

	/**
	 * Push a new job onto the queue after a delay.
	 *
	 * @param  int     $delay
	 * @param  string  $job
	 * @param  mixed   $data
	 * @param  string  $queue
	 * @return void
	 */
	public function later($delay, $job, $data = '', $queue = null)
	{
		if (is_null($queue)) $queue = $this->getQueue($queue);
		$payload = $this->createPayload($job, $data);

	    $message = new BrokeredMessage();
	    $message->setBody($payload);
	    $message->setScheduledEnqueueTimeUtc(DATE_ISO8601, time()+$delay);

	    $this->azure->sendQueueMessage($queue, $message);
	}

	/**
	 * Pop the next job off of the queue.
	 *
	 * @param  string  $queue
	 * @return \Illuminate\Queue\Jobs\Job|null
	 */
	public function pop($queue = null)
	{
		$options = new ReceiveMessageOptions();
    	$options->setPeekLock(true);

		$queue = $this->getQueue($queue);
	    $message = $this->azure->receiveQueueMessage($queue, $options);

	    if (is_null($message)) return null;

		return new AzureJob($this->container, $this->azure, $message);
	}

	/**
	 * Get the queue or return the default.
	 *
	 * @param  string|null  $queue
	 * @return string
	 */
	protected function getQueue($queue)
	{
		return $queue ?: $this->default;
	}

	/**
	 * Get the underlying Azure instance.
	 *
	 * @return WindowsAzure\Common\ServicesBuilder
	 */
	public function getAzure()
	{
		return $this->azure;
	}

}
