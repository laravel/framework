<?php namespace Illuminate\Queue;

use RuntimeException;
use Aws\Sns\SnsClient;
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
	 * The Amazon SNS instance.
	 *
	 * @var \Aws\Sns\SnsClient
	 */
	protected $sns;

	/**
	 * The name of the default queue.
	 *
	 * @var string
	 */
	protected $queue;

	/**
	 * The account associated with the default queue
	 *
	 * @var string
	 */
	protected $account;

	/**
	 * Create a new Amazon SQS queue instance.
	 *
	 * @param  \Aws\Sqs\SqsClient  $sqs 
	 * @param  \Aws\Sns\SnsClient  $sns
	 * @param  \Illuminate\Http\Request  $request
	 * @param  string  $queue
	 * @param  string  $account
	 * @return void
	 */
	public function __construct(SqsClient $sqs, SnsClient $sns, Request $request, $queue, $account)
	{
		$this->sqs = $sqs;
		$this->sns = $sns;
		$this->request = $request;
		$this->queue = $queue;
		$this->account = $account;
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
		$response = $this->sqs->sendMessage(array('QueueUrl' => $this->getQueueUrl($queue), 'MessageBody' => $payload));

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

			'QueueUrl' => $this->getQueueUrl($queue), 'MessageBody' => $payload, 'DelaySeconds' => $delay,

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
		$this->queue = $queue;

		$queueUrl = $this->getQueueUrl($this->queue);

		$response = $this->sqs->receiveMessage(
			array('QueueUrl' => $queueUrl, 'AttributeNames' => array('ApproximateReceiveCount'))
		);

		if (count($response['Messages']) > 0)
		{
			return $this->createSqsJob($response['Messages'][0]);
		}
	}

	/**
	 * Marshal a push queue request and fire the job.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function marshal()
	{
		$r = $this->request;

		if($r->header('x-amz-sns-message-type') == 'SubscriptionConfirmation') 
		{
			$response = $this->getSns()->confirmSubscription(array('TopicArn' => $r->json('TopicArn'), 'Token' => $r->json('Token'), 'AuthenticateOnUnsubscribe' => 'true'));
		} 
		else 
		{
			$this->createSqsJob($this->marshalPushedJob(), $pushed = true)->fire();
		}

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
			'MessageId' => $this->parseOutMessageId($r),
			'Body' => $this->parseOutMessage($r)
		);
	}

	/**
	 * Create a new SqsJob.
	 *
	 * @param  array  $job
	 * @param  bool	  $pushed 
	 * @return \Illuminate\Queue\Jobs\SqsJob
	 */
	protected function createSqsJob($job, $pushed = false)
	{
		return new SqsJob($this->container, $this, $job, $pushed);
	}

	/**
	 * Get the queue name
	 *
	 * @return string
	 */
	public function getQueue()
	{
		return $this->queue;
	}

	/**
	 * Get the full queue url based on the one passed in or the default.
	 *
	 * @param  string|null  $queue
	 * @return string
	 */
	public function getQueueUrl($queue = null)
	{
		return $this->sqs->getBaseUrl() . '/' . $this->account . '/' . ($queue ?: $this->queue);
	}

	/**
	 * Parse out the appropriate message id from the request header
	 *
	 * @param  Request $request
	 * @return string
	 */
	protected function parseOutMessageId($request)
	{
		$snsMessageId = $request->header('x-amz-sns-message-id');
		$sqsMessageId = $request->header('x-aws-sqsd-msgid');

		if(($sqsMessageId == null) && ($snsMessageId == null))
		{
			throw new RuntimeException("The marshaled job must come from either SQS or SNS.");	
		}

		return $snsMessageId ?: $sqsMessageId;
	}

	/**
	 * Parse out the message from the request
	 *
	 * @param  Request $request
	 * @return string
	 */
	protected function parseOutMessage($request)
	{
		return stripslashes($request->json('Message'));
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

	/**
	 * Get the underlying SNS instance.
	 *
	 * @return \Aws\Sns\SnsClient
	 */
	public function getSns()
	{
		return $this->sns;
	}

	/**
	 * Get the queue options
	 *
	 * @param string $queue
	 * @param string $endpoint
	 * @param array  $options
	 * @param array  $advanced
	 * @return array
	 */
	protected function getQueueOptions($queue, $endpoint, $options, $advanced)
	{
		$standardOptions = $this->getStandardOptions($queue, $endpoint, $options);		

		$newDeliveryPolicy = array('healthyRetryPolicy' => array('numRetries' => intval($standardOptions['retries'])));

		if (isset($advanced['healthyRetryPolicy'])) 
		{
			$newDeliveryPolicy['healthyRetryPolicy'] = array_merge($newDeliveryPolicy['healthyRetryPolicy'], $advanced['healthyRetryPolicy']);	
		}

		if (isset($advanced['throttlePolicy']))
		{
			$newDeliveryPolicy['throttlePolicy'] = $advanced['throttlePolicy'];
		}

		$newRedrivePolicy = array();

		if ($standardOptions['error_queue'] != '')
		{
			$newRedrivePolicy['maxReceiveCount'] = 5;
			$newRedrivePolicy['deadLetterTargetArn'] = $this->getSqs()->getQueueArn($this->getQueueUrl($standardOptions['error_queue']));
		}

		return array('DeliveryPolicy' => $newDeliveryPolicy, 'RedrivePolicy' => $newRedrivePolicy);
	}

	/**
	 * Get the standard queue options.
	 *
	 * @param string $queue
	 * @param string $endpoint
	 * @param array  $options
	 * @return array
	 */
	protected function getStandardOptions($queue, $endpoint, $options)
	{
		return array(
			'retries' => $this->getOption($queue, $endpoint, 'retries', (isset($options['retries']) ? $options['retries'] : false)), 
			'error_queue' => $this->getOption($queue, $endpoint, 'error_queue', (isset($options['errqueue']) ? $options['errqueue'] : false))
		);
	}

	/**
	 * Maps standard keys to dot paths for the meta array
	 *
	 * @param  string $key
	 * @return string
	 */
	protected function getDotPath($key) 
	{
		$mapping = array('retries' => 'DeliveryPolicy.numRetries',
				 'error_queue' => 'QueueAttributes.RedrivePolicy.deadLetterTargetArn');

		return $mapping[$key];
	}

	/**
	 * Get a queue option
	 *
	 * @param string $queue
	 * @param string $endpoint
	 * @param string $key
	 * @param string $value
	 * @return array
	 *
	 * @throws \RuntimeException
	 */
	protected function getOption($queue, $endpoint, $key, $value = null)
	{
		if ($value) return $value;

		try
		{
			return array_get($this->getQueueMeta($queue, $endpoint), $this->getDotPath($key));
		}
		catch (\Exception $e)
		{
			switch($key) {
				case 'retries':
					return 3;
				case 'error_queue':
					return '';
				default :
					throw new RuntimeException("The option '".$key."' is not a valid setting for an SQS queue");
			}
		}
	}

	/**
	 * Get the queue meta data from SQS then store it and return it
	 *
	 * @param string $queue
	 * @param string $endpoint
	 * @return array
	 */
	protected function getQueueMeta($queue, $endpoint)
	{
		if (isset($this->meta)) return $this->meta;

		$subscriptionArn = $this->getCurrentSubscriptionArn($queue, $endpoint);	

		$queryParameters = array('QueueUrl' => $this->getQueueUrl($queue), 'Attributes' => 'All');

		return $this->meta = array('QueueAttributes' => $this->getSqs()->getQueueAttributes($queryParameters),
					   'DeliveryPolicy' => $this->getCurrentDeliveryPolicy($subscriptionArn),
					   'SubscriptionArn' => $subscriptionArn);
	}

	/**
	 * Get the SubscriberArn for the current subscription
	 *
	 * @param string $queue
	 * @param string $endpoint
	 * @return array
	 */
	protected function getCurrentSubscriptionArn($queue, $endpoint)
	{
		$response = $this->getSns()->listSubscriptions();

		$subscription = array_values(array_filter($response->toArray()['Subscriptions'], function($element) use ($endpoint) {

			return $element['Endpoint'] == $endpoint;
		}));

		if ( ! count($subscription)) throw new RuntimeException("Can't find any subscriptions for the '".$queue."' topic.");

		return $subscription[0]['SubscriptionArn'];	
	}

	/**
	 * Get the SubscriberArn for the current subscription
	 *
	 * @param string $arn
	 * @return array
	 */
	protected function getCurrentDeliveryPolicy($arn)
	{
		$response = $this->getSns()->getSubscriptionAttributes(array('SubscriptionArn' => $arn));

		$existingDeliveryPolicy = json_decode(stripslashes($response->toArray()['Attributes']['EffectiveDeliveryPolicy']), true);

		return $existingDeliveryPolicy;
	}

	/**
	 * Subscribe a queue to the endpoint url
	 *
	 * @param string  $queue
	 * @param string  $endpoint
	 * @param array   $options
	 * @param array   $advanced
	 * @return array
	 */
	public function subscribe($queue, $endpoint, array $options = array(), array $advanced = array())	
	{
		$topicArn = $this->getSns()->createTopic(array('Name' => $queue))->get('TopicArn');

		$response = $this->getSns()->subscribe(array('TopicArn' => $topicArn, 'Protocol' => ((stripos($endpoint, 'https') !== false) ? 'https' : 'http'), 'Endpoint' => $endpoint));

		return $response->toArray();
	}

	/**
	 * Unsubscribe a queue from an endpoint url
	 *
	 * @param string  $queue
	 * @param string  $endpoint
	 * @return array
	 */
	public function unsubscribe($queue, $endpoint)
	{
		$response = $this->getSns()->listSubscriptions();

		$subscription = array_values(array_filter($response->toArray()['Subscriptions'], function($element) use ($endpoint) {

			return $element['Endpoint'] == $endpoint;
		}));

		if (count($subscription))
		{
			$response = $this->getSns()->unsubscribe(array('SubscriptionArn' => $subscription[0]['SubscriptionArn']));
		}

		return $response->toArray();
	}

	/**
	 * Update queue settings
	 *
	 * @param string  $queue
	 * @param string  $endpoint
	 * @param array   $options
	 * @param array   $advanced
	 * @return array
	 */
	public function update($queue, $endpoint, array $options = array(), array $advanced = array())
	{
		$queueOptions = $this->getQueueOptions($queue, $endpoint, $options, $advanced);

		$newDeliveryPolicy = $queueOptions['DeliveryPolicy'];

		$subscriptionArn = array_get($this->getQueueMeta($queue, $endpoint), 'SubscriptionArn');

		$response = $this->getSns()->setSubscriptionAttributes(array('SubscriptionArn' => $subscriptionArn, 
									     'AttributeName' => 'DeliveryPolicy',
									     'AttributeValue' => json_encode($newDeliveryPolicy)));

		$newRedrivePolicy = $queueOptions['RedrivePolicy'];

		$response = $this->getSqs()->setQueueAttributes(array('QueueUrl' => $this->getQueueUrl($queue),
								      'Attributes' => array('RedrivePolicy' => json_encode($newRedrivePolicy))));

		return $response->toArray();
	}

}
