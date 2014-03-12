<?php

use Mockery as m;
use Guzzle\Service\Resource\Model;

class QueueSqsJobTest extends PHPUnit_Framework_TestCase {

	public function setUp() {

		$this->service = 'sqs';
		$this->region = 'mars';
		$this->account = 'infinity';
		$this->baseUrl = 'http://nowhere';

		$this->queueName = 'emails';
		$this->pushQueueName = 'notifications';
		$this->queueUrl = $this->baseUrl . '/' . $this->account . '/' . $this->queueName;
		$this->pushQueueUrl = $this->baseUrl . '/' . $this->account . '/' . $this->pushQueueName;

		$this->job = 'foo';
		$this->data = array('data');
		$this->payload = json_encode(array('job' => $this->job, 'data' => $this->data, 'attempts' => 1));
		$this->messageId = 'foo';
		$this->receiptHandle = 'bar';

		$this->topicArn = 'arn:aws:sns:'.$this->region.':'.$this->account.':'.$this->pushQueueName;

		$this->jobData = array('Body' => $this->payload,
				       'MD5OfBody' => md5($this->payload),
				       'ReceiptHandle' => $this->receiptHandle,
				       'MessageId' => $this->messageId,
				       'Attributes' => array('ApproximateReceiveCount' => 1));

		$this->alternateAttributes = array('Attributes' => array('ApproximateReceiveCount' => 5));

		$this->receiveMessageResponseModel = new Model(array('Messages' => array( 0 => array('Body' => $this->payload,
												     'MD5OfBody' => md5($this->payload),
												     'ReceiptHandle' => $this->receiptHandle,
												     'MessageId' => $this->messageId))));

		$this->mockedContainer = m::mock('Illuminate\Container\Container');
		$this->mockedSqsQueue = m::mock('Illuminate\Queue\SqsQueue');
		$this->mockedQueueManager = m::mock('Illuminate\Queue\QueueManager');
		$this->mockedDatabaseFailedJobProvider = m::mock('Illuminate\Queue\Failed\DatabaseFailedJobProvider');
	}

	public function tearDown()
	{
		m::close();
	}

	public function testFireProperlyCallsTheJobHandler()
	{
		$this->mockedSqsQueue->shouldReceive('getQueue')->andReturn($this->queueName);
		$job = $this->getJob($this->jobData);
		$job->getContainer()->shouldReceive('make')->once()->with('foo')->andReturn($handler = m::mock('StdClass'));
		$handler->shouldReceive('fire')->once()->with($job, array('data'));
		$job->fire();
	}

	public function testDeleteRemovesJobFromSqs()
	{
		$this->mockedSqsQueue->shouldReceive('getQueue')->andReturn($this->queueName);
		$job = $this->getJob($this->jobData);
		$job->getSqsQueue()->shouldReceive('getSqs')->twice()->andReturn($sqs = m::mock('Aws\Sqs\SqsClient'));
		$job->getSqsQueue()->shouldReceive('getQueueUrl')->once()->andReturn($this->queueUrl);
		$job->getSqsQueue()->getSqs()->shouldReceive('deleteMessage')->once()->with(array('QueueUrl' => $this->queueUrl, 'ReceiptHandle' => $this->receiptHandle));
		$job->delete();
	}

	public function testDeleteRemovesPushedJobFromSqs()
	{
		$this->mockedSqsQueue->shouldReceive('getQueue')->andReturn($this->queueName);
		$job = $this->getJob($this->jobData, $pushed = true);
		$job->getSqsQueue()->shouldReceive('getSqs')->times(4)->andReturn($sqs = m::mock('Aws\Sqs\SqsClient'));
		$job->getSqsQueue()->shouldReceive('getRequest')->once()->andReturn($request = m::mock('Illuminate\Http\Request'));
		$request->shouldReceive('header')->with('x-amz-sns-topic-arn')->andReturn($this->topicArn);
		$job->getSqsQueue()->shouldReceive('getQueueUrl')->times(2)->andReturn($this->queueUrl);
		$job->getSqsQueue()->getSqs()->shouldReceive('receiveMessage')->once()->with(array('QueueUrl' => $this->queueUrl))->andReturn($this->receiveMessageResponseModel);
		$job->getSqsQueue()->getSqs()->shouldReceive('deleteMessage')->once()->with(array('QueueUrl' => $this->queueUrl, 'ReceiptHandle' => $this->receiptHandle));
		$job->delete();
	}

	public function testFailedJobWouldGetLoggedAfterMaxTriesHasBeenExceeded()
	{
		$worker = $this->getWorker();
		$worker->getManager()->shouldReceive('connection')->once()->with('sqs')->andReturn($queue = $this->mockedSqsQueue);
		$worker->getManager()->shouldReceive('getName')->andReturn('sqs');
		$this->mockedDatabaseFailedJobProvider->shouldReceive('log')->with('sqs', $this->queueName, $this->payload);
		$this->mockedSqsQueue->shouldReceive('getQueue')->once()->andReturn($this->queueName);
		$job = $this->getJob(array_merge($this->jobData, $this->alternateAttributes));
		$this->mockedSqsQueue->shouldReceive('getQueueUrl')->once()->andReturn($this->queueUrl);
		$this->mockedSqsQueue->shouldReceive('getSqs')->once()->andReturn($sqs = m::mock('Aws\Sqs\SqsClient'));
		$sqs->shouldReceive('deleteMessage')->once()->with(array('QueueUrl' => $this->queueUrl, 'ReceiptHandle' => $this->receiptHandle));
		$worker->getManager()->connection('sqs');
		$worker->process('sqs', $job, 3, 3);
	}

	public function testAttemptsReturnsCurrentCountOfAttempts()
	{
		$this->mockedSqsQueue->shouldReceive('getQueue')->andReturn($this->queueName);
		$job = $this->getJob($this->jobData);
		$one = $job->attempts();
		$this->assertEquals($one, 1);
	}

	protected function getJob($jobData, $pushed = false)
	{
		return new Illuminate\Queue\Jobs\SqsJob(
			$this->mockedContainer,
			$this->mockedSqsQueue,
			$jobData,
			$pushed
		);
	}

	protected function getWorker()
	{
		return new Illuminate\Queue\Worker(
			$this->mockedQueueManager,
			$this->mockedDatabaseFailedJobProvider
		);
	}

}
