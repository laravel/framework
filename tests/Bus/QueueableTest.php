<?php

namespace Illuminate\Tests\Bus;

use Illuminate\Bus\Queueable;
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
