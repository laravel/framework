<?php namespace Illuminate\Queue\Jobs;

use RuntimeException;
use Illuminate\Queue\SqsQueue;
use Illuminate\Container\Container;

class SqsJob extends Job {

	/**
	 * The name of the queue the job belongs to.
	 *
	 * @var string
	 */
	protected $queue;

	/**
	 * The SqsQueue instance
	 *
	 * @var \Illuminate\Queue\SqsQueue
	 */
	protected $sqsQueue;

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
	protected $pushed;

	/**
	 * Create a new job instance.
	 *
	 * @param  \Illuminate\Container\Container  $container
	 * @param  \Illuminate\Queue\SqsQueue  $queue
	 * @param  array   $job
	 * @param  boolean $pushed
	 * @return void
	 */
	public function __construct(Container $container,
                                SqsQueue $queue,
                                array $job,
				$pushed = false)
	{
		$this->sqsQueue = $queue;
		$this->queue = $this->sqsQueue->getQueue();
		$this->job = $job;
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
		$queueUrl = $this->sqsQueue->getQueueUrl();
	
		parent::delete();

		if ($this->pushed) 
		{
			$r = $this->sqsQueue->getRequest();

			$topic = $this->parseTopicArn($r, 'topic');

			$queueUrl = $this->sqsQueue->getQueueUrl($topic);
	
			$response = $this->sqsQueue->getSqs()->receiveMessage(array(

				'QueueUrl' => $queueUrl
			));

			$receiptHandle = $response->toArray()['Messages'][0]['ReceiptHandle'];
		} 
		else 
		{
			$queueUrl = $this->sqsQueue->getQueueUrl($this->queue);

			$receiptHandle = $this->job['ReceiptHandle'];
		}

		$this->sqsQueue->getSqs()->deleteMessage(array(

			'QueueUrl' => $queueUrl, 'ReceiptHandle' => $receiptHandle 
		));
	}

	/**
	 * Parse the topic arn for a specific piece of data
	 * 
	 * @param  string  $piece
	 * @return string
	 */
	public function parseTopicArn($request, $piece)
	{
		$pieces = array('arn', 'aws', 'service', 'region', 'account', 'topic');

		if( ! in_array($piece, $pieces)) throw new RuntimeException("The target piece is not part of the TopicArn."); 

		list($arn, $aws, $service, $region, $account, $topic) = explode(":", $request->header('x-amz-sns-topic-arn'));
	
		return compact($pieces)[$piece];
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
	 * Get the underlying raw SQS job.
	 *
	 * @return array
	 */
	public function getSqsJob()
	{
		return $this->job;
	}

	/**
	 * Get the underlying raw SqsQueue.
	 *
	 * @return Illuminate\Queue\SqsQueue
	 */
	public function getSqsQueue()
	{
		return $this->sqsQueue;
	}

}
