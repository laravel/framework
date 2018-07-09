<?php

namespace Illuminate\Tests\Queue;

use Aws\Result;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class QueueSqsQueueTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function setUp()
    {
        // Use Mockery to mock the SqsClient
        $this->sqs = m::mock('Aws\Sqs\SqsClient');

        $this->account = '1234567891011';
        $this->queueName = 'emails';
        $this->baseUrl = 'https://sqs.someregion.amazonaws.com';

        // This is how the modified getQueue builds the queueUrl
        $this->prefix = $this->baseUrl.'/'.$this->account.'/';
        $this->queueUrl = $this->prefix.$this->queueName;

        $this->mockedJob = 'foo';
        $this->mockedData = ['data'];
        $this->mockedPayload = json_encode(['job' => $this->mockedJob, 'data' => $this->mockedData]);
        $this->mockedDelay = 10;
        $this->mockedMessageId = 'e3cd03ee-59a3-4ad8-b0aa-ee2e3808ac81';
        $this->mockedReceiptHandle = '0NNAq8PwvXuWv5gMtS9DJ8qEdyiUwbAjpp45w2m6M4SJ1Y+PxCh7R930NRB8ylSacEmoSnW18bgd4nK\/O6ctE+VFVul4eD23mA07vVoSnPI4F\/voI1eNCp6Iax0ktGmhlNVzBwaZHEr91BRtqTRM3QKd2ASF8u+IQaSwyl\/DGK+P1+dqUOodvOVtExJwdyDLy1glZVgm85Yw9Jf5yZEEErqRwzYz\/qSigdvW4sm2l7e4phRol\/+IjMtovOyH\/ukueYdlVbQ4OshQLENhUKe7RNN5i6bE\/e5x9bnPhfj2gbM';

        $this->mockedSendMessageResponseModel = new Result([
            'Body' => $this->mockedPayload,
            'MD5OfBody' => md5($this->mockedPayload),
            'ReceiptHandle' => $this->mockedReceiptHandle,
            'MessageId' => $this->mockedMessageId,
            'Attributes' => ['ApproximateReceiveCount' => 1],
        ]);

        $this->mockedReceiveMessageResponseModel = new Result([
            'Messages' => [
                0 => [
                    'Body' => $this->mockedPayload,
                    'MD5OfBody' => md5($this->mockedPayload),
                    'ReceiptHandle' => $this->mockedReceiptHandle,
                    'MessageId' => $this->mockedMessageId,
                ],
            ],
        ]);

        $this->mockedReceiveEmptyMessageResponseModel = new Result([
            'Messages' => null,
        ]);

        $this->mockedQueueAttributesResponseModel = new Result([
            'Attributes' => [
                'ApproximateNumberOfMessages' => 1,
            ],
        ]);
    }

    public function testPopProperlyPopsJobOffOfSqs()
    {
        $queue = $this->getMockBuilder('Illuminate\Queue\SqsQueue')->setMethods(['getQueue'])->setConstructorArgs([$this->sqs, $this->queueName, $this->account])->getMock();
        $queue->setContainer(m::mock('Illuminate\Container\Container'));
        $queue->expects($this->once())->method('getQueue')->with($this->queueName)->will($this->returnValue($this->queueUrl));
        $this->sqs->shouldReceive('receiveMessage')->once()->with(['QueueUrl' => $this->queueUrl, 'AttributeNames' => ['ApproximateReceiveCount']])->andReturn($this->mockedReceiveMessageResponseModel);
        $result = $queue->pop($this->queueName);
        $this->assertInstanceOf('Illuminate\Queue\Jobs\SqsJob', $result);
    }

    public function testPopProperlyHandlesEmptyMessage()
    {
        $queue = $this->getMockBuilder('Illuminate\Queue\SqsQueue')->setMethods(['getQueue'])->setConstructorArgs([$this->sqs, $this->queueName, $this->account])->getMock();
        $queue->setContainer(m::mock('Illuminate\Container\Container'));
        $queue->expects($this->once())->method('getQueue')->with($this->queueName)->will($this->returnValue($this->queueUrl));
        $this->sqs->shouldReceive('receiveMessage')->once()->with(['QueueUrl' => $this->queueUrl, 'AttributeNames' => ['ApproximateReceiveCount']])->andReturn($this->mockedReceiveEmptyMessageResponseModel);
        $result = $queue->pop($this->queueName);
        $this->assertNull($result);
    }

    public function testDelayedPushWithDateTimeProperlyPushesJobOntoSqs()
    {
        $now = \Illuminate\Support\Carbon::now();
        $queue = $this->getMockBuilder('Illuminate\Queue\SqsQueue')->setMethods(['createPayload', 'secondsUntil', 'getQueue'])->setConstructorArgs([$this->sqs, $this->queueName, $this->account])->getMock();
        $queue->expects($this->once())->method('createPayload')->with($this->mockedJob, $this->mockedData)->will($this->returnValue($this->mockedPayload));
        $queue->expects($this->once())->method('secondsUntil')->with($now)->will($this->returnValue(5));
        $queue->expects($this->once())->method('getQueue')->with($this->queueName)->will($this->returnValue($this->queueUrl));
        $this->sqs->shouldReceive('sendMessage')->once()->with(['QueueUrl' => $this->queueUrl, 'MessageBody' => $this->mockedPayload, 'DelaySeconds' => 5])->andReturn($this->mockedSendMessageResponseModel);
        $id = $queue->later($now->addSeconds(5), $this->mockedJob, $this->mockedData, $this->queueName);
        $this->assertEquals($this->mockedMessageId, $id);
    }

    public function testDelayedPushProperlyPushesJobOntoSqs()
    {
        $queue = $this->getMockBuilder('Illuminate\Queue\SqsQueue')->setMethods(['createPayload', 'secondsUntil', 'getQueue'])->setConstructorArgs([$this->sqs, $this->queueName, $this->account])->getMock();
        $queue->expects($this->once())->method('createPayload')->with($this->mockedJob, $this->mockedData)->will($this->returnValue($this->mockedPayload));
        $queue->expects($this->once())->method('secondsUntil')->with($this->mockedDelay)->will($this->returnValue($this->mockedDelay));
        $queue->expects($this->once())->method('getQueue')->with($this->queueName)->will($this->returnValue($this->queueUrl));
        $this->sqs->shouldReceive('sendMessage')->once()->with(['QueueUrl' => $this->queueUrl, 'MessageBody' => $this->mockedPayload, 'DelaySeconds' => $this->mockedDelay])->andReturn($this->mockedSendMessageResponseModel);
        $id = $queue->later($this->mockedDelay, $this->mockedJob, $this->mockedData, $this->queueName);
        $this->assertEquals($this->mockedMessageId, $id);
    }

    public function testPushProperlyPushesJobOntoSqs()
    {
        $queue = $this->getMockBuilder('Illuminate\Queue\SqsQueue')->setMethods(['createPayload', 'getQueue'])->setConstructorArgs([$this->sqs, $this->queueName, $this->account])->getMock();
        $queue->expects($this->once())->method('createPayload')->with($this->mockedJob, $this->mockedData)->will($this->returnValue($this->mockedPayload));
        $queue->expects($this->once())->method('getQueue')->with($this->queueName)->will($this->returnValue($this->queueUrl));
        $this->sqs->shouldReceive('sendMessage')->once()->with(['QueueUrl' => $this->queueUrl, 'MessageBody' => $this->mockedPayload])->andReturn($this->mockedSendMessageResponseModel);
        $id = $queue->push($this->mockedJob, $this->mockedData, $this->queueName);
        $this->assertEquals($this->mockedMessageId, $id);
    }

    public function testSizeProperlyReadsSqsQueueSize()
    {
        $queue = $this->getMockBuilder('Illuminate\Queue\SqsQueue')->setMethods(['getQueue'])->setConstructorArgs([$this->sqs, $this->queueName, $this->account])->getMock();
        $queue->expects($this->once())->method('getQueue')->with($this->queueName)->will($this->returnValue($this->queueUrl));
        $this->sqs->shouldReceive('getQueueAttributes')->once()->with(['QueueUrl' => $this->queueUrl, 'AttributeNames' => ['ApproximateNumberOfMessages']])->andReturn($this->mockedQueueAttributesResponseModel);
        $size = $queue->size($this->queueName);
        $this->assertEquals($size, 1);
    }

    public function testGetQueueProperlyResolvesUrlWithPrefix()
    {
        $queue = new \Illuminate\Queue\SqsQueue($this->sqs, $this->queueName, $this->prefix);
        $this->assertEquals($this->queueUrl, $queue->getQueue(null));
        $queueUrl = $this->baseUrl.'/'.$this->account.'/test';
        $this->assertEquals($queueUrl, $queue->getQueue('test'));
    }

    public function testGetQueueProperlyResolvesUrlWithoutPrefix()
    {
        $queue = new \Illuminate\Queue\SqsQueue($this->sqs, $this->queueUrl);
        $this->assertEquals($this->queueUrl, $queue->getQueue(null));
        $queueUrl = $this->baseUrl.'/'.$this->account.'/test';
        $this->assertEquals($queueUrl, $queue->getQueue($queueUrl));
    }
}
