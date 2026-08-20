<?php

namespace Illuminate\Tests\Integration\Concurrency;

use Carbon\CarbonInterval;
use Closure;
use Exception;
use Illuminate\Concurrency\CapturedTaskException;
use Illuminate\Concurrency\InvokeQueuedClosure;
use Illuminate\Concurrency\QueueDriver;
use Illuminate\Concurrency\TaskResult;
use Illuminate\Concurrency\TaskTimedOutException;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Queue\CallQueuedClosure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use Laravel\SerializableClosure\SerializableClosure;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Throwable;

class QueueConcurrencyTest extends TestCase
{
    protected function defineEnvironment($app)
    {
        $app['config']->set('queue.default', 'sync');
        $app['config']->set('cache.default', 'array');
    }

    public function testRunReturnsResultsInOrder()
    {
        $results = Concurrency::driver('queue')->run([
            fn () => 1 + 1,
            fn () => 2 + 2,
        ]);

        $this->assertSame([2, 4], $results);
    }

    public function testRunPreservesStringKeys()
    {
        $results = Concurrency::driver('queue')->run([
            'first' => fn () => 1 + 1,
            'second' => fn () => 2 + 2,
        ]);

        $this->assertSame(['first' => 2, 'second' => 4], $results);
    }

    public function testRunWrapsSingleClosure()
    {
        $this->assertSame(['value'], Concurrency::driver('queue')->run(fn () => 'value'));
    }

    public function testRunReturnsEmptyArrayForNoTasks()
    {
        $this->assertSame([], Concurrency::driver('queue')->run([]));
    }

    public function testTaskExceptionIsRethrownInCaller()
    {
        $this->expectExceptionObject(new Exception('This is a different exception'));

        Concurrency::driver('queue')->run([
            fn () => throw new Exception('This is a different exception'),
        ]);
    }

    public function testTaskExceptionWithParametersIsReconstructed()
    {
        $this->expectException(QueueExceptionWithParam::class);
        $this->expectExceptionMessage('API request to https://api.example.com failed with status 400 Bad Request');

        Concurrency::driver('queue')->run([
            fn () => throw new QueueExceptionWithParam(
                'https://api.example.com', 400, 'Bad Request', 'Invalid payload',
            ),
        ]);
    }

    #[DataProvider('falseyExceptionParameters')]
    public function testTaskExceptionWithFalseyParametersIsReconstructed(int|bool|string $value)
    {
        try {
            Concurrency::driver('queue')->run([
                fn () => throw new QueueExceptionWithFalseyParam($value),
            ]);
        } catch (QueueExceptionWithFalseyParam $e) {
            $this->assertSame($value, $e->value);

            return;
        }

        $this->fail('The expected exception was not thrown.');
    }

    public static function falseyExceptionParameters(): array
    {
        return [
            'zero' => [0],
            'false' => [false],
            'empty string' => [''],
        ];
    }

    public function testFirstFailureInKeyOrderWins()
    {
        try {
            Concurrency::driver('queue')->run([
                'first' => fn () => throw new Exception('First failure'),
                'second' => fn () => throw new Exception('Second failure'),
            ]);
        } catch (Exception $e) {
            $this->assertSame('First failure', $e->getMessage());

            return;
        }

        $this->fail('The expected exception was not thrown.');
    }

    public function testCacheIsCleanedUpWhenATaskFails()
    {
        $thrown = false;

        $ulid = Str::freezeUlids(function () use (&$thrown) {
            try {
                Concurrency::driver('queue')->run([
                    'ok' => fn () => 'this task succeeds',
                    'boom' => fn () => throw new RuntimeException('Task failure'),
                ]);
            } catch (RuntimeException) {
                $thrown = true;
            }
        });

        $this->assertTrue($thrown);
        $this->assertNull(Cache::get("illuminate:concurrency:{$ulid}:0"));
        $this->assertNull(Cache::get("illuminate:concurrency:{$ulid}:1"));
    }

    public function testResultKeysAreRemovedFromCacheAfterRun()
    {
        $ulid = Str::freezeUlids(function () {
            return Concurrency::driver('queue')->run([fn () => 1, fn () => 2]);
        });

        $this->assertNull(Cache::get("illuminate:concurrency:{$ulid}:0"));
        $this->assertNull(Cache::get("illuminate:concurrency:{$ulid}:1"));
        $this->assertNull(Cache::get("illuminate:concurrency:{$ulid}:cancelled"));
    }

    #[DataProvider('processLocalCacheStores')]
    public function testRunRejectsProcessLocalCacheStoresForAsyncQueues(string $driver)
    {
        config()->set('queue.default', 'database');
        config()->set('cache.stores.local', ['driver' => $driver]);
        config()->set('cache.default', 'local');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('is not shared across processes');

        Concurrency::driver('queue')->run([fn () => 1]);
    }

    public static function processLocalCacheStores(): array
    {
        return [
            'array' => ['array'],
            'null' => ['null'],
            'session' => ['session'],
            'octane' => ['octane'],
            'apc' => ['apc'],
        ];
    }

    public function testRunRejectsDeferredQueueConnections()
    {
        config()->set('queue.connections.deferred', ['driver' => 'deferred']);
        config()->set('queue.default', 'deferred');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('may not be used with the queue concurrency driver');

        Concurrency::driver('queue')->run([fn () => 1]);
    }

    public function testRunRejectsNullQueueConnections()
    {
        config()->set('queue.connections.discard', ['driver' => 'null']);
        config()->set('queue.default', 'discard');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('may not be used with the queue concurrency driver');

        Concurrency::driver('queue')->run([fn () => 1]);
    }

    public function testRunTimesOutWhenNoWorkerProcessesTheTasks()
    {
        config()->set('queue.default', 'database');
        config()->set('cache.default', 'file');

        Queue::fake();

        // Without Carbon-synced fake sleeps advancing the clock, the poll
        // loop could never reach its deadline, so catching the timeout
        // exception below also proves that the loop polled and slept.
        Sleep::fake(syncWithCarbon: true);

        try {
            Concurrency::driver('queue')->run([fn () => 1, fn () => 2], timeout: 2);

            $this->fail('The expected timeout exception was not thrown.');
        } catch (TaskTimedOutException $e) {
            $this->assertSame(0, $e->received);
            $this->assertSame(2, $e->total);
            $this->assertSame(2, $e->seconds);
            $this->assertSame('database', $e->connection);
            $this->assertSame('file', $e->store);
        } finally {
            Cache::store('file')->flush();
        }
    }

    public function testTimeoutWritesCancellationFlagThatOutlivesTheRun()
    {
        config()->set('queue.default', 'database');
        config()->set('cache.default', 'file');

        Queue::fake();
        Sleep::fake(syncWithCarbon: true);

        $ulid = Str::freezeUlids(function () {
            try {
                Concurrency::driver('queue')->run([fn () => 1], timeout: 1);
            } catch (TaskTimedOutException) {
                //
            }
        });

        try {
            $this->assertTrue(Cache::store('file')->get("illuminate:concurrency:{$ulid}:cancelled"));
            $this->assertNull(Cache::store('file')->get("illuminate:concurrency:{$ulid}:0"));
        } finally {
            Cache::store('file')->flush();
        }
    }

    public function testDeferDispatchesCallQueuedClosureJobs()
    {
        Bus::fake();

        $callback = Concurrency::driver('queue')->defer([fn () => 1, fn () => 2]);

        Bus::assertNothingDispatched();

        $callback();

        Bus::assertDispatchedTimes(CallQueuedClosure::class, 2);
    }

    public function testManagerResolvesQueueDriver()
    {
        $this->assertInstanceOf(QueueDriver::class, Concurrency::driver('queue'));
    }

    public function testNamedInstancesUseTheirConfiguredQueue()
    {
        config()->set('concurrency.drivers.reports', ['driver' => 'queue', 'queue' => 'reports']);

        Bus::fake();

        $driver = Concurrency::driver('reports');

        $this->assertInstanceOf(QueueDriver::class, $driver);

        try {
            $driver->run([fn () => 1]);
        } catch (RuntimeException) {
            // The faked bus never executes the job, so no result is reported.
        }

        Bus::assertDispatched(InvokeQueuedClosure::class, fn ($job) => $job->queue === 'reports');
    }

    public function testJobWritesSuccessEnvelope()
    {
        $job = $this->makeJob(fn () => 'value');

        $job->handle($this->app, $this->app->make(CacheFactory::class));

        $envelope = Cache::get('task:0');

        $this->assertTrue($envelope['successful']);
        $this->assertSame('value', TaskResult::unwrap($envelope));
    }

    public function testJobSwallowsFailuresWhenNotRethrowing()
    {
        $job = $this->makeJob(fn () => throw new Exception('Task failure'));

        $job->handle($this->app, $this->app->make(CacheFactory::class));

        $envelope = Cache::get('task:0');

        $this->assertFalse($envelope['successful']);
        $this->assertSame(Exception::class, $envelope['exception']);
    }

    public function testJobRethrowsCapturedTaskExceptionsWhenRethrowing()
    {
        $job = $this->makeJob(fn () => throw new Exception('Task failure'), rethrowFailures: true);

        try {
            $job->handle($this->app, $this->app->make(CacheFactory::class));
        } catch (CapturedTaskException $e) {
            $this->assertSame('Task failure', $e->getMessage());
            $this->assertSame('Task failure', $e->getPrevious()->getMessage());
            $this->assertFalse(Cache::get('task:0')['successful']);

            return;
        }

        $this->fail('The expected exception was not thrown.');
    }

    public function testJobHonorsCancellationFlag()
    {
        $invoked = false;

        Cache::forever('task:cancelled', true);

        $job = $this->makeJob(function () use (&$invoked) {
            $invoked = true;
        });

        $job->handle($this->app, $this->app->make(CacheFactory::class));

        $this->assertFalse($invoked);
        $this->assertNull(Cache::get('task:0'));
    }

    public function testJobHonorsExpiredDeadline()
    {
        $invoked = false;

        $job = $this->makeJob(function () use (&$invoked) {
            $invoked = true;
        }, deadline: time() - 10);

        $job->handle($this->app, $this->app->make(CacheFactory::class));

        $this->assertFalse($invoked);
        $this->assertNull(Cache::get('task:0'));
    }

    public function testFailedWritesInfrastructureFailureEnvelope()
    {
        $job = $this->makeJob(fn () => 'value');

        $job->failed(new RuntimeException('The worker died'));

        $envelope = Cache::get('task:0');

        $this->assertFalse($envelope['successful']);
        $this->assertSame(RuntimeException::class, $envelope['exception']);
        $this->assertSame('The worker died', $envelope['message']);
    }

    public function testFailedIgnoresCapturedTaskExceptions()
    {
        $job = $this->makeJob(fn () => 'value');

        $job->failed(new CapturedTaskException(new Exception('Already enveloped')));

        $this->assertNull(Cache::get('task:0'));
    }

    public function testFailedDoesNotOverwriteExistingEnvelopes()
    {
        Cache::put('task:0', TaskResult::success('kept'), 60);

        $job = $this->makeJob(fn () => 'value');

        $job->failed(new RuntimeException('Late failure'));

        $this->assertSame('kept', TaskResult::unwrap(Cache::get('task:0')));
    }

    public function testJobDefaults()
    {
        $job = $this->makeJob(fn () => 'value');

        $this->assertSame(1, $job->tries);
        $this->assertTrue($job->failOnTimeout);
        $this->assertFalse($job->afterCommit);
    }

    public function testMixedKeysArePreservedInOrder()
    {
        $results = Concurrency::driver('queue')->run([
            5 => fn () => 'five',
            'a' => fn () => 'letter',
            0 => fn () => 'zero',
        ]);

        $this->assertSame([5 => 'five', 'a' => 'letter', 0 => 'zero'], $results);
    }

    public function testTimeoutAcceptsCarbonInterval()
    {
        $results = Concurrency::driver('queue')->run([fn () => 'value'], CarbonInterval::seconds(5));

        $this->assertSame(['value'], $results);
    }

    public function testAsyncRunReturnsOnceAllEnvelopesArePresent()
    {
        config()->set('queue.default', 'database');
        config()->set('cache.default', 'file');

        Queue::fake();
        Sleep::fake();

        $results = null;

        try {
            Str::freezeUlids(function ($ulid) use (&$results) {
                Cache::store('file')->put("illuminate:concurrency:{$ulid}:0", TaskResult::success('one'), 60);
                Cache::store('file')->put("illuminate:concurrency:{$ulid}:1", TaskResult::success('two'), 60);

                $results = Concurrency::driver('queue')->run([
                    'a' => fn () => null,
                    'b' => fn () => null,
                ]);
            });
        } finally {
            Cache::store('file')->flush();
        }

        $this->assertSame(['a' => 'one', 'b' => 'two'], $results);

        Sleep::assertNeverSlept();
    }

    public function testPartialTimeoutReportsReceivedResultsAndCleansUp()
    {
        config()->set('queue.default', 'database');
        config()->set('cache.default', 'file');

        Carbon::setTestNow(Carbon::now());
        Queue::fake();
        Sleep::fake(syncWithCarbon: true);

        try {
            Str::freezeUlids(function ($ulid) {
                Cache::store('file')->put("illuminate:concurrency:{$ulid}:0", TaskResult::success('one'), 60);

                try {
                    Concurrency::driver('queue')->run(['a' => fn () => null, 'b' => fn () => null], timeout: 1);

                    $this->fail('The expected timeout exception was not thrown.');
                } catch (TaskTimedOutException $e) {
                    $this->assertSame(1, $e->received);
                    $this->assertSame(2, $e->total);
                }

                $this->assertNull(Cache::store('file')->get("illuminate:concurrency:{$ulid}:0"));
                $this->assertTrue(Cache::store('file')->get("illuminate:concurrency:{$ulid}:cancelled"));
            });
        } finally {
            Carbon::setTestNow();
            Cache::store('file')->flush();
        }
    }

    public function testJobDeadlineIsDerivedFromRunStart()
    {
        config()->set('queue.default', 'database');
        config()->set('cache.default', 'file');

        Carbon::setTestNow($now = Carbon::now());
        Queue::fake();
        Sleep::fake(syncWithCarbon: true);

        try {
            Concurrency::driver('queue')->run([fn () => 1], timeout: 30);

            $this->fail('The expected timeout exception was not thrown.');
        } catch (TaskTimedOutException) {
            //
        } finally {
            Carbon::setTestNow();
            Cache::store('file')->flush();
        }

        Queue::assertPushed(InvokeQueuedClosure::class, fn ($job) => $job->deadline === $now->getTimestamp() + 30);
    }

    public function testPollLoopUsesTheFullTimeoutBudget()
    {
        config()->set('queue.default', 'database');
        config()->set('cache.default', 'file');

        Carbon::setTestNow(Carbon::now());
        Queue::fake();
        Sleep::fake(syncWithCarbon: true);

        try {
            Concurrency::driver('queue')->run([fn () => 1], timeout: 1);

            $this->fail('The expected timeout exception was not thrown.');
        } catch (TaskTimedOutException) {
            //
        } finally {
            Carbon::setTestNow();
            Cache::store('file')->flush();
        }

        Sleep::assertSleptTimes(10);
    }

    public function testSyncMissingEnvelopeFailsWithoutSleeping()
    {
        Bus::fake();
        Sleep::fake();

        Str::freezeUlids(function ($ulid) {
            Cache::put("illuminate:concurrency:{$ulid}:0", TaskResult::success('collected'), 60);

            try {
                Concurrency::driver('queue')->run([fn () => 1, fn () => 2]);

                $this->fail('The expected exception was not thrown.');
            } catch (RuntimeException $e) {
                $this->assertStringContainsString('did not report a result', $e->getMessage());
            }

            $this->assertNull(Cache::get("illuminate:concurrency:{$ulid}:0"));
        });

        Sleep::assertNeverSlept();
    }

    public function testDispatchFailureWritesCancellationFlagAndCleansUp()
    {
        $context = new class {
        };

        $thrown = null;

        $ulid = Str::freezeUlids(function () use ($context, &$thrown) {
            try {
                Concurrency::driver('queue')->run([
                    // The first task runs inline and caches its envelope
                    // before the second task's dispatch fails to serialize.
                    'ok' => fn () => 'collected',
                    'boom' => fn () => $context,
                ]);
            } catch (Throwable $thrown) {
                //
            }
        });

        $this->assertNotNull($thrown);
        $this->assertTrue(Cache::get("illuminate:concurrency:{$ulid}:cancelled"));
        $this->assertNull(Cache::get("illuminate:concurrency:{$ulid}:0"));
    }

    public function testRunRejectsBackgroundQueueConnections()
    {
        config()->set('queue.connections.later', ['driver' => 'background']);
        config()->set('queue.default', 'later');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('may not be used with the queue concurrency driver');

        Concurrency::driver('queue')->run([fn () => 1]);
    }

    public function testDeferRejectsNullQueueConnections()
    {
        config()->set('queue.connections.discard', ['driver' => 'null']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('may not be used with the queue concurrency driver');

        Concurrency::driver('queue')->onConnection('discard')->defer([fn () => 1]);
    }

    public function testLegacyDriverConfigFallbackResolvesQueueDriver()
    {
        config()->set('concurrency.driver', ['legacy' => ['driver' => 'queue', 'queue' => 'legacy-queue']]);

        Bus::fake();

        $driver = Concurrency::driver('legacy');

        $this->assertInstanceOf(QueueDriver::class, $driver);

        try {
            $driver->run([fn () => 1]);
        } catch (RuntimeException) {
            // The faked bus never executes the job, so no result is reported.
        }

        Bus::assertDispatched(InvokeQueuedClosure::class, fn ($job) => $job->queue === 'legacy-queue');
    }

    public function testRuntimeTargetingReturnsNewDriverInstances()
    {
        Bus::fake();

        $base = Concurrency::driver('queue');

        $configured = $base->onConnection('sync')->onQueue('images')->store('array');

        $this->assertInstanceOf(QueueDriver::class, $configured);
        $this->assertNotSame($base, $configured);

        try {
            $configured->run([fn () => 1]);
        } catch (RuntimeException) {
            //
        }

        Bus::assertDispatched(InvokeQueuedClosure::class, function ($job) {
            return $job->connection === 'sync' && $job->queue === 'images' && $job->store === 'array';
        });

        try {
            $base->run([fn () => 1]);
        } catch (RuntimeException) {
            //
        }

        Bus::assertDispatched(InvokeQueuedClosure::class, fn ($job) => is_null($job->queue));
    }

    public function testInlineEnvelopesAreCollectedBeforeLaterTasksRun()
    {
        $results = null;

        Str::freezeUlids(function ($ulid) use (&$results) {
            $results = Concurrency::driver('queue')->run([
                'first' => fn () => 'kept',
                'second' => function () use ($ulid) {
                    // Simulates the first envelope expiring while a later
                    // inline task is still running, since inline execution
                    // is not bounded by the run timeout.
                    Cache::forget("illuminate:concurrency:{$ulid}:0");

                    return 'second';
                },
            ]);
        });

        $this->assertSame(['first' => 'kept', 'second' => 'second'], $results);
    }

    public function testHandleDoesNotOverwriteExistingEnvelope()
    {
        Cache::put('task:0', TaskResult::success('first'), 60);

        $job = $this->makeJob(fn () => 'second');

        $job->handle($this->app, $this->app->make(CacheFactory::class));

        $this->assertSame('first', TaskResult::unwrap(Cache::get('task:0')));
    }

    public function testFailureEnvelopesDropUnserializableExceptionParameters()
    {
        $job = new InvokeQueuedClosure(
            'illuminate:concurrency:test:0',
            'illuminate:concurrency:test:cancelled',
            'file',
            60,
            time() + 60,
            false,
            new SerializableClosure(fn () => throw new QueueExceptionWithClosureParam(fn () => 'context')),
        );

        try {
            $job->handle($this->app, $this->app->make(CacheFactory::class));

            $envelope = Cache::store('file')->get('illuminate:concurrency:test:0');

            $this->assertFalse($envelope['successful']);
            $this->assertSame(QueueExceptionWithClosureParam::class, $envelope['exception']);
            $this->assertSame([], $envelope['parameters']);
        } finally {
            Cache::store('file')->flush();
        }
    }

    public function testResultsRoundTripThroughARealDatabaseWorker()
    {
        $this->prepareDatabaseQueue();

        Sleep::fake(syncWithCarbon: true);

        Sleep::whenFakingSleep(function () {
            $this->artisan('queue:work', ['connection' => 'database', '--once' => true, '--sleep' => 0])->run();
        });

        try {
            $results = Concurrency::driver('queue')->run([
                'first' => fn () => 1 + 1,
                'second' => fn () => 'two',
            ], timeout: 30);

            $this->assertSame(['first' => 2, 'second' => 'two'], $results);
            $this->assertSame(0, DB::table('jobs')->count());
        } finally {
            Cache::store('file')->flush();
        }
    }

    public function testTaskFailuresRoundTripThroughARealDatabaseWorker()
    {
        $this->prepareDatabaseQueue();

        Sleep::fake(syncWithCarbon: true);

        Sleep::whenFakingSleep(function () {
            $this->artisan('queue:work', ['connection' => 'database', '--once' => true, '--sleep' => 0])->run();
        });

        try {
            Concurrency::driver('queue')->run([fn () => throw new Exception('Worker failure')], timeout: 30);

            $this->fail('The expected exception was not thrown.');
        } catch (Exception $e) {
            $this->assertSame(Exception::class, get_class($e));
            $this->assertSame('Worker failure', $e->getMessage());
        } finally {
            Cache::store('file')->flush();
        }

        $this->assertSame(1, DB::table('failed_jobs')->count());
    }

    protected function prepareDatabaseQueue(): void
    {
        config()->set('queue.default', 'database');
        config()->set('cache.default', 'file');
        config()->set('queue.failed.driver', 'database-uuids');
        config()->set('queue.failed.database', 'testing');
        config()->set('queue.failed.table', 'failed_jobs');

        Schema::create('jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }

    protected function makeJob(callable $task, bool $rethrowFailures = false, ?int $deadline = null): InvokeQueuedClosure
    {
        return new InvokeQueuedClosure(
            'task:0',
            'task:cancelled',
            null,
            60,
            $deadline ?? time() + 60,
            $rethrowFailures,
            new SerializableClosure($task),
        );
    }
}

class QueueExceptionWithParam extends Exception
{
    public function __construct(
        public string $uri,
        public int $statusCode,
        public string $reason,
        public string|array $responseBody = '',
    ) {
        parent::__construct("API request to {$uri} failed with status $statusCode $reason");
    }
}

class QueueExceptionWithFalseyParam extends Exception
{
    public function __construct(public int|bool|string $value)
    {
        parent::__construct('Exception with falsey parameter');
    }
}

class QueueExceptionWithClosureParam extends Exception
{
    public function __construct(public Closure $callback)
    {
        parent::__construct('Exception with closure parameter');
    }
}
