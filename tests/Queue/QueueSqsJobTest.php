<?php

namespace Illuminate\Tests\Queue;

use Aws\Sqs\SqsClient;
use Illuminate\Container\Container;
use Illuminate\Queue\Jobs\SqsJob;
use Illuminate\Queue\SqsQueue;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use stdClass;

class QueueSqsJobTest extends TestCase
{
    protected $key;
    protected $secret;
    protected $service;
    protected $region;
    protected $account;
    protected $queueName;
    protected $baseUrl;
    protected $releaseDelay;
    protected $queueUrl;
    protected $mockedSqsClient;
    protected $mockedContainer;
    protected $mockedJob;
    protected $mockedData;
    protected $mockedPayload;
    protected $mockedMessageId;
    protected $mockedReceiptHandle;
    protected $mockedJobData;

    protected function setUp(): void
    {
        $this->key = 'AMAZONSQSKEY';
        $this->secret = 'AmAz0n+SqSsEcReT+aLpHaNuM3R1CsTr1nG';
        $this->service = 'sqs';
        $this->region = 'someregion';
        $this->account = '1234567891011';
        $this->queueName = 'emails';
        $this->baseUrl = 'https://sqs.someregion.amazonaws.com';
        $this->releaseDelay = 0;

        // This is how the modified getQueue builds the queueUrl
        $this->queueUrl = $this->baseUrl.'/'.$this->account.'/'.$this->queueName;

        // Get a mock of the SqsClient
        $this->mockedSqsClient = m::mock(SqsClient::class)->makePartial();

        // Use Mockery to mock the IoC Container
        $this->mockedContainer = m::mock(Container::class);

        $this->mockedJob = 'foo';
        $this->mockedData = ['data'];
        $this->mockedPayload = json_encode(['job' => $this->mockedJob, 'data' => $this->mockedData, 'attempts' => 1]);
        $this->mockedMessageId = 'e3cd03ee-59a3-4ad8-b0aa-ee2e3808ac81';
        $this->mockedReceiptHandle = '0NNAq8PwvXuWv5gMtS9DJ8qEdyiUwbAjpp45w2m6M4SJ1Y+PxCh7R930NRB8ylSacEmoSnW18bgd4nK\/O6ctE+VFVul4eD23mA07vVoSnPI4F\/voI1eNCp6Iax0ktGmhlNVzBwaZHEr91BRtqTRM3QKd2ASF8u+IQaSwyl\/DGK+P1+dqUOodvOVtExJwdyDLy1glZVgm85Yw9Jf5yZEEErqRwzYz\/qSigdvW4sm2l7e4phRol\/+IjMtovOyH\/ukueYdlVbQ4OshQLENhUKe7RNN5i6bE\/e5x9bnPhfj2gbM';

        $this->mockedJobData = [
            'Body' => $this->mockedPayload,
            'MD5OfBody' => md5($this->mockedPayload),
            'ReceiptHandle' => $this->mockedReceiptHandle,
            'MessageId' => $this->mockedMessageId,
            'Attributes' => ['ApproximateReceiveCount' => 1],
        ];
    }

    public function testFireProperlyCallsTheJobHandler()
    {
        $job = $this->getJob();
        $job->getContainer()->shouldReceive('make')->once()->with('foo')->andReturn($handler = m::mock(stdClass::class));
        $handler->shouldReceive('fire')->once()->with($job, ['data']);
        $job->fire();
    }

    public function testDeleteRemovesTheJobFromSqs()
    {
        $this->mockedSqsClient = m::mock(SqsClient::class)->makePartial();
        $queue = m::mock(SqsQueue::class, [$this->mockedSqsClient, $this->queueName, $this->account])->makePartial();
        $queue->setContainer($this->mockedContainer);
        $job = $this->getJob();
        $job->getSqs()->shouldReceive('deleteMessage')->once()->with(['QueueUrl' => $this->queueUrl, 'ReceiptHandle' => $this->mockedReceiptHandle]);
        $job->delete();
    }

    public function testDeleteRemovesNonFailedJobFromSqsWhenRedriveExists()
    {
        $redrivePolicy = ['maxReceiveCount' => 5, 'deadLetterTargetArn' => 'arn:aws:sqs:us-east-1:123456789:dlq'];
        $job = $this->getJobWithRedrivePolicy($redrivePolicy);
        $job->getSqs()->shouldReceive('deleteMessage')->once()->with(['QueueUrl' => $this->queueUrl, 'ReceiptHandle' => $this->mockedReceiptHandle]);
        $job->delete();
    }

    public function testDeleteSkipsSqsDeletionWhenFailedJobHasRedrivePolicy()
    {
        $redrivePolicy = ['maxReceiveCount' => 5, 'deadLetterTargetArn' => 'arn:aws:sqs:us-east-1:123456789:dlq'];
        $job = $this->getJobWithRedrivePolicy($redrivePolicy);
        $job->markAsFailed();
        $job->getSqs()->shouldNotReceive('deleteMessage');
        $job->delete();
    }

    public function testDeleteRemovesFailedJobFromSqsWhenNoRedrivePolicy()
    {
        $job = $this->getJob();
        $job->markAsFailed();
        $job->getSqs()->shouldReceive('deleteMessage')->once()->with(['QueueUrl' => $this->queueUrl, 'ReceiptHandle' => $this->mockedReceiptHandle]);
        $job->delete();
    }

    public function testMaxTriesReturnsRedriveMaxReceiveCount()
    {
        $redrivePolicy = ['maxReceiveCount' => 3, 'deadLetterTargetArn' => 'arn:aws:sqs:us-east-1:123456789:dlq'];
        $job = $this->getJobWithRedrivePolicy($redrivePolicy);
        $this->assertEquals(3, $job->maxTries());
    }

    public function testMaxTriesFallsBackToPayloadWithoutRedrivePolicy()
    {
        $job = $this->getJob();
        $this->assertNull($job->maxTries());
    }

    public function testReleaseProperlyReleasesTheJobOntoSqs()
    {
        $this->mockedSqsClient = m::mock(SqsClient::class)->makePartial();
        $queue = m::mock(SqsQueue::class, [$this->mockedSqsClient, $this->queueName, $this->account])->makePartial();
        $queue->setContainer($this->mockedContainer);
        $job = $this->getJob();
        $job->getSqs()->shouldReceive('changeMessageVisibility')->once()->with(['QueueUrl' => $this->queueUrl, 'ReceiptHandle' => $this->mockedReceiptHandle, 'VisibilityTimeout' => $this->releaseDelay]);
        $job->release($this->releaseDelay);
        $this->assertTrue($job->isReleased());
    }

    protected function getJob()
    {
        return new SqsJob(
            $this->mockedContainer,
            $this->mockedSqsClient,
            $this->mockedJobData,
            'connection-name',
            $this->queueUrl
        );
    }

    protected function getJobWithRedrivePolicy(array $redrivePolicy)
    {
        return new SqsJob(
            $this->mockedContainer,
            $this->mockedSqsClient,
            $this->mockedJobData,
            'connection-name',
            $this->queueUrl,
            $redrivePolicy
        );
    }
}
