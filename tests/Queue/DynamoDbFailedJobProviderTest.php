<?php

namespace Illuminate\Tests\Queue;

use Aws\DynamoDb\DynamoDbClient;
use Aws\MockHandler;
use Aws\Result;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Exception;
use Illuminate\Queue\Failed\DynamoDbFailedJobProvider;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use PHPUnit\Framework\TestCase;

class DynamoDbFailedJobProviderTest extends TestCase
{
    public function testCanProperlyLogFailedJob()
    {
        $uuid = Str::orderedUuid();

        Str::createUuidsUsing(function () use ($uuid) {
            return $uuid;
        });

        Carbon::setTestNow($now = CarbonImmutable::now());

        $exception = new Exception('Something went wrong.');

        $handler = new MockHandler;
        $dynamoDbClient = $this->dynamoDbClient($handler);

        $handler->append(new Result([]));
        $expectedParams = [
            'TableName' => 'table',
            'Item' => [
                'application' => ['S' => 'application'],
                'uuid' => ['S' => (string) $uuid],
                'connection' => ['S' => 'connection'],
                'queue' => ['S' => 'queue'],
                'payload' => ['S' => json_encode(['uuid' => (string) $uuid])],
                'exception' => ['S' => (string) $exception],
                'failed_at' => ['N' => (string) $now->getTimestamp()],
                'expires_at' => ['N' => (string) $now->addWeek()->getTimestamp()],
            ],
        ];

        $provider = new DynamoDbFailedJobProvider($dynamoDbClient, 'application', 'table');

        $provider->log('connection', 'queue', json_encode(['uuid' => (string) $uuid]), $exception);

        $this->assertEquals($expectedParams, $this->lastCommandParameters($handler));

        Str::createUuidsNormally();
    }

    public function testCanRetrieveAllFailedJobs()
    {
        $handler = new MockHandler;
        $dynamoDbClient = $this->dynamoDbClient($handler);

        $time = time();

        $handler->append(new Result([
            'Items' => [
                [
                    'application' => ['S' => 'application'],
                    'uuid' => ['S' => 'uuid'],
                    'connection' => ['S' => 'connection'],
                    'queue' => ['S' => 'queue'],
                    'payload' => ['S' => 'payload'],
                    'exception' => ['S' => 'exception'],
                    'failed_at' => ['N' => (string) $time],
                    'expires_at' => ['N' => (string) $time],
                ],
            ],
        ]));
        $expectedParams = [
            'TableName' => 'table',
            'Select' => 'ALL_ATTRIBUTES',
            'KeyConditionExpression' => 'application = :application',
            'ExpressionAttributeValues' => [
                ':application' => ['S' => 'application'],
            ],
            'ScanIndexForward' => false,
        ];

        $provider = new DynamoDbFailedJobProvider($dynamoDbClient, 'application', 'table');

        $response = $provider->all();

        $this->assertEquals([
            (object) [
                'id' => 'uuid',
                'connection' => 'connection',
                'queue' => 'queue',
                'payload' => 'payload',
                'exception' => 'exception',
                'failed_at' => Carbon::createFromTimestamp($time)->format(DateTimeInterface::ISO8601),
            ],
        ], $response);

        $this->assertEquals($expectedParams, $this->lastCommandParameters($handler));
    }

    public function testASingleJobCanBeFound()
    {
        $handler = new MockHandler;
        $dynamoDbClient = $this->dynamoDbClient($handler);

        $time = time();

        $handler->append(new Result([
            'Item' => [
                'application' => ['S' => 'application'],
                'uuid' => ['S' => 'uuid'],
                'connection' => ['S' => 'connection'],
                'queue' => ['S' => 'queue'],
                'payload' => ['S' => 'payload'],
                'exception' => ['S' => 'exception'],
                'failed_at' => ['N' => (string) $time],
                'expires_at' => ['N' => (string) $time],
            ],
        ]));
        $expectedParams = [
            'TableName' => 'table',
            'Key' => [
                'application' => ['S' => 'application'],
                'uuid' => ['S' => 'id'],
            ],
        ];

        $provider = new DynamoDbFailedJobProvider($dynamoDbClient, 'application', 'table');

        $response = $provider->find('id');

        $this->assertEquals(
            (object) [
                'id' => 'uuid',
                'connection' => 'connection',
                'queue' => 'queue',
                'payload' => 'payload',
                'exception' => 'exception',
                'failed_at' => Carbon::createFromTimestamp($time)->format(DateTimeInterface::ISO8601),
            ], $response
        );

        $this->assertEquals($expectedParams, $this->lastCommandParameters($handler));
    }

    public function testNullIsReturnedIfJobNotFound()
    {
        $handler = new MockHandler;
        $dynamoDbClient = $this->dynamoDbClient($handler);

        $handler->append(new Result([]));
        $expectedParams = [
            'TableName' => 'table',
            'Key' => [
                'application' => ['S' => 'application'],
                'uuid' => ['S' => 'id'],
            ],
        ];

        $provider = new DynamoDbFailedJobProvider($dynamoDbClient, 'application', 'table');

        $response = $provider->find('id');

        $this->assertNull($response);

        $this->assertEquals($expectedParams, $this->lastCommandParameters($handler));
    }

    public function testJobsCanBeDeleted()
    {
        $handler = new MockHandler;
        $dynamoDbClient = $this->dynamoDbClient($handler);

        $handler->append(new Result([]));
        $expectedParams = [
            'TableName' => 'table',
            'Key' => [
                'application' => ['S' => 'application'],
                'uuid' => ['S' => 'id'],
            ],
        ];

        $provider = new DynamoDbFailedJobProvider($dynamoDbClient, 'application', 'table');

        $provider->forget('id');

        $this->assertEquals($expectedParams, $this->lastCommandParameters($handler));
    }

    protected function dynamoDbClient(MockHandler $handler): DynamoDbClient
    {
        return new DynamoDbClient([
            'region' => 'us-east-1',
            'version' => 'latest',
            'credentials' => ['key' => 'foo', 'secret' => 'bar'],
            'handler' => $handler,
        ]);
    }

    protected function lastCommandParameters(MockHandler $handler): array
    {
        return array_filter(
            $handler->getLastCommand()->toArray(),
            fn ($key) => ! str_starts_with($key, '@'),
            ARRAY_FILTER_USE_KEY
        );
    }
}
