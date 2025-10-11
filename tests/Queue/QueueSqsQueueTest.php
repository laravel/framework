<?php

namespace Illuminate\Tests\Queue;

use Aws\Result;
use Aws\Sqs\SqsClient;
use Illuminate\Bus\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\Contracts\Bus\Dispatcher as DispatcherContract;
use Illuminate\Queue\Jobs\SqsJob;
use Illuminate\Queue\SqsQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Tests\Queue\Fixtures\FakeSqsJob;
use Illuminate\Tests\Queue\Fixtures\FakeSqsJobWithDeduplication;
use Laravel\SerializableClosure\SerializableClosure;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class QueueSqsQueueTest extends TestCase
{
    protected $sqs;
    protected $account;
    protected $queueName;
    protected $fifoQueueName;
    protected $baseUrl;
    protected $prefix;
    protected $queueUrl;
    protected $fifoQueueUrl;
    protected $mockedJob;
    protected $mockedData;
    protected $mockedPayload;
    protected $mockedDelay;
    protected $mockedMessageGroupId;
    protected $mockedDeduplicationId;
    protected $mockedMessageId;
    protected $mockedReceiptHandle;
    protected $mockedSendMessageResponseModel;
    protected $mockedReceiveMessageResponseModel;
    protected $mockedReceiveEmptyMessageResponseModel;
    protected $mockedQueueAttributesResponseModel;

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
        $this->fifoQueueName = 'emails.fifo';
        $this->baseUrl = 'https://sqs.someregion.amazonaws.com';

        // This is how the modified getQueue builds the queueUrl
        $this->prefix = $this->baseUrl.'/'.$this->account.'/';
        $this->queueUrl = $this->prefix.$this->queueName;
        $this->fifoQueueUrl = $this->prefix.$this->fifoQueueName;

        $this->mockedJob = 'foo';
        $this->mockedData = ['data'];
        $this->mockedPayload = json_encode(['job' => $this->mockedJob, 'data' => $this->mockedData]);
        $this->mockedDelay = 10;
        $this->mockedMessageGroupId = 'group-1';
        $this->mockedDeduplicationId = 'deduplication-id-1';
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
        $queue = $this->getMockBuilder(SqsQueue::class)->onlyMethods(['getQueue'])->setConstructorArgs([$this->sqs, $this->queueName, $this->account])->getMock();
        $queue->setContainer(m::mock(Container::class));
        $queue->expects($this->once())->method('getQueue')->with($this->queueName)->willReturn($this->queueUrl);
        $this->sqs->shouldReceive('receiveMessage')->once()->with(['QueueUrl' => $this->queueUrl, 'AttributeNames' => ['ApproximateReceiveCount']])->andReturn($this->mockedReceiveMessageResponseModel);
        $result = $queue->pop($this->queueName);
        $this->assertInstanceOf(SqsJob::class, $result);
    }

    public function testPopProperlyHandlesEmptyMessage()
    {
        $queue = $this->getMockBuilder(SqsQueue::class)->onlyMethods(['getQueue'])->setConstructorArgs([$this->sqs, $this->queueName, $this->account])->getMock();
        $queue->setContainer(m::mock(Container::class));
        $queue->expects($this->once())->method('getQueue')->with($this->queueName)->willReturn($this->queueUrl);
        $this->sqs->shouldReceive('receiveMessage')->once()->with(['QueueUrl' => $this->queueUrl, 'AttributeNames' => ['ApproximateReceiveCount']])->andReturn($this->mockedReceiveEmptyMessageResponseModel);
        $result = $queue->pop($this->queueName);
        $this->assertNull($result);
    }

    public function testDelayedPushWithDateTimeProperlyPushesJobOntoSqs()
    {
        $now = Carbon::now();
        $queue = $this->getMockBuilder(SqsQueue::class)->onlyMethods(['createPayload', 'secondsUntil', 'getQueue'])->setConstructorArgs([$this->sqs, $this->queueName, $this->account])->getMock();
        $queue->setContainer($container = m::spy(Container::class));
        $queue->expects($this->once())->method('createPayload')->with($this->mockedJob, $this->queueName, $this->mockedData)->willReturn($this->mockedPayload);
        $queue->expects($this->once())->method('secondsUntil')->with($now->addSeconds(5))->willReturn(5);
        $queue->expects($this->once())->method('getQueue')->with($this->queueName)->willReturn($this->queueUrl);
        $this->sqs->shouldReceive('sendMessage')->once()->with(['QueueUrl' => $this->queueUrl, 'MessageBody' => $this->mockedPayload, 'DelaySeconds' => 5])->andReturn($this->mockedSendMessageResponseModel);
        $id = $queue->later($now->addSeconds(5), $this->mockedJob, $this->mockedData, $this->queueName);
        $this->assertEquals($this->mockedMessageId, $id);
        $container->shouldHaveReceived('bound')->with('events')->twice();
    }

    public function testDelayedPushProperlyPushesJobOntoSqs()
    {
        $queue = $this->getMockBuilder(SqsQueue::class)->onlyMethods(['createPayload', 'secondsUntil', 'getQueue'])->setConstructorArgs([$this->sqs, $this->queueName, $this->account])->getMock();
        $queue->setContainer($container = m::spy(Container::class));
        $queue->expects($this->once())->method('createPayload')->with($this->mockedJob, $this->queueName, $this->mockedData)->willReturn($this->mockedPayload);
        $queue->expects($this->once())->method('secondsUntil')->with($this->mockedDelay)->willReturn($this->mockedDelay);
        $queue->expects($this->once())->method('getQueue')->with($this->queueName)->willReturn($this->queueUrl);
        $this->sqs->shouldReceive('sendMessage')->once()->with(['QueueUrl' => $this->queueUrl, 'MessageBody' => $this->mockedPayload, 'DelaySeconds' => $this->mockedDelay])->andReturn($this->mockedSendMessageResponseModel);
        $id = $queue->later($this->mockedDelay, $this->mockedJob, $this->mockedData, $this->queueName);
        $this->assertEquals($this->mockedMessageId, $id);
        $container->shouldHaveReceived('bound')->with('events')->twice();
    }

    public function testPushProperlyPushesJobOntoSqs()
    {
        $queue = $this->getMockBuilder(SqsQueue::class)->onlyMethods(['createPayload', 'getQueue'])->setConstructorArgs([$this->sqs, $this->queueName, $this->account])->getMock();
        $queue->setContainer($container = m::spy(Container::class));
        $queue->expects($this->once())->method('createPayload')->with($this->mockedJob, $this->queueName, $this->mockedData)->willReturn($this->mockedPayload);
        $queue->expects($this->once())->method('getQueue')->with($this->queueName)->willReturn($this->queueUrl);
        $this->sqs->shouldReceive('sendMessage')->once()->with(['QueueUrl' => $this->queueUrl, 'MessageBody' => $this->mockedPayload])->andReturn($this->mockedSendMessageResponseModel);
        $id = $queue->push($this->mockedJob, $this->mockedData, $this->queueName);
        $this->assertEquals($this->mockedMessageId, $id);
        $container->shouldHaveReceived('bound')->with('events')->twice();
    }

    public function testSizeProperlyReadsSqsQueueSize()
    {
        $queue = $this->getMockBuilder(SqsQueue::class)->onlyMethods(['getQueue'])->setConstructorArgs([$this->sqs, $this->queueName, $this->account])->getMock();
        $queue->expects($this->once())->method('getQueue')->with($this->queueName)->willReturn($this->queueUrl);

        $this->sqs->shouldReceive('getQueueAttributes')->once()->with([
            'QueueUrl' => $this->queueUrl,
            'AttributeNames' => [
                'ApproximateNumberOfMessages',
                'ApproximateNumberOfMessagesDelayed',
                'ApproximateNumberOfMessagesNotVisible',
            ],
        ])->andReturn(new Result([
            'Attributes' => [
                'ApproximateNumberOfMessages' => 1,
                'ApproximateNumberOfMessagesDelayed' => 2,
                'ApproximateNumberOfMessagesNotVisible' => 3,
            ],
        ]));

        $size = $queue->size($this->queueName);

        $this->assertEquals(6, $size); // 1 + 2 + 3
    }

    public function testGetQueueProperlyResolvesUrlWithPrefix()
    {
        $queue = new SqsQueue($this->sqs, $this->queueName, $this->prefix);
        $this->assertEquals($this->queueUrl, $queue->getQueue(null));
        $queueUrl = $this->baseUrl.'/'.$this->account.'/test';
        $this->assertEquals($queueUrl, $queue->getQueue('test'));
    }

    public function testGetQueueProperlyResolvesFifoUrlWithPrefix()
    {
        $this->queueName = 'emails.fifo';
        $this->queueUrl = $this->prefix.$this->queueName;
        $queue = new SqsQueue($this->sqs, $this->queueName, $this->prefix);
        $this->assertEquals($this->queueUrl, $queue->getQueue(null));
        $queueUrl = $this->baseUrl.'/'.$this->account.'/test.fifo';
        $this->assertEquals($queueUrl, $queue->getQueue('test.fifo'));
    }

    public function testGetQueueProperlyResolvesUrlWithoutPrefix()
    {
        $queue = new SqsQueue($this->sqs, $this->queueUrl);
        $this->assertEquals($this->queueUrl, $queue->getQueue(null));
        $queueUrl = $this->baseUrl.'/'.$this->account.'/test';
        $this->assertEquals($queueUrl, $queue->getQueue($queueUrl));
    }

    public function testGetQueueProperlyResolvesFifoUrlWithoutPrefix()
    {
        $this->queueName = 'emails.fifo';
        $this->queueUrl = $this->prefix.$this->queueName;
        $queue = new SqsQueue($this->sqs, $this->queueUrl);
        $this->assertEquals($this->queueUrl, $queue->getQueue(null));
        $fifoQueueUrl = $this->baseUrl.'/'.$this->account.'/test.fifo';
        $this->assertEquals($fifoQueueUrl, $queue->getQueue($fifoQueueUrl));
    }

    public function testGetQueueProperlyResolvesUrlWithSuffix()
    {
        $queue = new SqsQueue($this->sqs, $this->queueName, $this->prefix, $suffix = '-staging');
        $this->assertEquals($this->queueUrl.$suffix, $queue->getQueue(null));
        $queueUrl = $this->baseUrl.'/'.$this->account.'/test'.$suffix;
        $this->assertEquals($queueUrl, $queue->getQueue('test'));
    }

    public function testGetQueueProperlyResolvesFifoUrlWithSuffix()
    {
        $this->queueName = 'emails.fifo';
        $queue = new SqsQueue($this->sqs, $this->queueName, $this->prefix, $suffix = '-staging');
        $this->assertEquals("{$this->prefix}emails-staging.fifo", $queue->getQueue(null));
        $queueUrl = $this->baseUrl.'/'.$this->account.'/test'.$suffix.'.fifo';
        $this->assertEquals($queueUrl, $queue->getQueue('test.fifo'));
    }

    public function testGetQueueEnsuresTheQueueIsOnlySuffixedOnce()
    {
        $queue = new SqsQueue($this->sqs, "{$this->queueName}-staging", $this->prefix, $suffix = '-staging');
        $this->assertEquals($this->queueUrl.$suffix, $queue->getQueue(null));
        $queueUrl = $this->baseUrl.'/'.$this->account.'/test'.$suffix;
        $this->assertEquals($queueUrl, $queue->getQueue('test-staging'));
    }

    public function testGetFifoQueueEnsuresTheQueueIsOnlySuffixedOnce()
    {
        $queue = new SqsQueue($this->sqs, "{$this->queueName}-staging.fifo", $this->prefix, $suffix = '-staging');
        $this->assertEquals("{$this->prefix}{$this->queueName}{$suffix}.fifo", $queue->getQueue(null));
        $queueUrl = $this->baseUrl.'/'.$this->account.'/test'.$suffix.'.fifo';
        $this->assertEquals($queueUrl, $queue->getQueue('test-staging.fifo'));
    }

    public function testPushProperlyPushesJobObjectOntoSqs()
    {
        $job = new FakeSqsJob();

        $queue = $this->getMockBuilder(SqsQueue::class)->onlyMethods(['createPayload', 'getQueue'])->setConstructorArgs([$this->sqs, $this->queueName, $this->account])->getMock();
        $queue->setContainer($container = m::spy(Container::class));
        $queue->expects($this->once())->method('createPayload')->with($job, $this->queueName, $this->mockedData)->willReturn($this->mockedPayload);
        $queue->expects($this->once())->method('getQueue')->with($this->queueName)->willReturn($this->queueUrl);
        $this->sqs->shouldReceive('sendMessage')->once()->with(['QueueUrl' => $this->queueUrl, 'MessageBody' => $this->mockedPayload])->andReturn($this->mockedSendMessageResponseModel);
        $id = $queue->push($job, $this->mockedData, $this->queueName);
        $this->assertEquals($this->mockedMessageId, $id);
        $container->shouldHaveReceived('bound')->with('events')->twice();
    }

    public function testPendingDispatchProperlyPushesJobObjectOntoSqs()
    {
        // Job will not be dispatched until the PendingDispatch object is destroyed.
        $pendingDispatch = FakeSqsJob::dispatch();

        $queue = $this->getMockBuilder(SqsQueue::class)->onlyMethods(['createPayload', 'getQueue'])->setConstructorArgs([$this->sqs, $this->queueName, $this->account])->getMock();
        $queue->setContainer($container = m::spy(Container::class));
        $queue->expects($this->once())->method('createPayload')->with($pendingDispatch->getJob(), $this->queueName, '')->willReturn($this->mockedPayload);
        $queue->expects($this->once())->method('getQueue')->with(null)->willReturn($this->queueUrl);
        $this->sqs->shouldReceive('sendMessage')->once()->with(['QueueUrl' => $this->queueUrl, 'MessageBody' => $this->mockedPayload])->andReturn($this->mockedSendMessageResponseModel);

        $dispatcher = new Dispatcher($container, fn () => $queue);
        app()->instance(DispatcherContract::class, $dispatcher);

        // Destroy object to trigger dispatch.
        unset($pendingDispatch);

        $container->shouldHaveReceived('bound')->with('events')->twice();
    }

    public function testPushProperlyPushesJobObjectOntoSqsFairQueue()
    {
        $job = (new FakeSqsJob())->onGroup($this->mockedMessageGroupId);

        $queue = $this->getMockBuilder(SqsQueue::class)->onlyMethods(['createPayload', 'getQueue'])->setConstructorArgs([$this->sqs, $this->queueName, $this->account])->getMock();
        $queue->setContainer($container = m::spy(Container::class));
        $queue->expects($this->once())->method('createPayload')->with($job, $this->queueName, $this->mockedData)->willReturn($this->mockedPayload);
        $queue->expects($this->once())->method('getQueue')->with($this->queueName)->willReturn($this->queueUrl);
        $this->sqs->shouldReceive('sendMessage')->once()->with(['QueueUrl' => $this->queueUrl, 'MessageBody' => $this->mockedPayload, 'MessageGroupId' => $this->mockedMessageGroupId])->andReturn($this->mockedSendMessageResponseModel);
        $id = $queue->push($job, $this->mockedData, $this->queueName);
        $this->assertEquals($this->mockedMessageId, $id);
        $container->shouldHaveReceived('bound')->with('events')->twice();
    }

    public function testPendingDispatchProperlyPushesJobObjectOntoSqsFairQueue()
    {
        $pendingDispatch = FakeSqsJob::dispatch()->onGroup($this->mockedMessageGroupId);

        $queue = $this->getMockBuilder(SqsQueue::class)->onlyMethods(['createPayload', 'getQueue'])->setConstructorArgs([$this->sqs, $this->queueName, $this->account])->getMock();
        $queue->setContainer($container = m::spy(Container::class));
        $queue->expects($this->once())->method('createPayload')->with($pendingDispatch->getJob(), $this->queueName, '')->willReturn($this->mockedPayload);
        $queue->expects($this->once())->method('getQueue')->with(null)->willReturn($this->queueUrl);
        $this->sqs->shouldReceive('sendMessage')->once()->with(['QueueUrl' => $this->queueUrl, 'MessageBody' => $this->mockedPayload, 'MessageGroupId' => $this->mockedMessageGroupId])->andReturn($this->mockedSendMessageResponseModel);

        $dispatcher = new Dispatcher($container, fn () => $queue);
        app()->instance(DispatcherContract::class, $dispatcher);

        // Destroy object to trigger dispatch.
        unset($pendingDispatch);

        $container->shouldHaveReceived('bound')->with('events')->twice();
    }

    public function testPushProperlyPushesJobStringOntoSqsFifoQueue()
    {
        Str::createUuidsUsing(fn () => $this->mockedDeduplicationId);

        $queue = $this->getMockBuilder(SqsQueue::class)->onlyMethods(['createPayload', 'getQueue'])->setConstructorArgs([$this->sqs, $this->fifoQueueName, $this->account])->getMock();
        $queue->setContainer($container = m::spy(Container::class));
        $queue->expects($this->once())->method('createPayload')->with($this->mockedJob, $this->fifoQueueName, $this->mockedData)->willReturn($this->mockedPayload);
        $queue->expects($this->once())->method('getQueue')->with($this->fifoQueueName)->willReturn($this->fifoQueueUrl);
        $this->sqs->shouldReceive('sendMessage')->once()->with([
            'QueueUrl' => $this->fifoQueueUrl,
            'MessageBody' => $this->mockedPayload,
            'MessageGroupId' => $this->fifoQueueName,
            'MessageDeduplicationId' => $this->mockedDeduplicationId,
        ])->andReturn($this->mockedSendMessageResponseModel);
        $id = $queue->push($this->mockedJob, $this->mockedData, $this->fifoQueueName);
        $this->assertEquals($this->mockedMessageId, $id);
        $container->shouldHaveReceived('bound')->with('events')->twice();

        Str::createUuidsNormally();
    }

    public function testPushProperlyPushesJobObjectOntoSqsFifoQueue()
    {
        Str::createUuidsUsing(fn () => $this->mockedDeduplicationId);

        $job = (new FakeSqsJob())->onGroup($this->mockedMessageGroupId);

        $queue = $this->getMockBuilder(SqsQueue::class)->onlyMethods(['createPayload', 'getQueue'])->setConstructorArgs([$this->sqs, $this->fifoQueueName, $this->account])->getMock();
        $queue->setContainer($container = m::spy(Container::class));
        $queue->expects($this->once())->method('createPayload')->with($job, $this->fifoQueueName, $this->mockedData)->willReturn($this->mockedPayload);
        $queue->expects($this->once())->method('getQueue')->with($this->fifoQueueName)->willReturn($this->fifoQueueUrl);
        $this->sqs->shouldReceive('sendMessage')->once()->with([
            'QueueUrl' => $this->fifoQueueUrl,
            'MessageBody' => $this->mockedPayload,
            'MessageGroupId' => $this->mockedMessageGroupId,
            'MessageDeduplicationId' => $this->mockedDeduplicationId,
        ])->andReturn($this->mockedSendMessageResponseModel);
        $id = $queue->push($job, $this->mockedData, $this->fifoQueueName);
        $this->assertEquals($this->mockedMessageId, $id);
        $container->shouldHaveReceived('bound')->with('events')->twice();

        Str::createUuidsNormally();
    }

    public function testPushProperlyPushesJobObjectOntoSqsFifoQueueWithDeduplicationId()
    {
        $job = $this->getMockBuilder(FakeSqsJobWithDeduplication::class)->onlyMethods(['deduplicationId'])->getMock();
        $job->expects($this->once())->method('deduplicationId')->with($this->mockedPayload, $this->fifoQueueName)->willReturn($this->mockedDeduplicationId);
        $job->onGroup($this->mockedMessageGroupId);

        $queue = $this->getMockBuilder(SqsQueue::class)->onlyMethods(['createPayload', 'getQueue'])->setConstructorArgs([$this->sqs, $this->fifoQueueName, $this->account])->getMock();
        $queue->setContainer($container = m::spy(Container::class));
        $queue->expects($this->once())->method('createPayload')->with($job, $this->fifoQueueName, $this->mockedData)->willReturn($this->mockedPayload);
        $queue->expects($this->once())->method('getQueue')->with($this->fifoQueueName)->willReturn($this->fifoQueueUrl);
        $this->sqs->shouldReceive('sendMessage')->once()->with([
            'QueueUrl' => $this->fifoQueueUrl,
            'MessageBody' => $this->mockedPayload,
            'MessageGroupId' => $this->mockedMessageGroupId,
            'MessageDeduplicationId' => $this->mockedDeduplicationId,
        ])->andReturn($this->mockedSendMessageResponseModel);
        $id = $queue->push($job, $this->mockedData, $this->fifoQueueName);
        $this->assertEquals($this->mockedMessageId, $id);
        $container->shouldHaveReceived('bound')->with('events')->twice();
    }

    public function testPushProperlyPushesJobObjectOntoSqsFifoQueueWithDeduplicator()
    {
        $job = $this->getMockBuilder(FakeSqsJobWithDeduplication::class)->onlyMethods(['deduplicationId'])->getMock();

        // Ensure the deduplicationId method is not called when a deduplicator callback is provided.
        $job->expects($this->never())->method('deduplicationId')->willReturn('this-should-not-be-used');
        $job->onGroup($this->mockedMessageGroupId)->withDeduplicator(function ($payload, $queue) {
            $this->assertEquals($this->mockedPayload, $payload);
            $this->assertEquals($this->fifoQueueName, $queue);

            return $this->mockedDeduplicationId;
        });

        $queue = $this->getMockBuilder(SqsQueue::class)->onlyMethods(['createPayload', 'getQueue'])->setConstructorArgs([$this->sqs, $this->fifoQueueName, $this->account])->getMock();
        $queue->setContainer($container = m::spy(Container::class));
        $queue->expects($this->once())->method('createPayload')->with($job, $this->fifoQueueName, $this->mockedData)->willReturn($this->mockedPayload);
        $queue->expects($this->once())->method('getQueue')->with($this->fifoQueueName)->willReturn($this->fifoQueueUrl);
        $this->sqs->shouldReceive('sendMessage')->once()->with([
            'QueueUrl' => $this->fifoQueueUrl,
            'MessageBody' => $this->mockedPayload,
            'MessageGroupId' => $this->mockedMessageGroupId,
            'MessageDeduplicationId' => $this->mockedDeduplicationId,
        ])->andReturn($this->mockedSendMessageResponseModel);
        $id = $queue->push($job, $this->mockedData, $this->fifoQueueName);
        $this->assertEquals($this->mockedMessageId, $id);
        $container->shouldHaveReceived('bound')->with('events')->twice();
    }

    public function testPendingDispatchProperlyPushesJobObjectOntoSqsFifoQueue()
    {
        Str::createUuidsUsing(fn () => $this->mockedDeduplicationId);

        $pendingDispatch = FakeSqsJob::dispatch()->onGroup($this->mockedMessageGroupId);

        $queue = $this->getMockBuilder(SqsQueue::class)->onlyMethods(['createPayload', 'getQueue'])->setConstructorArgs([$this->sqs, $this->fifoQueueName, $this->account])->getMock();
        $queue->setContainer($container = m::spy(Container::class));
        $queue->expects($this->once())->method('createPayload')->with($pendingDispatch->getJob(), $this->fifoQueueName, '')->willReturn($this->mockedPayload);
        $queue->expects($this->once())->method('getQueue')->with(null)->willReturn($this->fifoQueueUrl);
        $this->sqs->shouldReceive('sendMessage')->once()->with([
            'QueueUrl' => $this->fifoQueueUrl,
            'MessageBody' => $this->mockedPayload,
            'MessageGroupId' => $this->mockedMessageGroupId,
            'MessageDeduplicationId' => $this->mockedDeduplicationId,
        ])->andReturn($this->mockedSendMessageResponseModel);

        $dispatcher = new Dispatcher($container, fn () => $queue);
        app()->instance(DispatcherContract::class, $dispatcher);

        // Destroy object to trigger dispatch.
        unset($pendingDispatch);

        $container->shouldHaveReceived('bound')->with('events')->twice();

        Str::createUuidsNormally();
    }

    public function testPendingDispatchProperlyPushesJobObjectOntoSqsFifoQueueWithDeduplicationId()
    {
        FakeSqsJobWithDeduplication::createDeduplicationIdsUsing(fn ($payload, $queue) => $this->mockedDeduplicationId);

        $pendingDispatch = FakeSqsJobWithDeduplication::dispatch()->onGroup($this->mockedMessageGroupId);

        $queue = $this->getMockBuilder(SqsQueue::class)->onlyMethods(['createPayload', 'getQueue'])->setConstructorArgs([$this->sqs, $this->fifoQueueName, $this->account])->getMock();
        $queue->setContainer($container = m::spy(Container::class));
        $queue->expects($this->once())->method('createPayload')->with($pendingDispatch->getJob(), $this->fifoQueueName, '')->willReturn($this->mockedPayload);
        $queue->expects($this->once())->method('getQueue')->with(null)->willReturn($this->fifoQueueUrl);
        $this->sqs->shouldReceive('sendMessage')->once()->with([
            'QueueUrl' => $this->fifoQueueUrl,
            'MessageBody' => $this->mockedPayload,
            'MessageGroupId' => $this->mockedMessageGroupId,
            'MessageDeduplicationId' => $this->mockedDeduplicationId,
        ])->andReturn($this->mockedSendMessageResponseModel);

        $dispatcher = new Dispatcher($container, fn () => $queue);
        app()->instance(DispatcherContract::class, $dispatcher);

        // Destroy object to trigger dispatch.
        unset($pendingDispatch);

        $container->shouldHaveReceived('bound')->with('events')->twice();

        FakeSqsJobWithDeduplication::createDeduplicationIdsNormally();
    }

    public function testPendingDispatchProperlyPushesJobObjectOntoSqsFifoQueueWithDeduplicator()
    {
        FakeSqsJobWithDeduplication::createDeduplicationIdsUsing(function ($payload, $queue) {
            $this->fail('The deduplicationId method should not be called when a deduplicator callback is provided.');

            return 'this-should-not-be-used';
        });

        $pendingDispatch = FakeSqsJobWithDeduplication::dispatch()->onGroup($this->mockedMessageGroupId)->withDeduplicator(function ($payload, $queue) {
            $this->assertEquals($this->mockedPayload, $payload);
            $this->assertEquals($this->fifoQueueName, $queue);

            return $this->mockedDeduplicationId;
        });

        $queue = $this->getMockBuilder(SqsQueue::class)->onlyMethods(['createPayload', 'getQueue'])->setConstructorArgs([$this->sqs, $this->fifoQueueName, $this->account])->getMock();
        $queue->setContainer($container = m::spy(Container::class));
        $queue->expects($this->once())->method('createPayload')->with($pendingDispatch->getJob(), $this->fifoQueueName, '')->willReturn($this->mockedPayload);
        $queue->expects($this->once())->method('getQueue')->with(null)->willReturn($this->fifoQueueUrl);
        $this->sqs->shouldReceive('sendMessage')->once()->with([
            'QueueUrl' => $this->fifoQueueUrl,
            'MessageBody' => $this->mockedPayload,
            'MessageGroupId' => $this->mockedMessageGroupId,
            'MessageDeduplicationId' => $this->mockedDeduplicationId,
        ])->andReturn($this->mockedSendMessageResponseModel);

        $dispatcher = new Dispatcher($container, fn () => $queue);
        app()->instance(DispatcherContract::class, $dispatcher);

        // Destroy object to trigger dispatch.
        unset($pendingDispatch);

        $container->shouldHaveReceived('bound')->with('events')->twice();

        FakeSqsJobWithDeduplication::createDeduplicationIdsNormally();
    }

    public function testJobObjectCanBeSerializedOntoSqsFifoQueueWithDeduplicator()
    {
        // Can't reference test case property in serialized closure.
        $deduplicationId = $this->mockedDeduplicationId;

        $pendingDispatch = FakeSqsJobWithDeduplication::dispatch()->onGroup($this->mockedMessageGroupId)->withDeduplicator(function ($payload, $queue) use ($deduplicationId) {
            return $deduplicationId;
        });

        $queue = $this->getMockBuilder(SqsQueue::class)->onlyMethods(['getQueue'])->setConstructorArgs([$this->sqs, $this->fifoQueueName, $this->account])->getMock();
        $queue->setContainer($container = m::spy(Container::class));
        $queue->expects($this->once())->method('getQueue')->with(null)->willReturn($this->fifoQueueUrl);
        $this->sqs->shouldReceive('sendMessage')->once()->withArgs(function ($args) {
            $this->assertIsArray($args);
            $this->assertEqualsCanonicalizing(['QueueUrl', 'MessageBody', 'MessageGroupId', 'MessageDeduplicationId'], array_keys($args));
            $this->assertEquals($this->fifoQueueUrl, $args['QueueUrl']);
            $this->assertEquals($this->mockedMessageGroupId, $args['MessageGroupId']);
            $this->assertEquals($this->mockedDeduplicationId, $args['MessageDeduplicationId']);

            $message = json_decode($args['MessageBody'], true);
            $command = unserialize($message['data']['command'] ?? '');
            $this->assertInstanceOf(FakeSqsJobWithDeduplication::class, $command);
            $this->assertInstanceOf(SerializableClosure::class, $command->deduplicator);

            return true;
        })->andReturn($this->mockedSendMessageResponseModel);

        $dispatcher = new Dispatcher($container, fn () => $queue);
        app()->instance(DispatcherContract::class, $dispatcher);

        // Destroy object to trigger dispatch.
        unset($pendingDispatch);

        $container->shouldHaveReceived('bound')->with('events')->twice();
    }

    public function testDelayedPushProperlyPushesJobStringOntoSqsFifoQueueWithoutDelay()
    {
        Str::createUuidsUsing(fn () => $this->mockedDeduplicationId);

        $queue = $this->getMockBuilder(SqsQueue::class)->onlyMethods(['createPayload', 'secondsUntil', 'getQueue'])->setConstructorArgs([$this->sqs, $this->fifoQueueName, $this->account])->getMock();
        $queue->setContainer($container = m::spy(Container::class));
        $queue->expects($this->once())->method('createPayload')->with($this->mockedJob, $this->fifoQueueName, $this->mockedData)->willReturn($this->mockedPayload);
        $queue->expects($this->never())->method('secondsUntil')->with($this->mockedDelay)->willReturn($this->mockedDelay);
        $queue->expects($this->once())->method('getQueue')->with($this->fifoQueueName)->willReturn($this->fifoQueueUrl);
        $this->sqs->shouldReceive('sendMessage')->once()->with([
            'QueueUrl' => $this->fifoQueueUrl,
            'MessageBody' => $this->mockedPayload,
            'MessageGroupId' => $this->fifoQueueName,
            'MessageDeduplicationId' => $this->mockedDeduplicationId,
        ])->andReturn($this->mockedSendMessageResponseModel);
        $id = $queue->later($this->mockedDelay, $this->mockedJob, $this->mockedData, $this->fifoQueueName);
        $this->assertEquals($this->mockedMessageId, $id);
        $container->shouldHaveReceived('bound')->with('events')->twice();

        Str::createUuidsNormally();
    }

    public function testDelayedPushProperlyPushesJobObjectOntoSqsFifoQueueWithoutDelay()
    {
        Str::createUuidsUsing(fn () => $this->mockedDeduplicationId);

        $job = (new FakeSqsJob())->onGroup($this->mockedMessageGroupId);

        $queue = $this->getMockBuilder(SqsQueue::class)->onlyMethods(['createPayload', 'secondsUntil', 'getQueue'])->setConstructorArgs([$this->sqs, $this->fifoQueueName, $this->account])->getMock();
        $queue->setContainer($container = m::spy(Container::class));
        $queue->expects($this->once())->method('createPayload')->with($job, $this->fifoQueueName, $this->mockedData)->willReturn($this->mockedPayload);
        $queue->expects($this->never())->method('secondsUntil')->with($this->mockedDelay)->willReturn($this->mockedDelay);
        $queue->expects($this->once())->method('getQueue')->with($this->fifoQueueName)->willReturn($this->fifoQueueUrl);
        $this->sqs->shouldReceive('sendMessage')->once()->with([
            'QueueUrl' => $this->fifoQueueUrl,
            'MessageBody' => $this->mockedPayload,
            'MessageGroupId' => $this->mockedMessageGroupId,
            'MessageDeduplicationId' => $this->mockedDeduplicationId,
        ])->andReturn($this->mockedSendMessageResponseModel);
        $id = $queue->later($this->mockedDelay, $job, $this->mockedData, $this->fifoQueueName);
        $this->assertEquals($this->mockedMessageId, $id);
        $container->shouldHaveReceived('bound')->with('events')->twice();

        Str::createUuidsNormally();
    }

    public function testDelayedPendingDispatchProperlyPushesJobObjectOntoSqsFifoQueueWithoutDelay()
    {
        Str::createUuidsUsing(fn () => $this->mockedDeduplicationId);

        $pendingDispatch = FakeSqsJob::dispatch()->onGroup($this->mockedMessageGroupId)->delay($this->mockedDelay);

        $queue = $this->getMockBuilder(SqsQueue::class)->onlyMethods(['createPayload', 'getQueue'])->setConstructorArgs([$this->sqs, $this->fifoQueueName, $this->account])->getMock();
        $queue->setContainer($container = m::spy(Container::class));
        $queue->expects($this->once())->method('createPayload')->with($pendingDispatch->getJob(), $this->fifoQueueName, '')->willReturn($this->mockedPayload);
        $queue->expects($this->once())->method('getQueue')->with(null)->willReturn($this->fifoQueueUrl);
        $this->sqs->shouldReceive('sendMessage')->once()->with([
            'QueueUrl' => $this->fifoQueueUrl,
            'MessageBody' => $this->mockedPayload,
            'MessageGroupId' => $this->mockedMessageGroupId,
            'MessageDeduplicationId' => $this->mockedDeduplicationId,
        ])->andReturn($this->mockedSendMessageResponseModel);

        $dispatcher = new Dispatcher($container, fn () => $queue);
        app()->instance(DispatcherContract::class, $dispatcher);

        // Destroy object to trigger dispatch.
        unset($pendingDispatch);

        $container->shouldHaveReceived('bound')->with('events')->twice();

        Str::createUuidsNormally();
    }
}
