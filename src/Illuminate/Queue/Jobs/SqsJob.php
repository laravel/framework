<?php namespace Illuminate\Queue\Jobs;

use Aws\Sqs\SqsClient;
use Illuminate\Container\Container;

class SqsJob extends Job {

	/**
	 * The IoC container instance.
	 *
	 * @var \Illuminate\Container
	 */
	protected $container;

	/**
	 * The Amazon SQS client instance.
	 *
	 * @var Aws\Sqs\SqsClient
	 */
	protected $sqs;

	/**
	 * The queue URL that the job belongs to.
	 *
	 * @var string
	 */
	protected $queue;

	/**
	 * The Amazon SQS job instance.
	 *
	 * @var array
	 */
	protected $job;

	/**
	 * Create a new job instance.
	 *
	 * @param  \Illuminate\Container  $container
	 * @param  Aws\Sqs\SqsClient  $sqs
	 * @param  string  $queue
	 * @param  array   $job
	 * @return void
	 */
	public function __construct(Container $container,
                                SqsClient $sqs,
                                $queue,
                                array $job)
	{
		$this->sqs = $sqs;
		$this->job = $job;
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
		$payload = json_decode($this->job['Body'], true);

		// Once we have the message payload, we can create the given class and fire
		// it off with the given data. The data is in the messages serialized so
		// we will unserialize it and pass into the jobs in its original form.
		$this->instance = $this->container->make($payload['job']);

		$this->instance->fire($this, $payload['data']);
	}

	/**
	 * Delete the job from the queue.
	 *
	 * @return void
	 */
	public function delete()
	{
		$this->sqs->deleteMessage(array(

			'QueueUrl' => $this->queue, 'ReceiptHandle' => $this->job['ReceiptHandle'],

		));
	}

	/**
	 * Release the job back into the queue.
	 *
	 * @param  int   $delay
	 * @return void
	 */
	public function release($delay = 0)
	{
		// SQS job releases are handled by the server configuration...
	}

	/**
	 * Get the number of times the job has been attempted.
	 *
	 * @return int
	 */
	public function attempts()
	{
		return 1;
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
	 * Get the underlying SQS client instance.
	 *
	 * @return Aws\Sqs\SqsClient
	 */
	public function getSqs()
	{
		return $this->sqs;
	}

	/**
	 * Get the underlying raw SQS job.
	 *
	 * @return array
	 */
	public function getSqsJob()
	{
		return $this->job;
	}

}