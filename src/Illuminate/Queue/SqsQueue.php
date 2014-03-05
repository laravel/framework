<?php namespace Illuminate\Queue;

use Aws\Sqs\SqsClient;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Queue\Jobs\SqsJob;

class SqsQueue extends PushQueue implements QueueInterface {

	/**
	 * The Amazon SQS instance.
	 *
	 * @var \Aws\Sqs\SqsClient
	 */
	protected $sqs;

	/**
	 * The name of the default tube.
	 *
	 * @var string
	 */
	protected $default;

	/**
	 * Create a new Amazon SQS queue instance.
	 *
	 * @param  \Aws\Sqs\SqsClient  $sqs
	 * @param  \Illuminate\Http\Request  $request
	 * @param  string  $default
	 * @return void
	 */
	public function __construct(SqsClient $sqs, Request $request, $default)
	{
		$this->sqs = $sqs;
		$this->request = $request;
		$this->default = $default;
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
		return $this->pushRaw($this->createPayload($job, $data), $queue);
	}

	/**
	 * Push a raw payload onto the queue.
	 *
	 * @param  string  $payload
	 * @param  string  $queue
	 * @param  array   $options
	 * @return mixed
	 */
	public function pushRaw($payload, $queue = null, array $options = array())
	{
		$response = $this->sqs->sendMessage(array('QueueUrl' => $this->getQueue($queue), 'MessageBody' => $payload));

		return $response->get('MessageId');
	}

	/**
	 * Push a new job onto the queue after a delay.
	 *
	 * @param  \DateTime|int  $delay
	 * @param  string  $job
	 * @param  mixed   $data
	 * @param  string  $queue
	 * @return mixed
	 */
	public function later($delay, $job, $data = '', $queue = null)
	{
		$payload = $this->createPayload($job, $data);

		$delay = $this->getSeconds($delay);

		return $this->sqs->sendMessage(array(

			'QueueUrl' => $this->getQueue($queue), 'MessageBody' => $payload, 'DelaySeconds' => $delay,

		))->get('MessageId');
	}

	/**
	 * Pop the next job off of the queue.
	 *
	 * @param  string  $queue
	 * @return \Illuminate\Queue\Jobs\Job|null
	 */
	public function pop($queue = null)
	{
		$queue = $this->getQueue($queue);

		$response = $this->sqs->receiveMessage(
			array('QueueUrl' => $queue, 'AttributeNames' => array('ApproximateReceiveCount'))
		);

		if (count($response['Messages']) > 0)
		{
			return new SqsJob($this->container, $this->sqs, $response['Messages'][0], $queue);
		}
	}

	/**
	* Marshal a push queue request and fire the job.
	*
	* @return \Illuminate\Http\Response
	*/
	public function marshal()
	{
		$this->createPushedSqsJob($this->marshalPushedJob())->fire();

		return new Response('OK');
	}

	/**
	* Marshal out the pushed job and payload.
	*
	* @return array
	*/
	protected function marshalPushedJob()
	{
		$r = $this->request;

		return array(
			'MessageId' => $r->header('X-aws-sqsd-msgid'),
			'Body' => $r->getContent(),
			'Attributes' => array('ApproximateReceiveCount' => $r->header('X-aws-sqsd-receive-count')),
			'pushed' => true,
		);
	}

	/**
	* Create a new SqsJob for a pushed job.
	*
	* @param  array  $job
	* @return \Illuminate\Queue\Jobs\SqsJob
	*/
	protected function createPushedSqsJob($job)
	{
		return new SqsJob($this->container, $this->sqs, $this, $job, true);
	}

	/**
	 * Get the queue or return the default.
	 *
	 * @param  string|null  $queue
	 * @return string
	 */
	public function getQueue($queue)
	{
		return $queue ?: $this->default;
	}

	/**
	 * Get the underlying SQS instance.
	 *
	 * @return \Aws\Sqs\SqsClient
	 */
	public function getSqs()
	{
		return $this->sqs;
	}

}
