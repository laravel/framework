<?php

namespace Illuminate\Tests\Bus;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Container\Container;
use Illuminate\Queue\SyncQueue;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class QueueableTest extends TestCase
{
    public static function connectionDataProvider(): array
    {
        return [
            'uses string' => ['redis', 'redis'],
            'uses BackedEnum #1' => [ConnectionEnum::SQS, 'sqs'],
            'uses BackedEnum #2' => [ConnectionEnum::REDIS, 'redis'],
            'uses null' => [null, null],
        ];
    }

    #[DataProvider('connectionDataProvider')]
    public function testOnConnection(mixed $connection, ?string $expected): void
    {
        $job = new FakeJob();
        $job->onConnection($connection);

        $this->assertSame($job->connection, $expected);
    }

    #[DataProvider('connectionDataProvider')]
    public function testAllOnConnection(mixed $connection, ?string $expected): void
    {
        $job = new FakeJob();
        $job->allOnConnection($connection);

        $this->assertSame($job->connection, $expected);
        $this->assertSame($job->chainConnection, $expected);
    }

    public static function queuesDataProvider(): array
    {
        return [
            'uses string' => ['high', 'high'],
            'uses BackedEnum #1' => [QueueEnum::DEFAULT, 'default'],
            'uses BackedEnum #2' => [QueueEnum::HIGH, 'high'],
            'uses null' => [null, null],
        ];
    }

    #[DataProvider('queuesDataProvider')]
    public function testOnQueue(mixed $queue, ?string $expected): void
    {
        $job = new FakeJob();
        $job->onQueue($queue);

        $this->assertSame($job->queue, $expected);
    }

    #[DataProvider('queuesDataProvider')]
    public function testAllOnQueue(mixed $queue, ?string $expected): void
    {
        $job = new FakeJob();
        $job->allOnQueue($queue);

        $this->assertSame($job->queue, $expected);
        $this->assertSame($job->chainQueue, $expected);
    }

    public static function retryWithDelayDataProvider(): array
    {
        $datetime = Carbon::now()->addHour();
        $interval = new \DateInterval('PT1H');

        return [
            'integer' => [60, 60],
            'array of integers' => [[10, 30, 60, 120], [10, 30, 60, 120]],
            'DateTimeInterface' => [$datetime, $datetime],
            'DateInterval' => [$interval, $interval],
        ];
    }

    #[DataProvider('retryWithDelayDataProvider')]
    public function testRetryWithDelay(mixed $delays, mixed $expected): void
    {
        $job = new FakeJob();
        $result = $job->retryWithDelay($delays);

        if ($expected instanceof \DateTimeInterface && $job->backoff instanceof \DateTimeInterface) {
            $this->assertEquals($expected->getTimestamp(), $job->backoff->getTimestamp());
        } elseif ($expected instanceof \DateInterval && $job->backoff instanceof \DateInterval) {
            $this->assertEquals($expected->format('%R%Y%M%D%H%I%S'), $job->backoff->format('%R%Y%M%D%H%I%S'));
        } else {
            $this->assertSame($job->backoff, $expected);
        }
        $this->assertSame($result, $job);
    }

    public static function retryUntilDataProvider(): array
    {
        $datetime = Carbon::now()->addDay();
        $timestamp = $datetime->getTimestamp();

        return [
            'DateTimeInterface' => [$datetime, $datetime],
            'timestamp' => [$timestamp, $timestamp],
        ];
    }

    #[DataProvider('retryUntilDataProvider')]
    public function testRetryUntil(mixed $datetime, mixed $expected): void
    {
        $job = new FakeJob();
        $result = $job->retryUntil($datetime);

        $this->assertSame($job->retryUntil, $expected);
        $this->assertSame($result, $job);
    }

    public function testRetryWithDelayReturnsSelfForFluentInterface(): void
    {
        $job = new FakeJob();
        $result = $job->retryWithDelay(60);

        $this->assertSame($job, $result);
    }

    public function testRetryUntilReturnsSelfForFluentInterface(): void
    {
        $job = new FakeJob();
        $result = $job->retryUntil(Carbon::now());

        $this->assertSame($job, $result);
    }

    public function testRetryWithDelayAndRetryUntilCanBeChained(): void
    {
        $job = new FakeJob();
        $datetime = Carbon::now()->addDay();
        $delays = [10, 30, 60];

        $result = $job->retryWithDelay($delays)->retryUntil($datetime);

        $this->assertSame($job->backoff, $delays);
        $this->assertSame($job->retryUntil, $datetime);
        $this->assertSame($result, $job);
    }

    public function testRetryWithDelayCanBeChainedWithOtherMethods(): void
    {
        $job = new FakeJob();

        $result = $job->retryWithDelay(60)
            ->onQueue('high')
            ->onConnection('redis');

        $this->assertSame($job->backoff, 60);
        $this->assertSame($job->queue, 'high');
        $this->assertSame($job->connection, 'redis');
        $this->assertSame($result, $job);
    }

    public function testRetryUntilCanBeChainedWithOtherMethods(): void
    {
        $job = new FakeJob();
        $datetime = Carbon::now()->addDay();

        $result = $job->retryUntil($datetime)
            ->onQueue('high')
            ->delay(30);

        $this->assertSame($job->retryUntil, $datetime);
        $this->assertSame($job->queue, 'high');
        $this->assertSame($job->delay, 30);
        $this->assertSame($result, $job);
    }

    public function testRetryWithDelayPropertyIsUsedInQueuePayload(): void
    {
        $job = new FakeJob();
        $job->retryWithDelay([10, 30, 60]);

        $queue = new SyncQueue(new Container);
        $queue->setContainer(new Container);

        $reflection = new \ReflectionClass($queue);
        $method = $reflection->getMethod('getJobBackoff');
        $method->setAccessible(true);

        $backoff = $method->invoke($queue, $job);

        $this->assertSame('10,30,60', $backoff);
    }

    public function testRetryWithDelayWithSingleIntegerIsUsedInQueuePayload(): void
    {
        $job = new FakeJob();
        $job->retryWithDelay(60);

        $queue = new SyncQueue(new Container);
        $queue->setContainer(new Container);

        $reflection = new \ReflectionClass($queue);
        $method = $reflection->getMethod('getJobBackoff');
        $method->setAccessible(true);

        $backoff = $method->invoke($queue, $job);

        $this->assertSame('60', $backoff);
    }

    public function testRetryUntilPropertyIsUsedInQueuePayload(): void
    {
        $job = new FakeJob();
        $datetime = Carbon::now()->addDay();
        $job->retryUntil($datetime);

        $queue = new SyncQueue(new Container);
        $queue->setContainer(new Container);

        $reflection = new \ReflectionClass($queue);
        $method = $reflection->getMethod('getJobExpiration');
        $method->setAccessible(true);

        $expiration = $method->invoke($queue, $job);

        $this->assertSame($datetime->getTimestamp(), $expiration);
    }

    public function testRetryUntilWithTimestampIsUsedInQueuePayload(): void
    {
        $job = new FakeJob();
        $timestamp = Carbon::now()->addDay()->getTimestamp();
        $job->retryUntil($timestamp);

        $queue = new SyncQueue(new Container);
        $queue->setContainer(new Container);

        $reflection = new \ReflectionClass($queue);
        $method = $reflection->getMethod('getJobExpiration');
        $method->setAccessible(true);

        $expiration = $method->invoke($queue, $job);

        $this->assertSame($timestamp, $expiration);
    }

    public function testRetryWithDelayCanBeOverwritten(): void
    {
        $job = new FakeJob();
        $job->retryWithDelay(60);
        $job->retryWithDelay([10, 30]);

        $this->assertSame($job->backoff, [10, 30]);
    }

    public function testRetryUntilCanBeOverwritten(): void
    {
        $job = new FakeJob();
        $firstDatetime = Carbon::now()->addDay();
        $secondDatetime = Carbon::now()->addDays(2);

        $job->retryUntil($firstDatetime);
        $job->retryUntil($secondDatetime);

        $this->assertSame($job->retryUntil, $secondDatetime);
    }
}

class FakeJob
{
    use Queueable;
}

enum ConnectionEnum: string
{
    case SQS = 'sqs';
    case REDIS = 'redis';
}

enum QueueEnum: string
{
    case HIGH = 'high';
    case DEFAULT = 'default';
}
