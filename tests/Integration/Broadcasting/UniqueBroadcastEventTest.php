<?php

namespace Illuminate\Tests\Integration\Broadcasting;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBeUnique;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Foundation\Auth\User;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Tests\Integration\Queue\QueueTestCase;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\Factories\UserFactory;

#[WithMigration]
#[WithMigration('cache')]
#[WithMigration('queue')]
class UniqueBroadcastEventTest extends QueueTestCase
{
    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        $app['config']->set('cache.default', 'database');
        $app['config']->set('broadcasting.default', 'null');
    }

    public function testLockIsReleasedWhenModelIsMissing()
    {
        /** @var \Illuminate\Foundation\Auth\User */
        $user = UserFactory::new()->create();

        $user->delete();

        Broadcast::queue(new UniqueBroadcastEventWithModel($user));

        $this->runQueueWorkerCommand(['--once' => true]);

        $lockKey = 'laravel_unique_job:'.hash('xxh128', UniqueBroadcastEventWithModel::class).':'.$user->getKey();

        $this->assertTrue($this->app->get(Cache::class)->lock($lockKey, 10)->get());
    }
}

class UniqueBroadcastEventWithModel implements ShouldBroadcast, ShouldBeUnique
{
    use SerializesModels;

    public function __construct(public User $user)
    {
    }

    public function uniqueId()
    {
        return $this->user->getKey();
    }

    public function broadcastOn()
    {
        return [new Channel('users.'.$this->user->getKey())];
    }
}
