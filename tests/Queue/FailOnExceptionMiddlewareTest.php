<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Bus\Dispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\CallQueuedHandler;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Jobs\FakeJob;
use Illuminate\Queue\Middleware\FailOnException;
use InvalidArgumentException;
use LogicException;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Throwable;

class FailOnExceptionMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        FailOnExceptionMiddlewareTestJob::$_middleware = [];
    }

    /**
     * @return array<string, array{class-string<\Throwable>, FailOnException, bool}>
     */
    public static function middlewareDataProvider(): array
    {
        return [
            'exception is in list' => [
                InvalidArgumentException::class,
                new FailOnException([InvalidArgumentException::class]),
                true,
            ],
            'exception is not in list' => [
                LogicException::class,
                new FailOnException([InvalidArgumentException::class]),
                false,
            ],
        ];
    }

    #[DataProvider('middlewareDataProvider')]
    public function test_middleware(
        string $thrown,
        FailOnException $middleware,
        bool $expectedToFail
    ): void {
        FailOnExceptionMiddlewareTestJob::$_middleware = [$middleware];
        $job = new FailOnExceptionMiddlewareTestJob($thrown);
        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $fakeJob = new FakeJob();
        $job->setJob($fakeJob);

        try {
            $instance->call($fakeJob, [
                'command' => serialize($job),
            ]);
            $this->fail('Did not throw exception');
        } catch (Throwable $e) {
            $this->assertInstanceOf($thrown, $e);
        }

        $expectedToFail ? $job->assertFailed() : $job->assertNotFailed();
    }

    #[TestWith(['abc', true])]
    #[TestWith(['tots', false])]
    public function test_can_test_against_job_properties($value, bool $expectedToFail): void
    {
        FailOnExceptionMiddlewareTestJob::$_middleware = [
            new FailOnException(fn ($thrown, $job) => $job->value === 'abc'),
        ];

        $job = new FailOnExceptionMiddlewareTestJob(InvalidArgumentException::class, $value);
        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $fakeJob = new FakeJob();
        $job->setJob($fakeJob);

        try {
            $instance->call($fakeJob, [
                'command' => serialize($job),
            ]);
            $this->fail('Did not throw exception');
        } catch (Throwable) {
            //
        }

        $expectedToFail ? $job->assertFailed() : $job->assertNotFailed();
    }
}

class FailOnExceptionMiddlewareTestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public static array $_middleware = [];

    public int $tries = 2;

    public function __construct(private $throws, public $value = null)
    {
    }

    public function handle()
    {
        throw new $this->throws;
    }

    public function middleware(): array
    {
        return self::$_middleware;
    }
}
