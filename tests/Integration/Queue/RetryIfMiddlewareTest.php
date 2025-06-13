<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RetryIf;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use Orchestra\Testbench\Attributes\WithConfig;
use PHPUnit\Framework\Attributes\DataProvider;

#[WithConfig('queue.default', 'database')]
class RetryIfMiddlewareTest extends QueueTestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);
        $this->driver = 'database';
    }

    public static function markFailedForRetryIfDataProvider(): array
    {
        return [
            'middleware fails on thrown exception' => [
                InvalidArgumentException::class,
                1,
                1,
            ],
            'middleware retries if exception does not match' => [
                \LogicException::class,
                2,
                1,
            ],
        ];
    }

    #[DataProvider('markFailedForRetryIfDataProvider')]
    public function test_retry_if_middleware(
        $throws,
        int $expectedExceptions,
        int $expectedFails
    ) {
        RetryIfMiddlewareJob::dispatch($throws)->onQueue('default')->onConnection('database');

        $failsCalled = $exceptionsOccurred = 0;
        Queue::exceptionOccurred(function () use (&$exceptionsOccurred) {
            $exceptionsOccurred++;
        });
        Queue::failing(function () use (&$failsCalled) {
            $failsCalled++;
        });

        $this->runQueueWorkerCommand(['--stop-when-empty' => true, '--sleep' => 1], 2);

        $this->assertEquals($expectedExceptions, $exceptionsOccurred);
        $this->assertEquals($expectedFails, $failsCalled);
    }
}

class RetryIfMiddlewareJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use Dispatchable;

    public int $tries = 2;

    public function __construct(private $throws)
    {
    }

    public function handle()
    {
        if ($this->throws === null) {
            return; // success
        }

        throw new ($this->throws);
    }

    public function middleware(): array
    {
        return [RetryIf::failForExceptions(InvalidArgumentException::class)];
    }
}
