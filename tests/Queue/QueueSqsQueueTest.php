<?php

namespace Illuminate\Tests\Queue;

use Aws\Result;
use Mockery as m;
use Aws\Sqs\SqsClient;
use Illuminate\Queue\SqsQueue;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Illuminate\Queue\Jobs\SqsJob;
use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Queue as QueueContract;

class QueueSqsQueueTest extends TestCase
{
    /** @var QueueContract */
    private $sqsQueue;

    const QUEUE_NAME = 'emails';
    const URL = 'https://sqs.someregion.amazonaws.com/1234567891011/emails-queue';

    private $mockedSqsClient;
    private $mockedJob;
    private $mockedData;
    private $mockedPayload;
    private $mockedDelay;
    private $mockedMessageId;
    private $mockedReceiptHandle;
    private $mockedSendMessageResponseModel;
    private $mockedReceiveMessageResponseModel;
    private $mockedReceiveEmptyMessageResponseModel;
    private $mockedQueueAttributesResponseModel;

    public function tearDown()
    {
        m::close();
    }

    public function setUp()
    {
        // Use Mockery to mock the SqsClient
        $this->mockedSqsClient = m::mock(SqsClient::class);

        $this->sqsQueue = new SqsQueue($this->mockedSqsClient, self::QUEUE_NAME, self::URL);
        $this->sqsQueue->setContainer(m::mock(Container::class));

        $this->mockedJob = 'foo';
        $this->mockedData = ['data'];
        $this->mockedPayload = json_encode([
            'displayName' => $this->mockedJob,
            'job' => $this->mockedJob,
            'maxTries' => null,
            'timeout' => null,
            'data' => $this->mockedData,
        ]);

        $this->mockedDelay = 10;
        $this->mockedMessageId = 'e3cd03ee-59a3-4ad8-b0aa-ee2e3808ac81';
        $this->mockedReceiptHandle = '0NNAq8PwvXuWv5gMtS9DJ8qEdyiUwbAjpp45w2m6M4SJ1Y+PxCh7R930NRB8ylSacEmoSnW18bgd4nK\/O6ctE+VFVul4eD23mA07vVoSnPI4F\/voI1eNCp6Iax0ktGmhlNVzBwaZHEr91BRtqTRM3QKd2ASF8u+IQaSwyl\/DGK+P1+dqUOodvOVtExJwdyDLy1glZVgm85Yw9Jf5yZEEErqRwzYz\/qSigdvW4sm2l7e4phRol\/+IjMtovOyH\/ukueYdlVbQ4OshQLENhUKe7RNN5i6bE\/e5x9bnPhfj2gbM';

        $this->mockedSendMessageResponseModel = new Result([
            'Body'          => $this->mockedPayload,
            'MD5OfBody'     => md5($this->mockedPayload),
            'ReceiptHandle' => $this->mockedReceiptHandle,
            'MessageId'     => $this->mockedMessageId,
            'Attributes'    => ['ApproximateReceiveCount' => 1],
        ]);

        $this->mockedReceiveMessageResponseModel = new Result([
            'Messages' => [
                0 => [
                    'Body'          => $this->mockedPayload,
                    'MD5OfBody'     => md5($this->mockedPayload),
                    'ReceiptHandle' => $this->mockedReceiptHandle,
                    'MessageId'     => $this->mockedMessageId,
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
        $this->mockedSqsClient->shouldReceive('receiveMessage')->once()->with([
            'QueueUrl'       => self::URL,
            'AttributeNames' => ['ApproximateReceiveCount'],
        ])->andReturn($this->mockedReceiveMessageResponseModel);

        $result = $this->sqsQueue->pop(self::QUEUE_NAME);
        $this->assertInstanceOf(SqsJob::class, $result);
    }

    public function testPopProperlyHandlesEmptyMessage()
    {
        $this->mockedSqsClient->shouldReceive('receiveMessage')->once()->with([
            'QueueUrl'       => self::URL,
            'AttributeNames' => ['ApproximateReceiveCount'],
        ])->andReturn($this->mockedReceiveEmptyMessageResponseModel);

        $result = $this->sqsQueue->pop(self::QUEUE_NAME);
        $this->assertNull($result);
    }

    public function testDelayedPushWithDateTimeProperlyPushesJobOntoSqs()
    {
        $now = Carbon::now();

        $this->mockedSqsClient->shouldReceive('sendMessage')->once()->with([
            'QueueUrl'     => self::URL,
            'MessageBody'  => $this->mockedPayload,
            'DelaySeconds' => 5,
        ])->andReturn($this->mockedSendMessageResponseModel);

        $id = $this->sqsQueue->later($now->addSeconds(5), $this->mockedJob, $this->mockedData, self::QUEUE_NAME);
        $this->assertEquals($this->mockedMessageId, $id);
    }

    public function testDelayedPushProperlyPushesJobOntoSqs()
    {
        $this->mockedSqsClient->shouldReceive('sendMessage')->once()->with([
            'QueueUrl'     => self::URL,
            'MessageBody'  => $this->mockedPayload,
            'DelaySeconds' => $this->mockedDelay,
        ])->andReturn($this->mockedSendMessageResponseModel);

        $id = $this->sqsQueue->later($this->mockedDelay, $this->mockedJob, $this->mockedData, self::QUEUE_NAME);
        $this->assertEquals($this->mockedMessageId, $id);
    }

    public function testPushProperlyPushesJobOntoSqs()
    {
        $this->mockedSqsClient->shouldReceive('sendMessage')->once()->with([
            'QueueUrl'    => self::URL,
            'MessageBody' => $this->mockedPayload,
        ])->andReturn($this->mockedSendMessageResponseModel);

        $id = $this->sqsQueue->push($this->mockedJob, $this->mockedData, self::QUEUE_NAME);
        $this->assertEquals($this->mockedMessageId, $id);
    }

    public function testSizeProperlyReadsSqsQueueSize()
    {
        $this->mockedSqsClient->shouldReceive('getQueueAttributes')->once()->with([
            'QueueUrl'       => self::URL,
            'AttributeNames' => ['ApproximateNumberOfMessages'],
        ])->andReturn($this->mockedQueueAttributesResponseModel);

        $size = $this->sqsQueue->size(self::QUEUE_NAME);
        $this->assertEquals($size, 1);
    }
}
