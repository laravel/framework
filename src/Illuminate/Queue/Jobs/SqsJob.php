<?php namespace Illuminate\Queue\Jobs;

use Aws\Sqs\SqsClient;
use Illuminate\Container\Container;

class SqsJob extends Job {

	/**
	 * The Amazon SQS client instance.
	 *
	 * @var \Aws\Sqs\SqsClient
	 */
	protected $sqs;

	/**
	 * The Amazon SQS job instance.
	 *
	 * @var array
	 */
	protected $job;

    /**
     * Indicates if the message was a push message.
     *
     * @var bool
     */
    protected $pushed = false;

	/**
	 * Create a new job instance.
	 *
	 * @param  \Illuminate\Container\Container  $container
	 * @param  \Aws\Sqs\SqsClient  $sqs
	 * @param  string  $queue
	 * @param  array   $job
	 * @return void
	 */
	public function __construct(Container $container,
                                SqsClient $sqs,
                                $queue,
                                array $job,
                                $pushed = false)
	{
		$this->sqs = $sqs;
		$this->job = $job;
		$this->queue = $queue;
        $this->pushed = $pushed;
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
	 * Get the raw body string for the job.
	 *
	 * @return string
	 */
	public function getRawBody()
	{
		return $this->job['Body'];
	}

	/**
	 * Delete the job from the queue.
	 *
	 * @return void
	 */
	public function delete()
	{
		parent::delete();

        if (isset($this->job['pushed'])) return;

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
		return (int) $this->job['Attributes']['ApproximateReceiveCount'];
	}

	/**
	 * Get the job identifier.
	 *
	 * @return string
	 */
	public function getJobId()
	{
		return $this->job['MessageId'];
	}

	/**
	 * Get the IoC container instance.
	 *
	 * @return \Illuminate\Container\Container
	 */
	public function getContainer()
	{
		return $this->container;
	}

	/**
	 * Get the underlying SQS client instance.
	 *
	 * @return \Aws\Sqs\SqsClient
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