<?php

use Mockery as m;

use Illuminate\Http\Request;
use Illuminate\Queue\Console\SubscribeCommand;
use Aws\Sqs\SqsClient;
use Guzzle\Service\Resource\Model;

class QueueSqsQueueTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}

	public function setUp() {

		// Use Mockery to mock the SqsClient
		$this->sqs = m::mock('Aws\Sqs\SqsClient');

		// Use Mockery to mock the SnsClient
		$this->sns = m::mock('Aws\Sns\SnsClient');

		$this->account = '1234567891011';
		$this->queueName = 'emails';
		$this->baseUrl = 'https://sqs.someregion.amazonaws.com';

		// This is how the modified getQueue builds the queueUrl
		$this->queueUrl = $this->baseUrl . '/' . $this->account . '/' . $this->queueName;

		$this->mockedJob = 'foo';
		$this->mockedData = array('data');
		$this->mockedPayload = json_encode(array('job' => $this->mockedJob, 'data' => $this->mockedData));
		$this->mockedDelay = 10;
		$this->mockedDateTime = m::mock('DateTime');
		$this->mockedMessageId = 'e3cd03ee-59a3-4ad8-b0aa-ee2e3808ac81';
		$this->mockedReceiptHandle = '0NNAq8PwvXuWv5gMtS9DJ8qEdyiUwbAjpp45w2m6M4SJ1Y+PxCh7R930NRB8ylSacEmoSnW18bgd4nK\/O6ctE+VFVul4eD23mA07vVoSnPI4F\/voI1eNCp6Iax0ktGmhlNVzBwaZHEr91BRtqTRM3QKd2ASF8u+IQaSwyl\/DGK+P1+dqUOodvOVtExJwdyDLy1glZVgm85Yw9Jf5yZEEErqRwzYz\/qSigdvW4sm2l7e4phRol\/+IjMtovOyH\/ukueYdlVbQ4OshQLENhUKe7RNN5i6bE\/e5x9bnPhfj2gbM';

		$this->mockedSendMessageResponseModel = new Model(array('Body' => $this->mockedPayload,
						      			'MD5OfBody' => md5($this->mockedPayload),
						      			'ReceiptHandle' => $this->mockedReceiptHandle,
						      			'MessageId' => $this->mockedMessageId,
						      			'Attributes' => array('ApproximateReceiveCount' => 1)));

		$this->mockedReceiveMessageResponseModel = new Model(array('Messages' => array( 0 => array(
												'Body' => $this->mockedPayload,
						     						'MD5OfBody' => md5($this->mockedPayload),
						      						'ReceiptHandle' => $this->mockedReceiptHandle,
						     						'MessageId' => $this->mockedMessageId))));
	}

	public function testGetQueueUrlCallsGetBaseUrlAndBuildsQueueUrl()
	{
		$queue = new Illuminate\Queue\SqsQueue($this->sqs, $this->sns, m::mock('Illuminate\Http\Request'), $this->queueName, $this->account);
		$this->sqs->shouldReceive('getBaseUrl')->once()->andReturn($this->baseUrl);
		$resultQueueUrl = $queue->getQueueUrl($this->queueName);
		$this->assertEquals($this->queueUrl, $resultQueueUrl);
		$this->sqs->shouldReceive('getBaseUrl')->once()->andReturn('https://bar');
		$resultQueueUrl = $queue->getQueueUrl($this->queueName);
		$this->assertNotEquals($this->queueUrl, $resultQueueUrl);
	}

	public function testPopProperlyPopsJobOffOfSqs()
	{
		$queue = $this->getMock('Illuminate\Queue\SqsQueue', array('getQueueUrl'), array($this->sqs, $this->sns, m::mock('Illuminate\Http\Request'), $this->account, $this->queueName));
		$queue->setContainer(m::mock('Illuminate\Container\Container'));
		$queue->expects($this->once())->method('getQueueUrl')->with($this->queueName)->will($this->returnValue($this->queueUrl));
		$this->sqs->shouldReceive('receiveMessage')->once()->with(array('QueueUrl' => $this->queueUrl, 'AttributeNames' => array('ApproximateReceiveCount')))->andReturn($this->mockedReceiveMessageResponseModel);
		$result = $queue->pop($this->queueName);
		$this->assertInstanceOf('Illuminate\Queue\Jobs\SqsJob', $result);
	}

	public function testDelayedPushWithDateTimeProperlyPushesJobOntoSqs()
	{
		$queue = $this->getMock('Illuminate\Queue\SqsQueue', array('createPayload', 'getQueueUrl', 'getSeconds'), array($this->sqs, $this->sns, m::mock('Illuminate\Http\Request'), $this->account, $this->queueName));
		$queue->expects($this->once())->method('createPayload')->with($this->mockedJob, $this->mockedData)->will($this->returnValue($this->mockedPayload));
		$queue->expects($this->once())->method('getSeconds')->with($this->mockedDateTime)->will($this->returnValue($this->mockedDateTime));
		$queue->expects($this->once())->method('getQueueUrl')->with($this->queueName)->will($this->returnValue($this->queueUrl));
		$this->sqs->shouldReceive('sendMessage')->once()->with(array('QueueUrl' => $this->queueUrl, 'MessageBody' => $this->mockedPayload, 'DelaySeconds' => $this->mockedDateTime))->andReturn($this->mockedSendMessageResponseModel);
		$id = $queue->later($this->mockedDateTime, $this->mockedJob, $this->mockedData, $this->queueName);
		$this->assertEquals($this->mockedMessageId, $id);
	}

	public function testDelayedPushProperlyPushesJobOntoSqs()
	{
		$queue = $this->getMock('Illuminate\Queue\SqsQueue', array('createPayload', 'getQueueUrl', 'getSeconds'), array($this->sqs, $this->sns, m::mock('Illuminate\Http\Request'), $this->account, $this->queueName));
		$queue->expects($this->once())->method('createPayload')->with($this->mockedJob, $this->mockedData)->will($this->returnValue($this->mockedPayload));
		$queue->expects($this->once())->method('getSeconds')->with($this->mockedDelay)->will($this->returnValue($this->mockedDelay));
		$queue->expects($this->once())->method('getQueueUrl')->with($this->queueName)->will($this->returnValue($this->queueUrl));
		$this->sqs->shouldReceive('sendMessage')->once()->with(array('QueueUrl' => $this->queueUrl, 'MessageBody' => $this->mockedPayload, 'DelaySeconds' => $this->mockedDelay))->andReturn($this->mockedSendMessageResponseModel);
		$id = $queue->later($this->mockedDelay, $this->mockedJob, $this->mockedData, $this->queueName);
		$this->assertEquals($this->mockedMessageId, $id);
	}

	public function testPushProperlyPushesJobOntoSqs()
	{
		$queue = $this->getMock('Illuminate\Queue\SqsQueue', array('createPayload', 'getQueueUrl'), array($this->sqs, $this->sns, m::mock('Illuminate\Http\Request'), $this->account, $this->queueName));
		$queue->expects($this->once())->method('createPayload')->with($this->mockedJob, $this->mockedData)->will($this->returnValue($this->mockedPayload));
		$queue->expects($this->once())->method('getQueueUrl')->with($this->queueName)->will($this->returnValue($this->queueUrl));
		$this->sqs->shouldReceive('sendMessage')->once()->with(array('QueueUrl' => $this->queueUrl, 'MessageBody' => $this->mockedPayload))->andReturn($this->mockedSendMessageResponseModel);
		$id = $queue->push($this->mockedJob, $this->mockedData, $this->queueName);
		$this->assertEquals($this->mockedMessageId, $id);
	}
	
	public function testPushedJobsCanBeMarshaled()
	{
		$queue = $this->getMock('Illuminate\Queue\SqsQueue', array('createSqsJob'), array($this->sqs, $this->sns, $request = m::mock('Illuminate\Http\Request'), $this->queueName, $this->account));
		$request->shouldReceive('header')->once()->with('x-amz-sns-message-type')->andReturn('Notification');
		$request->shouldReceive('header')->once()->with('x-amz-sns-message-id')->andReturn('message-id');
		$request->shouldReceive('header')->once()->with('x-aws-sqsd-msgid')->andReturn('message-id');
		$request->shouldReceive('json')->once()->andReturn($content = json_encode(array('foo' => 'bar')));
		$pushedJob = array(
			'MessageId' => 'message-id',
			'Body' => json_encode(array('foo' => 'bar'))
		);
		$queue->expects($this->once())->method('createSqsJob')->with($this->equalTo($pushedJob))->will($this->returnValue($mockSqsJob = m::mock('StdClass')));
		$mockSqsJob->shouldReceive('fire')->once();
		$response = $queue->marshal();
		$this->assertInstanceOf('Illuminate\Http\Response', $response);
		$this->assertEquals(200, $response->getStatusCode());
	}

	/**
	 * @expectedException RuntimeException
	 */
	public function testPushedJobsMustComeFromSqsOrSns()
	{
		$queue = $this->getMock('Illuminate\Queue\SqsQueue', array('createSqsJob'), array($this->sqs, $this->sns, $request = m::mock('Illuminate\Http\Request'), $this->queueName, $this->account));
		$request->shouldReceive('header')->once()->with('x-amz-sns-message-type')->andReturn(null);
		$request->shouldReceive('header')->once()->with('x-amz-sns-message-id')->andReturn(null);
		$request->shouldReceive('header')->once()->with('x-aws-sqsd-msgid')->andReturn(null);
		$response = $queue->marshal();
	}

	public function testSubscriptionConfirmationNoJob()
	{
		$queue = $this->getMock('Illuminate\Queue\SqsQueue', array('createSqsJob'), array($this->sqs, $this->sns, $request = m::mock('Illuminate\Http\Request'), $this->queueName, $this->account));
		$request->shouldReceive('header')->once()->with('x-amz-sns-message-type')->andReturn('SubscriptionConfirmation');
		$request->shouldReceive('json')->once()->with('TopicArn')->andReturn('foo');
		$request->shouldReceive('json')->once()->with('Token')->andReturn('bar');
		$this->sns->shouldReceive('confirmSubscription')->once()->with(array('TopicArn' => 'foo', 'Token' => 'bar', 'AuthenticateOnUnsubscribe' => 'true'))->andReturn();
		$response = $queue->marshal();
		$this->assertInstanceOf('Illuminate\Http\Response', $response);
		$this->assertEquals(200, $response->getStatusCode());
	}

	public function testSubscribeToSqsQueue()
	{
		$queue = $this->getMock('Illuminate\Queue\SqsQueue', array('createSqsJob'), array($this->sqs, $this->sns, $request = m::mock('Illuminate\Http\Request'), $this->queueName, $this->account));
		$request->shouldReceive('header')->once()->with('x-amz-sns-message-type')->andReturn('SubscriptionConfirmation');
		$request->shouldReceive('json')->once()->with('TopicArn')->andReturn('foo');
		$request->shouldReceive('json')->once()->with('Token')->andReturn('bar');
		$this->sns->shouldReceive('confirmSubscription')->once()->with(array('TopicArn' => 'foo', 'Token' => 'bar', 'AuthenticateOnUnsubscribe' => 'true'))->andReturn();
		$response = $queue->marshal();
		$this->assertInstanceOf('Illuminate\Http\Response', $response);
		$this->assertEquals(200, $response->getStatusCode());
	}

}
