<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Bus\Dispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\CallQueuedHandler;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Jobs\FakeJob;
use Illuminate\Queue\Middleware\SkipOnException;
use InvalidArgumentException;
use LogicException;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Throwable;

class SkipOnExceptionMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        SkipOnExceptionMiddlewareTestJob::$_middleware = [];
    }

    /**
     * @return array<string, array{class-string<\Throwable>, SkipOnException, bool}>
     */
    public static function testMiddlewareDataProvider(): array
    {
        return [
            'exception is in list' => [
                InvalidArgumentException::class,
                new SkipOnException([InvalidArgumentException::class]),
                true,
            ],
            'exception is not in list' => [
                LogicException::class,
                new SkipOnException([InvalidArgumentException::class]),
                false,
            ],
        ];
    }

    #[DataProvider('testMiddlewareDataProvider')]
    public function test_middleware(
        string $thrown,
        SkipOnException $middleware,
        bool $expectedToBeSkipped
    ): void {
        SkipOnExceptionMiddlewareTestJob::$_middleware = [$middleware];
        $job = new SkipOnExceptionMiddlewareTestJob($thrown);
        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $fakeJob = new FakeJob();
        $job->setJob($fakeJob);

        if ($expectedToBeSkipped) {
            // When exception should be skipped, no exception should be thrown
            $result = $instance->call($fakeJob, [
                'command' => serialize($job),
            ]);
            
            $this->assertNull($result);
            $job->assertNotFailed(); // Job should not be marked as failed
        } else {
            // When exception should not be skipped, it should be thrown
            $this->expectException($thrown);
            
            $instance->call($fakeJob, [
                'command' => serialize($job),
            ]);
        }
    }

    #[TestWith(['abc', true])]
    #[TestWith(['tots', false])]
    public function test_can_test_against_job_properties($value, bool $expectedToBeSkipped): void
    {
        SkipOnExceptionMiddlewareTestJob::$_middleware = [
            new SkipOnException(fn ($thrown, $job) => $job->value === 'abc'),
        ];

        $job = new SkipOnExceptionMiddlewareTestJob(InvalidArgumentException::class, $value);
        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $fakeJob = new FakeJob();
        $job->setJob($fakeJob);

        if ($expectedToBeSkipped) {
            // When exception should be skipped, no exception should be thrown
            $result = $instance->call($fakeJob, [
                'command' => serialize($job),
            ]);
            
            $this->assertNull($result);
            $job->assertNotFailed();
        } else {
            // When exception should not be skipped, it should be thrown
            $this->expectException(InvalidArgumentException::class);
            
            $instance->call($fakeJob, [
                'command' => serialize($job),
            ]);
        }
    }

    public function test_closure_callback_receives_exception_and_job(): void
    {
        $receivedException = null;
        $receivedJob = null;

        SkipOnExceptionMiddlewareTestJob::$_middleware = [
            new SkipOnException(function ($exception, $job) use (&$receivedException, &$receivedJob) {
                $receivedException = $exception;
                $receivedJob = $job;
                return true; // Skip the exception
            }),
        ];

        $job = new SkipOnExceptionMiddlewareTestJob(InvalidArgumentException::class, 'test-value');
        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $fakeJob = new FakeJob();
        $job->setJob($fakeJob);

        $result = $instance->call($fakeJob, [
            'command' => serialize($job),
        ]);

        $this->assertNull($result);
        $this->assertInstanceOf(InvalidArgumentException::class, $receivedException);
        $this->assertInstanceOf(SkipOnExceptionMiddlewareTestJob::class, $receivedJob);
        $this->assertNotNull($receivedJob);
        if ($receivedJob instanceof SkipOnExceptionMiddlewareTestJob) {
            $this->assertEquals('test-value', $receivedJob->value);
        }
    }

    public function test_multiple_exception_types_can_be_skipped(): void
    {
        SkipOnExceptionMiddlewareTestJob::$_middleware = [
            new SkipOnException([
                InvalidArgumentException::class,
                LogicException::class,
            ]),
        ];

        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        // Test first exception type
        $job1 = new SkipOnExceptionMiddlewareTestJob(InvalidArgumentException::class);
        $fakeJob1 = new FakeJob();
        $job1->setJob($fakeJob1);

        $result1 = $instance->call($fakeJob1, [
            'command' => serialize($job1),
        ]);

        $this->assertNull($result1);
        $job1->assertNotFailed();

        // Test second exception type
        $job2 = new SkipOnExceptionMiddlewareTestJob(LogicException::class);
        $fakeJob2 = new FakeJob();
        $job2->setJob($fakeJob2);

        $result2 = $instance->call($fakeJob2, [
            'command' => serialize($job2),
        ]);

        $this->assertNull($result2);
        $job2->assertNotFailed();
    }
}

class SkipOnExceptionMiddlewareTestJob implements ShouldQueue
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