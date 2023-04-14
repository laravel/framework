<?php

namespace Illuminate\Tests\Queue;

use Aws\Result;
use Aws\Sqs\SqsClient;
use BadMethodCallException;
use Illuminate\Container\Container;
use Illuminate\Queue\Jobs\SqsJob;
use Illuminate\Queue\SqsFifoQueue;
use Illuminate\Support\Carbon;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class QueueSqsFifoQueueTest extends TestCase
{
    protected $sqs;
    protected $account;
    protected $queueName;
    protected $baseUrl;
    protected $prefix;

    protected $suffix;
    protected $queueUrl;
    protected $allowDelay;
    protected $mockedJob;
    protected $mockedData;
    protected $mockedPayload;
    protected $mockedDelay;
    protected $mockedMessageId;
    protected $mockedMessageDeduplicationId;
    protected $mockedMessageGroupId;
    protected $mockedReceiptHandle;
    protected $mockedSendMessageResponseModel;
    protected $mockedReceiveMessageResponseModel;
    protected $mockedReceiveEmptyMessageResponseModel;
    protected $mockedQueueAttributesResponseModel;
    protected const FIFO_POSTFIX = '.fifo';

    protected function tearDown(): void
    {
        m::close();
    }

    protected function setUp(): void
    {
        // Use Mockery to mock the SqsClient
        $this->sqs = m::mock(SqsClient::class);

        $this->account = '1234567891011';
        $this->queueName = 'emails';
        $this->baseUrl = 'https://sqs.someregion.amazonaws.com';

        // This is how the modified getQueue builds the queueUrl
        $this->prefix = $this->baseUrl . '/' . $this->account . '/';
        $this->suffix = false;
        $this->queueUrl = $this->prefix . $this->queueName;
        $this->allowDelay = true;
        $this->mockedJob = 'foo';
        $this->mockedData = ['data'];
        $this->mockedPayload = json_encode(['job' => $this->mockedJob, 'data' => $this->mockedData]);
        $this->mockedDelay = 10;
        $this->mockedMessageId = 'e3cd03ee-59a3-4ad8-b0aa-ee2e3808ac81';
        $this->mockedMessageDeduplicationId = '710cd3f2-e3a5-b94e-f614-da0a2cc53c7d';
        $this->mockedMessageGroupId = 'high';
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
        $queue = $this->getMockBuilder(SqsFifoQueue::class)->onlyMethods(['getQueue'])->setConstructorArgs([$this->sqs, $this->queueName, $this->account])->getMock();
        $queue->setContainer(m::mock(Container::class));
        $queue->expects($this->once())->method('getQueue')->with($this->queueName)->willReturn($this->queueUrl);
        $this->sqs->shouldReceive('receiveMessage')->once()->with(['QueueUrl' => $this->queueUrl, 'AttributeNames' => ['ApproximateReceiveCount']])->andReturn($this->mockedReceiveMessageResponseModel);
        $result = $queue->pop($this->queueName);
        $this->assertInstanceOf(SqsJob::class, $result);
    }

    public function testPopProperlyHandlesEmptyMessage()
    {
        $queue = $this->getMockBuilder(SqsFifoQueue::class)->onlyMethods(['getQueue'])->setConstructorArgs([$this->sqs, $this->queueName, $this->account])->getMock();
        $queue->setContainer(m::mock(Container::class));
        $queue->expects($this->once())->method('getQueue')->with($this->queueName)->willReturn($this->queueUrl);
        $this->sqs->shouldReceive('receiveMessage')->once()->with(['QueueUrl' => $this->queueUrl, 'AttributeNames' => ['ApproximateReceiveCount']])->andReturn($this->mockedReceiveEmptyMessageResponseModel);
        $result = $queue->pop($this->queueName);
        $this->assertNull($result);
    }

    public function testDelayedPushFifoWithAllowDelayProperlyPushesJobOntoSqs()
    {
        $queue = $this->getMockBuilder(SqsFifoQueue::class)->onlyMethods(['createPayload', 'secondsUntil', 'getQueue'])->setConstructorArgs([$this->sqs, $this->queueName, $this->account, null, $this->suffix, $this->mockedMessageGroupId, $this->mockedMessageDeduplicationId, $this->allowDelay])->getMock();
        $queue->setContainer($container = m::spy(Container::class));
        $queue->expects($this->once())->method('createPayload')->with($this->mockedJob, $this->queueName, $this->mockedData)->willReturn($this->mockedPayload);
        $queue->expects($this->once())->method('getQueue')->with($this->queueName)->willReturn($this->queueUrl);
        $this->sqs->shouldReceive('sendMessage')->once()->with(['QueueUrl' => $this->queueUrl, 'MessageBody' => $this->mockedPayload, 'MessageGroupId' => $this->mockedMessageGroupId, 'MessageDeduplicationId' => $this->mockedMessageDeduplicationId])->andReturn($this->mockedSendMessageResponseModel);
        $id = $queue->later($this->mockedDelay, $this->mockedJob, $this->mockedData, $this->queueName);
        $this->assertEquals($this->mockedMessageId, $id);
        $container->shouldHaveReceived('bound')->with('events')->once();
    }

    public function testPushFifoProperlyPushesJobOntoSqs()
    {
        $queue = $this->getMockBuilder(SqsFifoQueue::class)->onlyMethods(['createPayload', 'getQueue'])->setConstructorArgs([$this->sqs, $this->queueName, $this->account, null, $this->suffix, $this->mockedMessageGroupId, $this->mockedMessageDeduplicationId, $this->allowDelay])->getMock();
        $queue->setContainer($container = m::spy(Container::class));
        $queue->expects($this->once())->method('createPayload')->with($this->mockedJob, $this->queueName, $this->mockedData)->willReturn($this->mockedPayload);
        $queue->expects($this->once())->method('getQueue')->with($this->queueName)->willReturn($this->queueUrl);
        $this->sqs->shouldReceive('sendMessage')->once()->with(['QueueUrl' => $this->queueUrl, 'MessageBody' => $this->mockedPayload, 'MessageGroupId' => $this->mockedMessageGroupId, 'MessageDeduplicationId' => $this->mockedMessageDeduplicationId])->andReturn($this->mockedSendMessageResponseModel);
        $id = $queue->push($this->mockedJob, $this->mockedData, $this->queueName);
        $this->assertEquals($this->mockedMessageId, $id);
        $container->shouldHaveReceived('bound')->with('events')->once();
    }

    public function testSizeProperlyReadsSqsFifoQueueSize()
    {
        $queue = $this->getMockBuilder(SqsFifoQueue::class)->onlyMethods(['getQueue'])->setConstructorArgs([$this->sqs, $this->queueName, $this->account])->getMock();
        $queue->expects($this->once())->method('getQueue')->with($this->queueName)->willReturn($this->queueUrl);
        $this->sqs->shouldReceive('getQueueAttributes')->once()->with(['QueueUrl' => $this->queueUrl, 'AttributeNames' => ['ApproximateNumberOfMessages']])->andReturn($this->mockedQueueAttributesResponseModel);
        $size = $queue->size($this->queueName);
        $this->assertEquals(1, $size);
    }

    public function testGetQueueProperlyResolvesFifoUrlWithPrefix()
    {
        $this->queueName = 'emails';
        $this->queueUrl = $this->prefix . $this->queueName;
        $queue = new SqsFifoQueue($this->sqs, $this->queueName, $this->prefix, null, false, 'fifo', $this->mockedMessageGroupId, $this->mockedMessageDeduplicationId);
        $this->assertEquals($this->queueUrl . $this::FIFO_POSTFIX, $queue->getQueue(null));
        $queueUrl = $this->baseUrl . '/' . $this->account . '/test';
        $this->assertEquals($queueUrl . $this::FIFO_POSTFIX, $queue->getQueue('test'));
    }

    public function testGetQueueProperlyResolvesFifoUrlWithoutPrefix()
    {
        $this->queueName = 'emails';
        $this->queueUrl = $this->prefix . $this->queueName;
        $queue = new SqsFifoQueue($this->sqs, $this->queueUrl, null, null, false, 'fifo', $this->mockedMessageGroupId, $this->mockedMessageDeduplicationId);
        $this->assertEquals($this->queueUrl . $this::FIFO_POSTFIX, $queue->getQueue(null));
        $fifoQueueUrl = $this->baseUrl . '/' . $this->account . '/test';
        $this->assertEquals($fifoQueueUrl . $this::FIFO_POSTFIX, $queue->getQueue($fifoQueueUrl));
    }

    public function testGetQueueProperlyResolvesFifoUrlWithSuffix()
    {
        $this->queueName = 'emails';
        $queue = new SqsFifoQueue($this->sqs, $this->queueName, $this->prefix, $suffix = '-staging', false, 'fifo', $this->mockedMessageGroupId, $this->mockedMessageDeduplicationId);
        $this->assertEquals("{$this->prefix}emails-staging" . $this::FIFO_POSTFIX, $queue->getQueue(null));
        $queueUrl = $this->baseUrl . '/' . $this->account . '/test' . $suffix;
        $this->assertEquals($queueUrl . $this::FIFO_POSTFIX, $queue->getQueue('test'));
    }

    public function testGetFifoQueueEnsuresTheQueueIsOnlySuffixedOnce()
    {
        $queue = new SqsFifoQueue($this->sqs, "{$this->queueName}-staging", $this->prefix, $suffix = '-staging', false, 'fifo', $this->mockedMessageGroupId, $this->mockedMessageDeduplicationId);
        $this->assertEquals("{$this->prefix}{$this->queueName}{$suffix}" . $this::FIFO_POSTFIX, $queue->getQueue(null));
        $queueUrl = $this->baseUrl . '/' . $this->account . '/test' . $suffix;
        $this->assertEquals($queueUrl . $this::FIFO_POSTFIX, $queue->getQueue('test-staging'));
    }
}
