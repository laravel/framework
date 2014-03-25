<?php namespace Illuminate\Queue;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Illuminate\Queue\Jobs\RabbitJob;
use Illuminate\Http\Response;

class RabbitQueue extends Queue implements QueueInterface {

	/**
	 * The Rabbit connection
	 *
	 * @var PhpAmqpLib\Connection\AMQPConnection connection
	 */
	protected $connection;

	/**
	 * The Rabbit connection channel.
	 *
	 * @var PhpAmqpLib\Channel\AMQPChannel channel
	 */
	protected $channel;

	/**
	 * The name of the queue.
	 *
	 * @var string
	 */
	protected $queue;

	/**
	 * Create a new Rabbit queue instance.
	 *
	 * @param  PhpAmqgLib\Connection\AMQPConnection  $connection
	 * @param  string  $default
	 * @return void
	 */
	public function __construct(AMQPConnection $connection, $default)
	{
		$this->connection = $connection;
		$this->channel = $connection->channel();
		$this->queue = $default;
	}

	/**
	 * Push a new job onto the queue.
	 *
	 * @param  string  $job
	 * @param  mixed   $data
	 * @param  string  $queue
	 * @return mixed
	 */
	public function push($job, $data = '', $queue = null)
	{
		return $this->pushRaw($this->createPayload($job, $data, $queue), $queue);
	}

	/**
	 * Push a raw payload onto the queue.
	 *
	 * @param  string  $payload
	 * @param  string  $queue
	 * @param  array   $options
	 * @return \Illuminate\Http\Response
	 */
	public function pushRaw($payload, $queue = null, array $options = array())
	{
		$this->channel->queue_declare($this->getQueue($queue), false, true, false, false);

		$this->channel->basic_publish(new AMQPMessage($payload, array('delivery_mode' => 2)), '', $this->getQueue($queue));

		return new Response('OK');
	}

	/**
	 * Push a raw payload onto the queue after recreating it after some delay
	 *
	 * @param  string  $payload
	 * @param  string  $queue
	 * @param  int     $delay
	 * @return mixed
	 */
	public function recreate($payload, $queue = null, $delay)
	{
		sleep($delay);

		return $this->pushRaw($payload, $queue);
	}

	/**
         * Push a new job onto the queue after a delay.
	 *
	 * @param  \DateTime|int  $delay
         * @param  string  $job
	 * @param  mixed   $data
	 * @param  string  $queue
	 *
	 * @throws \RuntimeException
	 */
	public function later($delay, $job, $data = '', $queue = null)
	{
		throw new \RuntimeException('RabbitQueue::later() method is not supported');
	}

	/**
	 * Pop the next job off of the queue.
	 *
	 * @param  string  $queue
	 * @return \Illuminate\Queue\Jobs\RabbitJob|null
         */
	public function pop($queue = null)
	{
                $this->queue = $this->getQueue($queue);

		$this->channel->queue_declare($this->getQueue($queue), false, true, false, false);

		$job = $this->channel->basic_get($this->getQueue($queue));

		if ( ! isset($job)) return null;

		return $this->createRabbitJob($job);
	}

	/**
	 * Create a payload string from the given job and data.
	 *
	 * @param  string  $job
	 * @param  mixed   $data
	 * @param  string  $queue
	 * @return string
	 */
	protected function createPayload($job, $data = '', $queue = null)
	{
		$payload = $this->setMeta(parent::createPayload($job, $data), 'attempts', 1);

		return $this->setMeta($payload, 'queue', $this->getQueue($queue));
	}

	/**
         * Create a new RabbitJob.
	 *
	 * @param  PhpAmqpLib\Message\AMQPMessage $job
	 * @param  bool   $pushed
	 * @return \Illuminate\Queue\Jobs\RabbitJob
	 */
	protected function createRabbitJob($job, $pushed = false)
        {
		return new RabbitJob($this->container, $this, $job, $pushed);
	}

	/**
	 * Get the queue or return the default queue.
	 *
	 * @param  string|null  $queue
	 * @return string
	 */
	public function getQueue($queue)
	{
		return $queue ?: $this->queue;
	}

	/**
	 * Get the underlying Rabbit connection channel instance.
	 *
	 * @return PhpAmqpLib\Channel\AMQPChannel
	 */
	public function getChannel()
	{
		return $this->channel;
	}

}
