<?php

namespace Illuminate\Tests\Integration\Concurrency;

use DomainException;
use Illuminate\Concurrency\InvokeDeferredClosure;
use Illuminate\Concurrency\InvokeQueuedClosure;
use Illuminate\Concurrency\TaskResult;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Queue\CallQueuedClosure;
use Illuminate\Queue\Events\QueueFailedOver;
use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\SerializableClosure\SerializableClosure;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;
use RuntimeException;

class QueueConcurrencyFailoverTest extends TestCase
{
    protected function defineEnvironment($app)
    {
        $app['config']->set('cache.default', 'file');

        // A link the failover queue hops off exactly as it would a dead redis,
        // without needing a redis client: the connector for its driver does
        // not exist.
        $app['config']->set('queue.connections.dead', ['driver' => 'no-such-driver']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Cache::store('file')->flush();
    }

    protected function tearDown(): void
    {
        Cache::store('file')->flush();

        parent::tearDown();
    }

    public function testDeliversTheOriginalExceptionWhenTheChainFallsThroughToSync()
    {
        $this->useChain(['dead', 'sync']);

        try {
            Concurrency::driver('queue')->run([
                'only' => function () {
                    Cache::store('file')->increment('runs');

                    throw new DomainException('task failed');
                },
            ], timeout: 3);
        } catch (DomainException $e) {
            $this->assertSame('task failed', $e->getMessage());
            $this->assertSame(1, $this->runsSoFar());

            return;
        }

        $this->fail('The original exception was not delivered.');
    }

    public function testRunsAFailingTaskOnceEvenWithAnotherSyncLinkAfterIt()
    {
        $this->useChain(['dead', 'sync', 'sync']);

        $hops = [];

        Event::listen(QueueFailedOver::class, function (QueueFailedOver $event) use (&$hops) {
            $hops[] = $event->connectionName;
        });

        try {
            Concurrency::driver('queue')->run([
                'only' => function () {
                    Cache::store('file')->increment('runs');

                    throw new DomainException('task failed');
                },
            ], timeout: 3);

            $this->fail('The expected exception was not thrown.');
        } catch (DomainException) {
            //
        }

        // The task's failure must not read as a dead link: the only hop is off "dead".
        $this->assertSame(1, $this->runsSoFar());
        $this->assertSame(['dead'], $hops);
    }

    public function testLeavesNoDuplicateOnARealQueueAfterASyncLinkRanTheTask()
    {
        $this->useChain(['dead', 'sync', 'database']);

        try {
            Concurrency::driver('queue')->run([
                'only' => function () {
                    Cache::store('file')->increment('runs');

                    throw new DomainException('task failed');
                },
            ], timeout: 3);

            $this->fail('The expected exception was not thrown.');
        } catch (DomainException) {
            //
        }

        $this->assertSame(1, $this->runsSoFar());
        $this->assertSame(0, DB::table('jobs')->count());
    }

    public function testDoesNotLetADeadLinkAfterSyncMaskTheTaskException()
    {
        $this->useChain(['dead', 'sync', 'dead']);

        $this->expectException(DomainException::class);

        Concurrency::driver('queue')->run([
            'only' => fn () => throw new DomainException('task failed'),
        ], timeout: 3);
    }

    public function testReturnsResultsWhenTheChainFallsThroughToSync()
    {
        $this->useChain(['dead', 'sync']);

        $this->assertSame(['a' => 2, 'b' => 'two'], Concurrency::driver('queue')->run([
            'a' => fn () => 1 + 1,
            'b' => fn () => 'two',
        ], timeout: 3));
    }

    public function testReportsASyncFallThroughFailureTheWayPlainSyncDoes()
    {
        $this->useChain(['dead', 'sync']);

        Exceptions::fake();

        try {
            Concurrency::driver('queue')->run([
                'only' => fn () => throw new DomainException('task failed'),
            ], timeout: 3);
        } catch (DomainException) {
            //
        }

        Exceptions::assertReported(DomainException::class);
    }

    public function testKeepsAnEnvelopeALaterFallThroughTaskOutlived()
    {
        // Work that ran during dispatch is not bounded by the timeout, so a
        // slow later task can outlive an earlier envelope's TTL. The envelope
        // read back right after dispatch is what survives that.
        $this->useChain(['dead', 'sync']);

        try {
            $results = Concurrency::driver('queue')->run([
                'first' => fn () => 'kept',
                'second' => function () {
                    Carbon::setTestNow(Carbon::now()->addSeconds(120));

                    return 'slow';
                },
            ], timeout: 3);
        } finally {
            Carbon::setTestNow();
        }

        $this->assertSame(['first' => 'kept', 'second' => 'slow'], $results);
    }

    public function testTreatsAChainMadeOnlyOfSyncLinksAsInline()
    {
        $this->useChain(['sync']);
        config()->set('cache.default', 'array');

        $this->assertSame([7], Concurrency::driver('queue')->run([fn () => 7]));
    }

    #[DataProvider('connectionsThatNeverRunTasks')]
    public function testRefusesAChainContainingAConnectionThatWouldNeverRunTheTasks(string $driver)
    {
        config()->set('queue.connections.never', ['driver' => $driver]);
        $this->useChain(['dead', 'never']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('may not be used with the queue concurrency driver');

        Concurrency::driver('queue')->run([fn () => 1], timeout: 1);
    }

    public static function connectionsThatNeverRunTasks(): array
    {
        return [
            'null' => ['null'],
            'deferred' => ['deferred'],
            'background' => ['background'],
        ];
    }

    public function testRefusesACyclicFailoverChain()
    {
        config()->set('queue.connections.a', ['driver' => 'failover', 'connections' => ['b']]);
        config()->set('queue.connections.b', ['driver' => 'failover', 'connections' => ['a']]);
        config()->set('queue.default', 'a');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('refers back to itself');

        Concurrency::driver('queue')->run([fn () => 1], timeout: 1);
    }

    public function testRefusesAFailoverChainWithNoConnections()
    {
        $this->useChain([]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('has no connections');

        Concurrency::driver('queue')->run([fn () => 1], timeout: 1);
    }

    public function testRefusesAFailoverChainWithNoConnectionsWhenDeferringBeforeTheCallbackRuns()
    {
        $this->useChain([]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('has no connections');

        Concurrency::driver('queue')->defer([fn () => 1]);
    }

    public function testRefusesACyclicFailoverCacheStore()
    {
        config()->set('queue.default', 'database');
        config()->set('cache.stores.loop_a', ['driver' => 'failover', 'stores' => ['loop_b']]);
        config()->set('cache.stores.loop_b', ['driver' => 'failover', 'stores' => ['loop_a']]);
        config()->set('cache.default', 'loop_a');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('refers back to itself');

        Concurrency::driver('queue')->run([fn () => 1], timeout: 1);
    }

    public function testRefusesAFailoverCacheStoreWhoseFallbackIsNotSharedForAnAsyncRun()
    {
        config()->set('queue.default', 'database');
        config()->set('cache.stores.fallback', ['driver' => 'failover', 'stores' => ['file', 'array']]);
        config()->set('cache.default', 'fallback');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('is not shared across processes');

        Concurrency::driver('queue')->run([fn () => 1], timeout: 1);
    }

    public function testLeavesATombstoneSoAStragglerRefusesToRunAfterTheCallerWasAnswered()
    {
        config()->set('queue.default', 'sync');
        $invoked = false;

        $ulid = Str::freezeUlids(function () {
            return Concurrency::driver('queue')->run([fn () => 'done']);
        });

        $this->assertTrue(Cache::store('file')->get("illuminate:concurrency:{$ulid}:cancelled"));

        // The same job, redelivered after the run finished and its envelope
        // was deleted, with the deadline still in the future.
        $straggler = new InvokeQueuedClosure(
            "illuminate:concurrency:{$ulid}:0",
            "illuminate:concurrency:{$ulid}:cancelled",
            'file',
            60,
            time() + 60,
            true,
            new SerializableClosure(function () use (&$invoked) {
                $invoked = true;
            }),
        );

        $straggler->handle($this->app, $this->app->make(CacheFactory::class));

        $this->assertFalse($invoked);
    }

    public function testSkipsAJobWhoseEnvelopeAlreadyExists()
    {
        $invoked = false;

        Cache::store('file')->put('dup:0', TaskResult::success('first'), 60);

        $job = new InvokeQueuedClosure('dup:0', 'dup:cancelled', 'file', 60, time() + 60, true, new SerializableClosure(function () use (&$invoked) {
            $invoked = true;
        }));

        $job->handle($this->app, $this->app->make(CacheFactory::class));

        $this->assertFalse($invoked);
        $this->assertSame('first', TaskResult::unwrap(Cache::store('file')->get('dup:0')));
    }

    public function testRunsAFailingDeferredTaskOnceWhenTheChainFallsThroughToSync()
    {
        $this->useChain(['dead', 'sync', 'sync']);

        Exceptions::fake();

        Concurrency::driver('queue')->defer([
            function () {
                Cache::store('file')->increment('runs');

                throw new DomainException('deferred failed');
            },
        ])();

        $this->assertSame(1, $this->runsSoFar());

        Exceptions::assertReported(DomainException::class);
    }

    public function testLetsTheWorkerRetryADeferredTaskThatFailsOnce()
    {
        $this->prepareFailedJobs();
        config()->set('queue.default', 'database');

        Concurrency::driver('queue')->defer([
            function () {
                if (Cache::store('file')->increment('attempts') === 1) {
                    throw new RuntimeException('first attempt fails');
                }

                Cache::store('file')->put('outcome', 'succeeded on retry', 60);
            },
        ])();

        // Two attempts allowed by the worker, so the second one completes the task.
        $this->artisan('queue:work', ['connection' => 'database', '--once' => true, '--tries' => 2, '--sleep' => 0])->run();
        $this->artisan('queue:work', ['connection' => 'database', '--once' => true, '--tries' => 2, '--sleep' => 0])->run();

        $this->assertSame(2, Cache::store('file')->get('attempts'));
        $this->assertSame('succeeded on retry', Cache::store('file')->get('outcome'));
        $this->assertSame(0, DB::table('failed_jobs')->count());
        $this->assertSame(0, DB::table('jobs')->count());
    }

    public function testInjectsTheJobIntoADeferredClosureThatAsksForIt()
    {
        config()->set('queue.default', 'sync');

        // CallQueuedClosure hands the closure the queued command itself as
        // $job, which in turn carries the queue's job wrapper; keep exactly that.
        Concurrency::driver('queue')->defer([
            function ($job) {
                Cache::store('file')->put('job class', $job::class, 60);
                Cache::store('file')->put('wrapper class', $job->job::class, 60);
            },
        ])();

        $this->assertSame(InvokeDeferredClosure::class, Cache::store('file')->get('job class'));
        $this->assertSame(SyncJob::class, Cache::store('file')->get('wrapper class'));
    }

    public function testTheDeferredJobIsACallQueuedClosure()
    {
        config()->set('queue.default', 'sync');

        Concurrency::driver('queue')->defer([
            function (CallQueuedClosure $job) {
                Cache::store('file')->put('typed', 'ran', 60);
                Cache::store('file')->put('batch', $job->batch() === null ? 'none' : 'some', 60);
            },
        ])();

        $this->assertSame('ran', Cache::store('file')->get('typed'));
        $this->assertSame('none', Cache::store('file')->get('batch'));
        $this->assertTrue(is_subclass_of(InvokeDeferredClosure::class, CallQueuedClosure::class));
        $this->assertTrue((new ReflectionProperty(InvokeDeferredClosure::class, 'deleteWhenMissingModels'))->getDefaultValue());
        $this->assertFalse(property_exists(InvokeDeferredClosure::class, 'tries'));
    }

    protected function useChain(array $links): void
    {
        config()->set('queue.connections.chain', ['driver' => 'failover', 'connections' => $links]);
        config()->set('queue.default', 'chain');
    }

    protected function runsSoFar(): int
    {
        return (int) Cache::store('file')->get('runs', 0);
    }

    protected function prepareFailedJobs(): void
    {
        config()->set('queue.failed.driver', 'database-uuids');
        config()->set('queue.failed.database', 'testing');
        config()->set('queue.failed.table', 'failed_jobs');

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
}
