<?php namespace Illuminate\Queue\Jobs;

use Illuminate\Queue\RabbitQueue;
use Illuminate\Container\Container;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitJob extends Job {

	/**
	 * The job is the response from the RabbitMQ basic_get.
	 *
	 * @var \PhpAmqpLib\Message\AMQPMessage
	 */
	protected $job;

	/**
	 * The Rabbit queue instance.
	 *
	 * @var \Illuminate\Queue\RabbitQueue $rabbit
	 */
	protected $rabbit;

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
	 * @param  \Illuminate\Queue\RabbitQueue $queue
	 * @param  \PhpAmqpLib\Message\AMQPMessage $job
	 * @param  boolean $pushed
	 * @return void
	 */
	public function __construct(Container $container,
				RabbitQueue $queue,
				AMQPMessage $job,
				$pushed = false)
	{
		$this->container = $container;
		$this->rabbit = $queue;
		$this->job = $job;
		$this->pushed = $pushed;
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
		return $this->job->body;
	}

	/**
	 * Delete the job from the queue.
	 *
	 * @return void
	 */
	public function delete()
	{
		$this->rabbit->getChannel()->basic_ack($this->job->delivery_info['delivery_tag']);
	}

	/**
	 * Release the job back into the queue.
	 *
	 * @param  int   $delay
	 * @return void
	 */
	public function release($delay = 0)
	{
		if ( ! $this->pushed) $this->delete();

		$this->recreateJob($delay);
	}

	/**
	 * Release a pushed job back onto the queue.
	 *
	 * @param  int  $delay
	 * @return void
	 */
	protected function recreateJob($delay)
	{
		$payload = json_decode($this->job->body, true);

		array_set($payload, 'attempts', array_get($payload, 'attempts', 1) + 1);

		$this->rabbit->recreate(json_encode($payload), $this->getQueue(), $delay);
	}

	/**
	 * Get the number of times the job has been attempted.
	 *
	 * @return int
	 */
	public function attempts()
	{
		return array_get(json_decode($this->job->body, true), 'attempts', 1);
	}

	/**
	 * Get the job identifier.
	 *
	 * @return string
	 */
	public function getJobId()
	{
		return $this->job->prop_types['message_id'];
	}

	/**
	 * Get the underlying raw rabbit queue.
	 *
	 * @return \Illuminate\Queue\RabbitQueue
	 */
	public function getRabbit()
	{
		return $this->rabbit;
	}

	/**
	 * Get the name of the queue the job belongs to.
	 *
	 * @return string
	 */
	public function getQueue()
	{
		return array_get(json_decode($this->job->body, true), 'queue');
	}

}
