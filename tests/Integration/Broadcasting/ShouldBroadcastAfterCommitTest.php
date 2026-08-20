<?php

namespace Illuminate\Tests\Integration\Broadcasting;

use Exception;
use Illuminate\Contracts\Broadcasting\Broadcaster;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastAfterCommit;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Orchestra\Testbench\TestCase;

class ShouldBroadcastAfterCommitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Broadcast::extend('recording', fn () => new RecordingBroadcaster);
    }

    protected function tearDown(): void
    {
        RecordingBroadcaster::$broadcasts = [];

        parent::tearDown();
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('queue.default', 'sync');
        $app['config']->set('broadcasting.default', 'recording');
        $app['config']->set('broadcasting.connections.recording', ['driver' => 'recording']);
    }

    public function testEventIsBroadcastImmediatelyIfThereIsNoTransaction()
    {
        Event::dispatch(new BroadcastAfterCommitTestEvent);

        $this->assertSame([BroadcastAfterCommitTestEvent::class], RecordingBroadcaster::$broadcasts);
    }

    public function testEventIsOnlyBroadcastAfterTheTransactionCommits()
    {
        DB::transaction(function () {
            Event::dispatch(new BroadcastAfterCommitTestEvent);

            $this->assertSame([], RecordingBroadcaster::$broadcasts);
        });

        $this->assertSame([BroadcastAfterCommitTestEvent::class], RecordingBroadcaster::$broadcasts);
    }

    public function testEventIsNotBroadcastIfTheTransactionIsRolledBack()
    {
        try {
            DB::transaction(function () {
                Event::dispatch(new BroadcastAfterCommitTestEvent);

                throw new Exception;
            });
        } catch (Exception) {
            //
        }

        $this->assertSame([], RecordingBroadcaster::$broadcasts);
    }

    public function testEventWithoutTheContractIsBroadcastInsideTheTransaction()
    {
        DB::transaction(function () {
            Event::dispatch(new BroadcastTestEvent);

            $this->assertSame([BroadcastTestEvent::class], RecordingBroadcaster::$broadcasts);
        });
    }
}

class BroadcastTestEvent implements ShouldBroadcast
{
    public function broadcastOn()
    {
        return ['test-channel'];
    }
}

class BroadcastAfterCommitTestEvent implements ShouldBroadcastAfterCommit
{
    public function broadcastOn()
    {
        return ['test-channel'];
    }
}

class RecordingBroadcaster implements Broadcaster
{
    public static $broadcasts = [];

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
        static::$broadcasts[] = $event;
    }
}
