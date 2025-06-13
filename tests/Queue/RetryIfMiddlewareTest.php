<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Bus\Dispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\CallQueuedHandler;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Jobs\FakeJob;
use Illuminate\Queue\Middleware\RetryIf;
use InvalidArgumentException;
use LogicException;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;

class RetryIfMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        RetryIfMiddlewareJob::$_middleware = [];
    }

    /**
     * @return array<string, array{class-string<\Throwable>, RetryIf}>
     */
    public static function expectedToFailDataProvider(): array
    {
        return [
            'failureIsNot and exception is in list' => [
                InvalidArgumentException::class,
                RetryIf::failureIsNot(InvalidArgumentException::class),
                true,
            ],
            'failureIs and exception is not in list' => [
                LogicException::class,
                RetryIf::failureIs(InvalidArgumentException::class),
                true,
            ],
            'failureIsNot and exception not in list' => [
                LogicException::class,
                RetryIf::failureIsNot(InvalidArgumentException::class),
                false,
            ],
            'failureIs and exception is in list' => [
                InvalidArgumentException::class,
                RetryIf::failureIs(InvalidArgumentException::class),
                false,
            ],
        ];
    }

    #[DataProvider('expectedToFailDataProvider')]
    public function test_middleware(
        string $thrown,
        RetryIf $middleware,
        bool $expectedToFail
    ): void {
        RetryIfMiddlewareJob::$_middleware = [$middleware];
        $job = new RetryIfMiddlewareJob($thrown);
        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $fakeJob = new FakeJob();
        $job->setJob($fakeJob);

        try {
            $instance->call($fakeJob, [
                'command' => serialize($job),
            ]);
            $this->fail('Did not throw exception');
        } catch (\Throwable $e) {
            $this->assertInstanceOf($thrown, $e);
        }

        $expectedToFail ? $job->assertFailed() : $job->assertNotFailed();
    }

    #[TestWith(['abc', false])]
    #[TestWith(['tots', true])]
    public function test_can_test_against_job($value, bool $expectedToFail): void
    {
        RetryIfMiddlewareJob::$_middleware = [new RetryIf(fn ($thrown, $job) => $job->value === 'abc')];

        $job = new RetryIfMiddlewareJob(InvalidArgumentException::class, $value);
        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $fakeJob = new FakeJob();
        $job->setJob($fakeJob);

        try {
            $instance->call($fakeJob, [
                'command' => serialize($job),
            ]);
            $this->fail('Did not throw exception');
        } catch (\Throwable $e) {
            //
        }

        $expectedToFail ? $job->assertFailed() : $job->assertNotFailed();

    }
}

class RetryIfMiddlewareJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use Dispatchable;

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
