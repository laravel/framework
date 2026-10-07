<?php

namespace Illuminate\Tests\Queue;

use Aws\MockHandler;
use Aws\Result;
use Aws\Sqs\SqsClient;
use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Queue\Jobs\SqsJob;
use JMac\Testing\Double;
use Mockery;
use PHPUnit\Framework\TestCase;

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
    protected $handler;
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

        // A real SqsClient that never leaves the process
        $this->handler = new MockHandler;
        $this->mockedSqsClient = new SqsClient([
            'region' => $this->region,
            'version' => 'latest',
            'credentials' => ['key' => $this->key, 'secret' => $this->secret],
            'handler' => $this->handler,
        ]);

        // Use Mockery to mock the IoC Container
        $this->mockedContainer = Double::for(Container::class, override: true);

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
        $handler = new SqsJobTestHandler;
        $this->mockedContainer->expects('make')->with('foo')->returns($handler);
        $job->fire();

        $this->assertSame([[$job, ['data']]], $handler->fired);
    }

    public function testDeleteRemovesTheJobFromSqs()
    {
        $this->handler->append(new Result([]));
        $job = $this->getJob();
        $job->delete();

        $command = $this->handler->getLastCommand();
        $this->assertSame('DeleteMessage', $command->getName());
        $this->assertSame($this->queueUrl, $command['QueueUrl']);
        $this->assertSame($this->mockedReceiptHandle, $command['ReceiptHandle']);
    }

    public function testReleaseProperlyReleasesTheJobOntoSqs()
    {
        $this->handler->append(new Result([]));
        $job = $this->getJob();
        $job->release($this->releaseDelay);
        $this->assertTrue($job->isReleased());

        $command = $this->handler->getLastCommand();
        $this->assertSame('ChangeMessageVisibility', $command->getName());
        $this->assertSame($this->queueUrl, $command['QueueUrl']);
        $this->assertSame($this->mockedReceiptHandle, $command['ReceiptHandle']);
        $this->assertSame($this->releaseDelay, $command['VisibilityTimeout']);
    }

    public function testGetRawBodyResolvesPointerFromCache()
    {
        $fullPayload = json_encode(['job' => 'foo', 'data' => ['key' => 'value']]);
        $pointerPath = 'laravel:sqs-payloads:some-uuid';
        $pointerBody = json_encode(['@pointer' => $pointerPath]);

        $store = Double::for(CacheRepository::class);
        $store->expects('get')->with($pointerPath)->returns($fullPayload);

        $cache = Double::for(CacheFactory::class);
        $cache->expects('store')->with('database')->returns($store);

        $container = Double::for(Container::class, override: true);
        $container->expects('make')->with('cache')->returns($cache);

        $jobData = $this->mockedJobData;
        $jobData['Body'] = $pointerBody;

        $job = new SqsJob($container->instance(), $this->mockedSqsClient, $jobData, 'connection-name', $this->queueUrl, [
            'enabled' => true,
            'store' => 'database',
            'delete_after_processing' => true,
        ]);

        $this->assertEquals($fullPayload, $job->getRawBody());
    }

    public function testGetRawBodyReturnsNormalBodyWithoutPointer()
    {
        $job = $this->getJob();
        $this->assertEquals($this->mockedPayload, $job->getRawBody());
    }

    public function testGetRawBodyReturnsPointerBodyWhenExtendedStoreIsDisabled()
    {
        $pointerBody = json_encode(['@pointer' => 'laravel:sqs-payloads:some-uuid']);

        $jobData = $this->mockedJobData;
        $jobData['Body'] = $pointerBody;

        $job = new SqsJob($this->mockedContainer->instance(), $this->mockedSqsClient, $jobData, 'connection-name', $this->queueUrl);

        $this->assertEquals($pointerBody, $job->getRawBody());
    }

    public function testGetRawBodyCachesResult()
    {
        $fullPayload = json_encode(['job' => 'foo', 'data' => ['key' => 'value']]);
        $pointerPath = 'laravel:sqs-payloads:some-uuid';
        $pointerBody = json_encode(['@pointer' => $pointerPath]);

        $store = Double::for(CacheRepository::class);
        $store->expects('get')->with($pointerPath)->returns($fullPayload);

        $cache = Double::for(CacheFactory::class);
        $cache->expects('store')->with('database')->returns($store);

        $container = Double::for(Container::class, override: true);
        $container->expects('make')->with('cache')->returns($cache);

        $jobData = $this->mockedJobData;
        $jobData['Body'] = $pointerBody;

        $job = new SqsJob($container->instance(), $this->mockedSqsClient, $jobData, 'connection-name', $this->queueUrl, [
            'enabled' => true,
            'store' => 'database',
            'delete_after_processing' => true,
        ]);

        // Call twice; cache should only be hit once.
        $job->getRawBody();
        $this->assertEquals($fullPayload, $job->getRawBody());
    }

    public function testDeleteCleansUpCacheKeyWhenCleanupEnabled()
    {
        $pointerPath = 'laravel:sqs-payloads:some-uuid';
        $pointerBody = json_encode(['@pointer' => $pointerPath]);

        $store = Double::for(CacheRepository::class);
        $store->expects('forget')->with($pointerPath);

        $cache = Double::for(CacheFactory::class);
        $cache->expects('store')->with('database')->returns($store);

        $container = Double::for(Container::class, override: true);
        $container->expects('make')->with('cache')->returns($cache);

        $jobData = $this->mockedJobData;
        $jobData['Body'] = $pointerBody;

        $this->handler->append(new Result([]));
        $sqsClient = $this->mockedSqsClient;

        $job = new SqsJob($container->instance(), $sqsClient, $jobData, 'connection-name', $this->queueUrl, [
            'enabled' => true,
            'store' => 'database',
            'delete_after_processing' => true,
        ]);

        $job->delete();
    }

    public function testDeleteDoesNotCleanUpWhenCleanupDisabled()
    {
        $pointerPath = 'laravel:sqs-payloads:some-uuid';
        $pointerBody = json_encode(['@pointer' => $pointerPath]);

        $jobData = $this->mockedJobData;
        $jobData['Body'] = $pointerBody;

        $this->handler->append(new Result([]));
        $sqsClient = $this->mockedSqsClient;

        $job = new SqsJob($this->mockedContainer->instance(), $sqsClient, $jobData, 'connection-name', $this->queueUrl, [
            'enabled' => true,
            'store' => 'database',
            'delete_after_processing' => false,
        ]);

        $job->delete();
    }

    public function testDeleteDoesNotCleanUpWhenNoPointer()
    {
        $this->handler->append(new Result([]));
        $sqsClient = $this->mockedSqsClient;

        $job = new SqsJob($this->mockedContainer->instance(), $sqsClient, $this->mockedJobData, 'connection-name', $this->queueUrl, [
            'enabled' => true,
            'store' => 'database',
            'delete_after_processing' => true,
        ]);

        $job->delete();
    }

    protected function getJob()
    {
        return new SqsJob(
            $this->mockedContainer->instance(),
            $this->mockedSqsClient,
            $this->mockedJobData,
            'connection-name',
            $this->queueUrl
        );
    }
}

class SqsJobTestHandler
{
    public array $fired = [];

    public function fire($job, array $data)
    {
        $this->fired[] = [$job, $data];
    }
}
