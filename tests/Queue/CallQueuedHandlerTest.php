<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Bus\Dispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Bus\UniqueLock;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Queue\CallQueuedHandler;
use Illuminate\Queue\Jobs\FakeJob;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class CallQueuedHandlerTest extends TestCase
{
    protected $container;
    protected $cache;
    protected $locks;
    protected $handler;

    protected function setUp(): void
    {
        $this->container = new Container;
        $this->cache = new Repository(new ArrayStore);
        $this->container->instance(Cache::class, $this->cache);
        $this->locks = new UniqueLock($this->cache);
        $this->handler = new CallQueuedHandler(new Dispatcher($this->container), $this->container);
    }

    public function testUniqueUntilProcessingLockIsReleasedOnTerminalFailure()
    {
        $command = new UniqueUntilProcessingFailureJob;
        $this->assertTrue($this->locks->acquire($command));

        $this->handler->failed(['command' => serialize($command)], new RuntimeException, 'job-id', new FakeJob);

        $this->assertTrue($this->locks->acquire(new UniqueUntilProcessingFailureJob));
    }

    public function testUniqueUntilProcessingListenerLockIsReleasedOnTerminalFailure()
    {
        $command = new CallQueuedListener(UniqueUntilProcessingFailureJob::class, 'handle', []);
        $command->shouldBeUnique = true;
        $command->shouldBeUniqueUntilProcessing = true;
        $this->assertTrue($this->locks->acquire($command));

        $this->handler->failed(['command' => serialize($command)], new RuntimeException, 'job-id', new FakeJob);

        $this->assertTrue($this->locks->acquire(clone $command));
    }

    public function testFailureDoesNotReleaseReplacementJobLock()
    {
        $command = new UniqueUntilProcessingFailureJob;
        $this->assertTrue($this->locks->acquire($command));
        $this->locks->release($command);
        $this->assertTrue($this->locks->acquire(new UniqueUntilProcessingFailureJob));

        $this->handler->failed(['command' => serialize($command)], new RuntimeException, 'job-id', new FakeJob);

        $this->assertFalse($this->locks->acquire(new UniqueUntilProcessingFailureJob));
    }

    public function testLegacyUniqueUntilProcessingFailureDoesNotForceReleaseAnUnownedLock()
    {
        $command = new UniqueUntilProcessingFailureJob;
        $this->assertTrue($this->locks->acquire($command));
        $command->uniqueLockOwner = '';

        $this->handler->failed(['command' => serialize($command)], new RuntimeException, 'job-id', new FakeJob);

        $this->assertFalse($this->locks->acquire(new UniqueUntilProcessingFailureJob));
    }

    public function testLegacyUniqueJobLockIsStillReleasedOnTerminalFailure()
    {
        $command = new LegacyUniqueFailureJob;
        $this->assertTrue($this->locks->acquire($command));

        $this->handler->failed(['command' => serialize($command)], new RuntimeException, 'job-id', new FakeJob);

        $this->assertTrue($this->locks->acquire(new LegacyUniqueFailureJob));
    }

    public function testUniqueLockReleaseOverrideCanForwardOwnershipRequirement()
    {
        $locks = new CustomUniqueLock($this->cache);
        $command = new UniqueUntilProcessingFailureJob;
        $this->assertTrue($locks->acquire($command));

        $locks->release($command, onlyOwned: true);

        $this->assertTrue($locks->onlyOwned);
        $this->assertTrue($locks->acquire(new UniqueUntilProcessingFailureJob));
    }

    public function testHandlerLockReleaseOverrideReceivesOwnershipRequirement()
    {
        $handler = new CustomLockReleaseHandler(new Dispatcher($this->container), $this->container);
        $command = new UniqueUntilProcessingFailureJob;
        $this->assertTrue($this->locks->acquire($command));

        $handler->failed(['command' => serialize($command)], new RuntimeException, 'job-id', new FakeJob);

        $this->assertTrue($handler->onlyOwned);
        $this->assertTrue($this->locks->acquire(new UniqueUntilProcessingFailureJob));
    }
}

class UniqueUntilProcessingFailureJob implements ShouldBeUniqueUntilProcessing
{
    use Queueable;
}

class LegacyUniqueFailureJob implements ShouldBeUnique
{
}

class CustomUniqueLock extends UniqueLock
{
    public $onlyOwned = false;

    public function release($job, $onlyOwned = false)
    {
        $this->onlyOwned = $onlyOwned;

        parent::release($job, $onlyOwned);
    }
}

class CustomLockReleaseHandler extends CallQueuedHandler
{
    public $onlyOwned = false;

    protected function ensureUniqueJobLockIsReleased($command, $onlyOwned = false)
    {
        $this->onlyOwned = $onlyOwned;

        parent::ensureUniqueJobLockIsReleased($command, $onlyOwned);
    }
}
