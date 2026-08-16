<?php

namespace Illuminate\Tests\Integration\Broadcasting;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\Broadcaster;
use Illuminate\Contracts\Broadcasting\ShouldBeUnique;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastAfterCommit;
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Orchestra\Testbench\TestCase;

class BroadcastAfterCommitTest extends TestCase
{
    /**
     * The number of events that reached the broadcaster.
     *
     * @var int
     */
    public static $broadcasts = 0;

    /**
     * The database transactions manager instance.
     *
     * @var \Illuminate\Database\DatabaseTransactionsManager
     */
    protected $transactions;

    protected function defineEnvironment($app)
    {
        $app['config']->set('broadcasting.default', 'counting');
        $app['config']->set('broadcasting.connections.counting', ['driver' => 'counting']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        static::$broadcasts = 0;

        Broadcast::extend('counting', fn () => new CountingBroadcaster);

        $this->app->instance('db.transactions', $this->transactions = new DatabaseTransactionsManager);
    }

    public function testEventWithoutContractIsBroadcastInsideATransaction()
    {
        $this->transactions->begin('testing', 1);

        Event::dispatch(new BroadcastAfterCommitTestEvent);

        $this->assertSame(1, static::$broadcasts);
    }

    public function testEventWithContractIsNotBroadcastUntilTheTransactionCommits()
    {
        $this->transactions->begin('testing', 1);

        Event::dispatch(new BroadcastAfterCommitTestEventAfterCommit);

        $this->assertSame(0, static::$broadcasts);

        $this->transactions->commit('testing', 1, 0);

        $this->assertSame(1, static::$broadcasts);
    }

    public function testEventWithContractIsNeverBroadcastWhenTheTransactionRollsBack()
    {
        $this->transactions->begin('testing', 1);

        Event::dispatch(new BroadcastAfterCommitTestEventAfterCommit);

        $this->transactions->rollback('testing', 0);

        $this->assertSame(0, static::$broadcasts);
    }

    public function testEventWithContractIsBroadcastImmediatelyWithoutATransaction()
    {
        Event::dispatch(new BroadcastAfterCommitTestEventAfterCommit);

        $this->assertSame(1, static::$broadcasts);
    }

    public function testEventWithContractCanOptOutOfWaitingForTheTransactionToCommit()
    {
        $this->transactions->begin('testing', 1);

        Event::dispatch(new BroadcastAfterCommitTestEventBeforeCommit);

        $this->assertSame(1, static::$broadcasts);
    }

    public function testEventWithContractIsOnlyBroadcastWhenTheOuterTransactionCommits()
    {
        $this->transactions->begin('testing', 1);
        $this->transactions->begin('testing', 2);

        Event::dispatch(new BroadcastAfterCommitTestEventAfterCommit);

        $this->transactions->commit('testing', 2, 1);

        $this->assertSame(0, static::$broadcasts);

        $this->transactions->commit('testing', 1, 0);

        $this->assertSame(1, static::$broadcasts);
    }

    public function testUniqueEventWithContractIsNotBroadcastUntilTheTransactionCommits()
    {
        $this->transactions->begin('testing', 1);

        Event::dispatch(new BroadcastAfterCommitTestUniqueEvent);

        $this->assertSame(0, static::$broadcasts);

        $this->transactions->commit('testing', 1, 0);

        $this->assertSame(1, static::$broadcasts);
    }
}

class CountingBroadcaster implements Broadcaster
{
    public function auth($request)
    {
        //
    }

    public function validAuthenticationResponse($request, $result)
    {
        //
    }

    public function broadcast(array $channels, $event, array $payload = [])
    {
        BroadcastAfterCommitTest::$broadcasts++;
    }
}

class BroadcastAfterCommitTestEvent implements ShouldBroadcast
{
    public function broadcastOn()
    {
        return [new PrivateChannel('orders.1')];
    }
}

class BroadcastAfterCommitTestEventAfterCommit extends BroadcastAfterCommitTestEvent implements ShouldBroadcastAfterCommit
{
    //
}

class BroadcastAfterCommitTestEventBeforeCommit extends BroadcastAfterCommitTestEventAfterCommit
{
    public $afterCommit = false;
}

class BroadcastAfterCommitTestUniqueEvent extends BroadcastAfterCommitTestEventAfterCommit implements ShouldBeUnique
{
    //
}
