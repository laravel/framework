<?php

namespace Illuminate\Tests\Queue;

use Aws\Result;
use Aws\Sqs\Exception\SqsException;
use Aws\Sqs\SqsClient;
use Illuminate\Bus\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\Contracts\Bus\Dispatcher as DispatcherContract;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Jobs\SqsJob;
use Illuminate\Queue\QueueRoutes;
use Illuminate\Queue\SqsQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Tests\Queue\Fixtures\FakeSqsJob;
use Illuminate\Tests\Queue\Fixtures\FakeSqsJobWithDeduplication;
use Illuminate\Tests\Queue\Fixtures\FakeSqsJobWithDelayAttribute;
use Illuminate\Tests\Queue\Fixtures\FakeSqsJobWithMessageGroup;
use Laravel\SerializableClosure\SerializableClosure;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use RuntimeException;

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

    protected function createSpyContainer()
    {
        $container = m::spy(Container::class);

        $container->shouldReceive('bound')
            ->with('queue.routes')
            ->andReturn(true);
        $container->shouldReceive('offsetGet')
            ->with('queue.routes')
            ->andReturn(new QueueRoutes());

        return $container;
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

    public function testPendingSizeProperlyReadsSqsQueuePendingSize()
    {
        $queue = $this->getMockBuilder(SqsQueue::class)->onlyMethods(['getQueue'])->setConstructorArgs([$this->sqs, $this->queueName, $this->account])->getMock();
        $queue->expects($this->exactly(2))->method('getQueue')->with($this->queueName)->willReturn($this->queueUrl);

        $this->sqs->shouldReceive('getQueueAttributes')->once()->with([
            'QueueUrl' => $this->queueUrl,
            'AttributeNames' => ['ApproximateNumberOfMessages'],
        ])->andReturn(new Result([
            'Attributes' => [
                'ApproximateNumberOfMessages' => 1,
            ],
        ]));

        $this->assertEquals(1, $queue->pendingSize($this->queueName));

        // Test missing attribute fallback
        $this->sqs->shouldReceive('getQueueAttributes')->once()->with([
            'QueueUrl' => $this->queueUrl,
            'AttributeNames' => ['ApproximateNumberOfMessages'],
        ])->andReturn(new Result([
            'Attributes' => [],
        ]));

        $this->assertEquals(0, $queue->pendingSize($this->queueName));
    }

    public function testDelayedSizeProperlyReadsSqsQueueDelayedSize()
    {
        $queue = $this->getMockBuilder(SqsQueue::class)->onlyMethods(['getQueue'])->setConstructorArgs([$this->sqs, $this->queueName, $this->account])->getMock();
        $queue->expects($this->exactly(2))->method('getQueue')->with($this->queueName)->willReturn($this->queueUrl);

        $this->sqs->shouldReceive('getQueueAttributes')->once()->with([
            'QueueUrl' => $this->queueUrl,
            'AttributeNames' => ['ApproximateNumberOfMessagesDelayed'],
        ])->andReturn(new Result([
            'Attributes' => [
                'ApproximateNumberOfMessagesDelayed' => 2,
            ],
        ]));

        $this->assertEquals(2, $queue->delayedSize($this->queueName));

        // Test missing attribute fallback
        $this->sqs->shouldReceive('getQueueAttributes')->once()->with([
            'QueueUrl' => $this->queueUrl,
            'AttributeNames' => ['ApproximateNumberOfMessagesDelayed'],
        ])->andReturn(new Result([
            'Attributes' => [],
        ]));

        $this->assertEquals(0, $queue->delayedSize($this->queueName));
    }

    public function testReservedSizeProperlyReadsSqsQueueReservedSize()
    {
        $queue = $this->getMockBuilder(SqsQueue::class)->onlyMethods(['getQueue'])->setConstructorArgs([$this->sqs, $this->queueName, $this->account])->getMock();
        $queue->expects($this->exactly(2))->method('getQueue')->with($this->queueName)->willReturn($this->queueUrl);

        $this->sqs->shouldReceive('getQueueAttributes')->once()->with([
            'QueueUrl' => $this->queueUrl,
            'AttributeNames' => ['ApproximateNumberOfMessagesNotVisible'],
        ])->andReturn(new Result([
            'Attributes' => [
                'ApproximateNumberOfMessagesNotVisible' => 3,
            ],
        ]));

        $this->assertEquals(3, $queue->reservedSize($this->queueName));

        // Test missing attribute fallback
        $this->sqs->shouldReceive('getQueueAttributes')->once()->with([
            'QueueUrl' => $this->queueUrl,
            'AttributeNames' => ['ApproximateNumberOfMessagesNotVisible'],
        ])->andReturn(new Result([
            'Attributes' => [],
        ]));

        $this->assertEquals(0, $queue->reservedSize($this->queueName));
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
        $this->assertSame("{$this->prefix}emails-staging.fifo", $queue->getQueue(null));
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
        $this->assertSame("{$this->prefix}{$this->queueName}{$suffix}.fifo", $queue->getQueue(null));
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
        $queue->setContainer($container = $this->createSpyContainer());
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
        $queue->setContainer($container = $this->createSpyContainer());
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

    public function testPushProperlyPushesJobObjectOntoSqsFifoQueueWithMessageGroupMethod()
    {
        Str::createUuidsUsing(fn () => $this->mockedDeduplicationId);

        $job = $this->getMockBuilder(FakeSqsJobWithMessageGroup::class)->onlyMethods(['messageGroup'])->getMock();
        $job->expects($this->once())->method('messageGroup')->willReturn($this->mockedMessageGroupId);

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

    public function testPushProperlyPushesJobObjectOntoSqsFifoQueueWithMessageGroupPropertyOverridingMethod()
    {
        Str::createUuidsUsing(fn () => $this->mockedDeduplicationId);

        $job = $this->getMockBuilder(FakeSqsJobWithMessageGroup::class)->onlyMethods(['messageGroup'])->getMock();

        // Ensure the messageGroup method is not called when a messageGroup property is provided.
        $job->expects($this->never())->method('messageGroup')->willReturn('this-should-not-be-used');
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
        $queue->setContainer($container = $this->createSpyContainer());
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
        $queue->setContainer($container = $this->createSpyContainer());
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
        $queue->setContainer($container = $this->createSpyContainer());
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
        $queue->setContainer($container = $this->createSpyContainer());
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
        $queue->setContainer($container = $this->createSpyContainer());
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

    public function testPushRawStoresPayloadToCacheWhenExceedingThreshold()
    {
        $uuid = 'test-uuid-1234';
        $largePayload = json_encode(['uuid' => $uuid, 'job' => 'App\\Jobs\\TestJob', 'data' => str_repeat('x', SqsQueue::MAX_SQS_PAYLOAD_SIZE)]);
        $expectedPath = 'laravel:sqs-payloads:'.$uuid;
        $expectedPointer = json_encode(['@pointer' => $expectedPath]);

        $store = m::mock(CacheRepository::class);
        $store->shouldReceive('put')->once()->with($expectedPath, $largePayload);

        $cache = m::mock(CacheFactory::class);
        $cache->shouldReceive('store')->with('database')->andReturn($store);

        $container = m::mock(Container::class);
        $container->shouldReceive('make')->with('cache')->andReturn($cache);

        $queue = new SqsQueue($this->sqs, $this->queueName, $this->prefix, '', false, [
            'enabled' => true,
            'store' => 'database',
            'always' => false,
            'delete_after_processing' => true,
        ]);
        $queue->setContainer($container);

        $this->sqs->shouldReceive('sendMessage')->once()->withArgs(function ($args) use ($expectedPointer) {
            return $args['MessageBody'] === $expectedPointer;
        })->andReturn($this->mockedSendMessageResponseModel);

        $queue->pushRaw($largePayload, $this->queueName);
    }

    public function testPushRawDoesNotStoreToCacheWhenBelowThreshold()
    {
        $smallPayload = json_encode(['uuid' => 'test-uuid', 'job' => 'App\\Jobs\\TestJob', 'data' => 'small']);

        $queue = new SqsQueue($this->sqs, $this->queueName, $this->prefix, '', false, [
            'enabled' => true,
            'store' => 'database',
            'always' => false,
            'delete_after_processing' => true,
        ]);
        $queue->setContainer(m::mock(Container::class));

        $this->sqs->shouldReceive('sendMessage')->once()->withArgs(function ($args) use ($smallPayload) {
            return $args['MessageBody'] === $smallPayload;
        })->andReturn($this->mockedSendMessageResponseModel);

        $queue->pushRaw($smallPayload, $this->queueName);
    }

    public function testPushRawAlwaysStoresToCacheWhenAlwaysIsTrue()
    {
        $uuid = 'test-uuid-always';
        $smallPayload = json_encode(['uuid' => $uuid, 'job' => 'App\\Jobs\\TestJob', 'data' => 'small']);
        $expectedPath = 'laravel:sqs-payloads:'.$uuid;
        $expectedPointer = json_encode(['@pointer' => $expectedPath]);

        $store = m::mock(CacheRepository::class);
        $store->shouldReceive('put')->once()->with($expectedPath, $smallPayload);

        $cache = m::mock(CacheFactory::class);
        $cache->shouldReceive('store')->with('database')->andReturn($store);

        $container = m::mock(Container::class);
        $container->shouldReceive('make')->with('cache')->andReturn($cache);

        $queue = new SqsQueue($this->sqs, $this->queueName, $this->prefix, '', false, [
            'enabled' => true,
            'store' => 'database',
            'always' => true,
            'delete_after_processing' => true,
        ]);
        $queue->setContainer($container);

        $this->sqs->shouldReceive('sendMessage')->once()->withArgs(function ($args) use ($expectedPointer) {
            return $args['MessageBody'] === $expectedPointer;
        })->andReturn($this->mockedSendMessageResponseModel);

        $queue->pushRaw($smallPayload, $this->queueName);
    }

    public function testPushRawDoesNotStoreToCacheWhenNotEnabled()
    {
        $largePayload = json_encode(['uuid' => 'test-uuid', 'job' => 'App\\Jobs\\TestJob', 'data' => str_repeat('x', SqsQueue::MAX_SQS_PAYLOAD_SIZE)]);

        $queue = new SqsQueue($this->sqs, $this->queueName, $this->prefix);
        $queue->setContainer(m::mock(Container::class));

        $this->sqs->shouldReceive('sendMessage')->once()->withArgs(function ($args) use ($largePayload) {
            return $args['MessageBody'] === $largePayload;
        })->andReturn($this->mockedSendMessageResponseModel);

        $queue->pushRaw($largePayload, $this->queueName);
    }

    public function testClearFlushesOverflowStoreWhenFlushOnClearEnabled()
    {
        $store = m::mock(CacheRepository::class);
        $store->shouldReceive('flush')->once();

        $cache = m::mock(CacheFactory::class);
        $cache->shouldReceive('store')->once()->with('database')->andReturn($store);

        $container = m::mock(Container::class);
        $container->shouldReceive('make')->once()->with('cache')->andReturn($cache);

        $queue = $this->getMockBuilder(SqsQueue::class)
            ->onlyMethods(['getQueue', 'size'])
            ->setConstructorArgs([$this->sqs, $this->queueName, $this->prefix, '', false, [
                'enabled' => true,
                'store' => 'database',
                'always' => false,
                'delete_after_processing' => true,
                'flush_on_clear' => true,
            ]])
            ->getMock();
        $queue->setContainer($container);
        $queue->expects($this->once())->method('getQueue')->willReturn($this->queueUrl);
        $queue->expects($this->once())->method('size')->willReturn(5);

        $this->sqs->shouldReceive('purgeQueue')->once();

        $queue->clear($this->queueName);
    }

    public function testClearDoesNotFlushOverflowStoreWhenFlushOnClearDisabled()
    {
        $container = m::mock(Container::class);
        $container->shouldNotReceive('make');

        $queue = $this->getMockBuilder(SqsQueue::class)
            ->onlyMethods(['getQueue', 'size'])
            ->setConstructorArgs([$this->sqs, $this->queueName, $this->prefix, '', false, [
                'enabled' => true,
                'store' => 'database',
                'always' => false,
                'delete_after_processing' => true,
                'flush_on_clear' => false,
            ]])
            ->getMock();
        $queue->setContainer($container);
        $queue->expects($this->once())->method('getQueue')->willReturn($this->queueUrl);
        $queue->expects($this->once())->method('size')->willReturn(5);

        $this->sqs->shouldReceive('purgeQueue')->once();

        $queue->clear($this->queueName);
    }

    public function testClearDoesNotFlushOverflowStoreWhenOverflowDisabled()
    {
        $container = m::mock(Container::class);
        $container->shouldNotReceive('make');

        $queue = $this->getMockBuilder(SqsQueue::class)
            ->onlyMethods(['getQueue', 'size'])
            ->setConstructorArgs([$this->sqs, $this->queueName, $this->prefix, '', false, [
                'enabled' => false,
                'store' => 'database',
                'always' => false,
                'delete_after_processing' => true,
                'flush_on_clear' => true,
            ]])
            ->getMock();
        $queue->setContainer($container);
        $queue->expects($this->once())->method('getQueue')->willReturn($this->queueUrl);
        $queue->expects($this->once())->method('size')->willReturn(5);

        $this->sqs->shouldReceive('purgeQueue')->once();

        $queue->clear($this->queueName);
    }

    public function testClearForwardsConfiguredStoreNameToFactory()
    {
        $store = m::mock(CacheRepository::class);
        $store->shouldReceive('flush')->once();

        $cache = m::mock(CacheFactory::class);
        $cache->shouldReceive('store')->once()->with('redis')->andReturn($store);

        $container = m::mock(Container::class);
        $container->shouldReceive('make')->once()->with('cache')->andReturn($cache);

        $queue = $this->getMockBuilder(SqsQueue::class)
            ->onlyMethods(['getQueue', 'size'])
            ->setConstructorArgs([$this->sqs, $this->queueName, $this->prefix, '', false, [
                'enabled' => true,
                'store' => 'redis',
                'always' => false,
                'delete_after_processing' => true,
                'flush_on_clear' => true,
            ]])
            ->getMock();
        $queue->setContainer($container);
        $queue->expects($this->once())->method('getQueue')->willReturn($this->queueUrl);
        $queue->expects($this->once())->method('size')->willReturn(5);

        $this->sqs->shouldReceive('purgeQueue')->once();

        $queue->clear($this->queueName);
    }

    public function testBulkSendsAllJobsInASingleBatchRequest()
    {
        $queue = $this->getMockBuilder(SqsQueue::class)
            ->onlyMethods(['getQueue', 'createPayload'])
            ->setConstructorArgs([$this->sqs, $this->queueName, $this->account])
            ->getMock();
        $queue->setContainer(m::spy(Container::class));
        $queue->expects($this->once())->method('getQueue')->with($this->queueName)->willReturn($this->queueUrl);
        $queue->expects($this->exactly(3))->method('createPayload')->willReturnOnConsecutiveCalls('p1', 'p2', 'p3');

        $captured = null;

        $this->sqs->shouldReceive('sendMessageBatch')->once()->with(m::on(function ($args) use (&$captured) {
            $captured = $args;

            return true;
        }))->andReturn(new Result([
            'Successful' => [
                ['Id' => 'placeholder', 'MessageId' => 'mid-1'],
            ],
            'Failed' => [],
        ]));

        $queue->bulk(['a', 'b', 'c'], 'data', $this->queueName);

        $this->assertSame($this->queueUrl, $captured['QueueUrl']);
        $this->assertCount(3, $captured['Entries']);
        $this->assertSame(['p1', 'p2', 'p3'], array_column($captured['Entries'], 'MessageBody'));
    }

    public function testBulkChunksAtTenMessagesPerBatch()
    {
        $queue = $this->getMockBuilder(SqsQueue::class)
            ->onlyMethods(['getQueue', 'createPayload'])
            ->setConstructorArgs([$this->sqs, $this->queueName, $this->account])
            ->getMock();
        $queue->setContainer(m::spy(Container::class));
        $queue->expects($this->once())->method('getQueue')->willReturn($this->queueUrl);
        $queue->method('createPayload')->willReturnCallback(fn ($job) => "payload-{$job}");

        $batchSizes = [];

        $this->sqs->shouldReceive('sendMessageBatch')->twice()->with(m::on(function ($args) use (&$batchSizes) {
            $batchSizes[] = count($args['Entries']);

            return true;
        }))->andReturn(new Result(['Successful' => [], 'Failed' => []]));

        $queue->bulk(range(1, 15), 'data', $this->queueName);

        $this->assertSame([10, 5], $batchSizes);
    }

    public function testBulkChunksWhenCumulativePayloadSizeExceedsLimit()
    {
        $queue = $this->getMockBuilder(SqsQueue::class)
            ->onlyMethods(['getQueue', 'createPayload'])
            ->setConstructorArgs([$this->sqs, $this->queueName, $this->account])
            ->getMock();
        $queue->setContainer(m::spy(Container::class));
        $queue->expects($this->once())->method('getQueue')->willReturn($this->queueUrl);

        $halfPayload = str_repeat('x', (int) (SqsQueue::MAX_SQS_PAYLOAD_SIZE * 0.6));
        $queue->method('createPayload')->willReturn($halfPayload);

        $batchSizes = [];

        $this->sqs->shouldReceive('sendMessageBatch')->twice()->with(m::on(function ($args) use (&$batchSizes) {
            $batchSizes[] = count($args['Entries']);

            return true;
        }))->andReturn(new Result(['Successful' => [], 'Failed' => []]));

        $queue->bulk(['a', 'b'], 'data', $this->queueName);

        $this->assertSame([1, 1], $batchSizes);
    }

    public function testBulkRaisesQueueingAndQueuedEventsForEachJob()
    {
        $events = m::mock(\Illuminate\Contracts\Events\Dispatcher::class);
        $dispatched = [];
        $events->shouldReceive('dispatch')->andReturnUsing(function ($event) use (&$dispatched) {
            $dispatched[] = $event;
        });

        $container = m::mock(Container::class);
        $container->shouldReceive('bound')->with('events')->andReturn(true);
        $container->shouldReceive('bound')->with('db.transactions')->andReturn(false);
        $container->shouldReceive('offsetGet')->with('events')->andReturn($events);

        $queue = $this->getMockBuilder(SqsQueue::class)
            ->onlyMethods(['getQueue', 'createPayload'])
            ->setConstructorArgs([$this->sqs, $this->queueName, $this->account])
            ->getMock();
        $queue->setContainer($container);
        $queue->setConnectionName('sqs');
        $queue->expects($this->once())->method('getQueue')->willReturn($this->queueUrl);
        $queue->method('createPayload')->willReturnCallback(fn ($job) => "payload-{$job}");

        $this->sqs->shouldReceive('sendMessageBatch')->once()->andReturnUsing(function ($args) {
            $successful = array_map(
                fn ($entry, $i) => ['Id' => $entry['Id'], 'MessageId' => 'mid-'.$i],
                $args['Entries'],
                array_keys($args['Entries'])
            );

            return new Result(['Successful' => $successful, 'Failed' => []]);
        });

        $queue->bulk(['a', 'b'], 'data', $this->queueName);

        $queueingEvents = array_filter($dispatched, fn ($e) => $e instanceof \Illuminate\Queue\Events\JobQueueing);
        $queuedEvents = array_filter($dispatched, fn ($e) => $e instanceof \Illuminate\Queue\Events\JobQueued);

        $this->assertCount(2, $queueingEvents);
        $this->assertCount(2, $queuedEvents);
        $this->assertSame(['mid-0', 'mid-1'], array_map(fn ($e) => $e->id, array_values($queuedEvents)));
    }

    public function testBulkHonoursPerJobDelay()
    {
        $jobA = new FakeSqsJob;
        $jobA->delay = 30;

        $jobB = new FakeSqsJob;

        $queue = $this->getMockBuilder(SqsQueue::class)
            ->onlyMethods(['getQueue', 'createPayload', 'secondsUntil'])
            ->setConstructorArgs([$this->sqs, $this->queueName, $this->account])
            ->getMock();
        $queue->setContainer(m::spy(Container::class));
        $queue->expects($this->once())->method('getQueue')->willReturn($this->queueUrl);
        $queue->method('createPayload')->willReturnCallback(fn ($job, $q, $data, $delay) => 'payload-'.($delay ?? 'none'));
        $queue->method('secondsUntil')->with(30)->willReturn(30);

        $captured = null;

        $this->sqs->shouldReceive('sendMessageBatch')->once()->with(m::on(function ($args) use (&$captured) {
            $captured = $args;

            return true;
        }))->andReturn(new Result(['Successful' => [], 'Failed' => []]));

        $queue->bulk([$jobA, $jobB], 'data', $this->queueName);

        $this->assertSame(30, $captured['Entries'][0]['DelaySeconds']);
        $this->assertArrayNotHasKey('DelaySeconds', $captured['Entries'][1]);
    }

    public function testBulkHonoursDelayAttribute()
    {
        $queue = $this->getMockBuilder(SqsQueue::class)
            ->onlyMethods(['getQueue', 'createPayload', 'secondsUntil'])
            ->setConstructorArgs([$this->sqs, $this->queueName, $this->account])
            ->getMock();
        $queue->setContainer(m::spy(Container::class));
        $queue->expects($this->once())->method('getQueue')->willReturn($this->queueUrl);
        $queue->method('createPayload')->willReturnCallback(fn ($job, $q, $data, $delay) => 'payload-'.($delay ?? 'none'));
        $queue->method('secondsUntil')->with(15)->willReturn(15);

        $captured = null;

        $this->sqs->shouldReceive('sendMessageBatch')->once()->with(m::on(function ($args) use (&$captured) {
            $captured = $args;

            return true;
        }))->andReturn(new Result(['Successful' => [], 'Failed' => []]));

        $queue->bulk([new FakeSqsJobWithDelayAttribute, new FakeSqsJob], 'data', $this->queueName);

        $this->assertSame(15, $captured['Entries'][0]['DelaySeconds']);
        $this->assertArrayNotHasKey('DelaySeconds', $captured['Entries'][1]);
    }

    public function testBulkThrowsWhenSqsReportsFailedEntries()
    {
        $queue = $this->getMockBuilder(SqsQueue::class)
            ->onlyMethods(['getQueue', 'createPayload'])
            ->setConstructorArgs([$this->sqs, $this->queueName, $this->account])
            ->getMock();
        $queue->setContainer(m::spy(Container::class));
        $queue->expects($this->once())->method('getQueue')->willReturn($this->queueUrl);
        $queue->expects($this->once())->method('createPayload')->willReturn('payload-a');

        $this->sqs->shouldReceive('sendMessageBatch')->once()->andReturnUsing(function ($args) {
            return new Result([
                'Successful' => [],
                'Failed' => [
                    ['Id' => $args['Entries'][0]['Id'], 'Code' => 'InternalError', 'Message' => 'oops', 'SenderFault' => false],
                ],
            ]);
        });

        try {
            $queue->bulk(['a'], 'data', $this->queueName);

            $this->fail('SqsException was not thrown.');
        } catch (SqsException $e) {
            $this->assertSame(
                'SQS SendMessageBatch rejected [1] of [1] messages. First failure [InternalError]: oops',
                $e->getMessage()
            );
            $this->assertSame('InternalError', $e->getAwsErrorCode());
            $this->assertSame('oops', $e->getAwsErrorMessage());
            $this->assertNotNull($e->getResult());
        }
    }

    public function testBulkSendsFifoBatchesSequentiallyUsingTheQueueNameForMessageGroups()
    {
        $queue = $this->getMockBuilder(SqsQueue::class)
            ->onlyMethods(['getQueue', 'createPayload'])
            ->setConstructorArgs([$this->sqs, $this->fifoQueueName, $this->account])
            ->getMock();
        $queue->setContainer(m::spy(Container::class));
        $queue->expects($this->once())->method('getQueue')->with($this->fifoQueueName)->willReturn($this->fifoQueueUrl);
        $queue->method('createPayload')->willReturnCallback(fn ($job) => "payload-{$job}");

        $captured = [];

        $this->sqs->shouldReceive('sendMessageBatch')->twice()->with(m::on(function ($args) use (&$captured) {
            $captured[] = $args;

            return true;
        }))->andReturn(new Result(['Successful' => [], 'Failed' => []]));

        $queue->bulk(range(1, 15), 'data', $this->fifoQueueName);

        $this->assertSame([10, 5], array_map(fn ($args) => count($args['Entries']), $captured));
        $this->assertSame($this->fifoQueueUrl, $captured[0]['QueueUrl']);
        $this->assertSame($this->fifoQueueName, $captured[0]['Entries'][0]['MessageGroupId']);
        $this->assertNotEmpty($captured[0]['Entries'][0]['MessageDeduplicationId']);
    }

    public function testBulkStopsSendingFifoBatchesAfterAFailedRequest()
    {
        $queue = $this->getMockBuilder(SqsQueue::class)
            ->onlyMethods(['getQueue', 'createPayload'])
            ->setConstructorArgs([$this->sqs, $this->fifoQueueName, $this->account])
            ->getMock();
        $queue->setContainer(m::spy(Container::class));
        $queue->expects($this->once())->method('getQueue')->willReturn($this->fifoQueueUrl);
        $queue->method('createPayload')->willReturnCallback(fn ($job) => "payload-{$job}");

        // Only the first chunk is attempted; its exception propagates untouched and later chunks are not sent.
        $this->sqs->shouldReceive('sendMessageBatch')->once()->andThrow(new RuntimeException('SQS is down'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SQS is down');

        $queue->bulk(range(1, 15), 'data', $this->fifoQueueName);
    }

    public function testBulkDefersAfterCommitJobsUntilTheTransactionCommits()
    {
        $job = new FakeSqsJob;
        $job->afterCommit = true;

        $transactions = m::mock(\Illuminate\Database\DatabaseTransactionsManager::class);

        $committed = null;

        $transactions->shouldReceive('addCallback')->once()->andReturnUsing(function ($callback) use (&$committed) {
            $committed = $callback;
        });

        $container = m::mock(Container::class);
        $container->shouldReceive('bound')->with('db.transactions')->andReturn(true);
        $container->shouldReceive('bound')->with('events')->andReturn(false);
        $container->shouldReceive('make')->with('db.transactions')->andReturn($transactions);

        $queue = $this->getMockBuilder(SqsQueue::class)
            ->onlyMethods(['getQueue', 'createPayload'])
            ->setConstructorArgs([$this->sqs, $this->queueName, $this->account])
            ->getMock();
        $queue->setContainer($container);
        $queue->expects($this->once())->method('getQueue')->willReturn($this->queueUrl);
        $queue->expects($this->once())->method('createPayload')->willReturn('payload-a');

        $sent = false;

        $this->sqs->shouldReceive('sendMessageBatch')->once()->andReturnUsing(function () use (&$sent) {
            $sent = true;

            return new Result(['Successful' => [], 'Failed' => []]);
        });

        $queue->bulk([$job], 'data', $this->queueName);

        // The payload is created at dispatch time, but nothing is sent until commit...
        $this->assertNotNull($committed);
        $this->assertFalse($sent);

        $committed();

        $this->assertTrue($sent);
    }

    public function testBulkRegistersRollbackCallbacksForUniqueAfterCommitJobs()
    {
        $job = new class implements ShouldQueue, ShouldBeUnique
        {
            use Queueable;
        };
        $job->afterCommit = true;

        $transactions = m::mock(\Illuminate\Database\DatabaseTransactionsManager::class);
        $transactions->shouldReceive('addCallbackForRollback')->once();
        $transactions->shouldReceive('addCallback')->once();

        $container = m::mock(Container::class);
        $container->shouldReceive('bound')->with('db.transactions')->andReturn(true);
        $container->shouldReceive('make')->with('db.transactions')->andReturn($transactions);

        $queue = $this->getMockBuilder(SqsQueue::class)
            ->onlyMethods(['getQueue', 'createPayload'])
            ->setConstructorArgs([$this->sqs, $this->queueName, $this->account])
            ->getMock();
        $queue->setContainer($container);
        $queue->expects($this->once())->method('createPayload')->willReturn('payload-a');

        $queue->bulk([$job], 'data', $this->queueName);
    }

    public function testBulkComputesQueueableOptionsBeforeApplyingOverflow()
    {
        $job = new FakeSqsJob;
        $job->messageGroup = 'group-1';
        $job->deduplicator = fn ($payload, $queue) => 'dedupe-'.$payload;

        $store = m::mock(CacheRepository::class);
        $store->shouldReceive('put')->once()->with(m::type('string'), 'original-payload');

        $cache = m::mock(CacheFactory::class);
        $cache->shouldReceive('store')->with('sqs-overflow')->andReturn($store);

        $container = m::mock(Container::class);
        $container->shouldReceive('bound')->andReturn(false);
        $container->shouldReceive('make')->with('cache')->andReturn($cache);

        $queue = $this->getMockBuilder(SqsQueue::class)
            ->onlyMethods(['getQueue', 'createPayload'])
            ->setConstructorArgs([$this->sqs, $this->fifoQueueName, $this->account, '', false, ['enabled' => true, 'always' => true, 'store' => 'sqs-overflow']])
            ->getMock();
        $queue->setContainer($container);
        $queue->expects($this->once())->method('getQueue')->willReturn($this->fifoQueueUrl);
        $queue->expects($this->once())->method('createPayload')->willReturn('original-payload');

        $captured = null;

        $this->sqs->shouldReceive('sendMessageBatch')->once()->with(m::on(function ($args) use (&$captured) {
            $captured = $args;

            return true;
        }))->andReturn(new Result(['Successful' => [], 'Failed' => []]));

        $queue->bulk([$job], 'data', $this->fifoQueueName);

        // The deduplicator receives the original payload, while the message body is the overflow pointer...
        $this->assertSame('dedupe-original-payload', $captured['Entries'][0]['MessageDeduplicationId']);
        $this->assertSame('group-1', $captured['Entries'][0]['MessageGroupId']);
        $this->assertStringStartsWith('{"@pointer":"laravel:sqs-payloads:', $captured['Entries'][0]['MessageBody']);
    }

    public function testBulkFiresQueuedEventsForSuccessfulChunksWhenAnotherChunkFails()
    {
        $events = m::mock(\Illuminate\Contracts\Events\Dispatcher::class);
        $dispatched = [];
        $events->shouldReceive('dispatch')->andReturnUsing(function ($event) use (&$dispatched) {
            $dispatched[] = $event;
        });

        $container = m::mock(Container::class);
        $container->shouldReceive('bound')->with('events')->andReturn(true);
        $container->shouldReceive('bound')->with('db.transactions')->andReturn(false);
        $container->shouldReceive('offsetGet')->with('events')->andReturn($events);

        $queue = $this->getMockBuilder(SqsQueue::class)
            ->onlyMethods(['getQueue', 'createPayload'])
            ->setConstructorArgs([$this->sqs, $this->queueName, $this->account])
            ->getMock();
        $queue->setContainer($container);
        $queue->setConnectionName('sqs');
        $queue->expects($this->once())->method('getQueue')->willReturn($this->queueUrl);
        $queue->method('createPayload')->willReturnCallback(fn ($job) => "payload-{$job}");

        $calls = 0;

        $this->sqs->shouldReceive('sendMessageBatch')->twice()->andReturnUsing(function ($args) use (&$calls) {
            if ($calls++ === 0) {
                return new Result([
                    'Successful' => array_map(
                        fn ($entry, $i) => ['Id' => $entry['Id'], 'MessageId' => 'mid-'.$i],
                        $args['Entries'],
                        array_keys($args['Entries'])
                    ),
                    'Failed' => [],
                ]);
            }

            throw new RuntimeException('chunk failed');
        });

        try {
            $queue->bulk(range(1, 15), 'data', $this->queueName);

            $this->fail('RuntimeException was not thrown.');
        } catch (RuntimeException $e) {
            $this->assertSame('chunk failed', $e->getMessage());
        }

        // The first chunk was queued before the second failed, so its queued events must already have fired.
        $queuedEvents = array_filter($dispatched, fn ($e) => $e instanceof \Illuminate\Queue\Events\JobQueued);

        $this->assertCount(10, $queuedEvents);
    }

    public function testBulkRethrowsTheOriginalExceptionWhenASingleBatchRequestFails()
    {
        $queue = $this->getMockBuilder(SqsQueue::class)
            ->onlyMethods(['getQueue', 'createPayload'])
            ->setConstructorArgs([$this->sqs, $this->queueName, $this->account])
            ->getMock();
        $queue->setContainer(m::spy(Container::class));
        $queue->expects($this->once())->method('getQueue')->willReturn($this->queueUrl);
        $queue->expects($this->once())->method('createPayload')->willReturn('payload-a');

        $this->sqs->shouldReceive('sendMessageBatch')->once()->andThrow(new RuntimeException('SQS is down'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SQS is down');

        $queue->bulk(['a'], 'data', $this->queueName);
    }

    public function testBulkDoesNothingWithEmptyInput()
    {
        $queue = new SqsQueue($this->sqs, $this->queueName, $this->account);
        $queue->setContainer(m::mock(Container::class));

        $this->sqs->shouldNotReceive('sendMessageBatch');

        $queue->bulk([], 'data', $this->queueName);
    }

    public function testPopPassesOverflowStorageOptionsToJob()
    {
        $overflowStorage = [
            'enabled' => true,
            'store' => 'database',
            'always' => false,
            'delete_after_processing' => true,
        ];

        $queue = $this->getMockBuilder(SqsQueue::class)
            ->onlyMethods(['getQueue'])
            ->setConstructorArgs([$this->sqs, $this->queueName, $this->account, '', false, $overflowStorage])
            ->getMock();
        $queue->setContainer(m::mock(Container::class));
        $queue->expects($this->once())->method('getQueue')->with($this->queueName)->willReturn($this->queueUrl);

        $this->sqs->shouldReceive('receiveMessage')->once()->andReturn($this->mockedReceiveMessageResponseModel);

        $job = $queue->pop($this->queueName);

        $this->assertInstanceOf(SqsJob::class, $job);
    }
}
