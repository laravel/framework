<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use Orchestra\Testbench\Factories\UserFactory;

trait EloquentTransactionWithAfterCommitTests
{
    use WithLaravelMigrations;

    protected function setUpEloquentTransactionWithAfterCommitTests(): void
    {
        User::unguard();
    }

    protected function tearDownEloquentTransactionWithAfterCommitTests(): void
    {
        User::reguard();
    }

    public function testObserverIsCalledOnTestsWithAfterCommit()
    {
        User::observe($observer = EloquentTransactionWithAfterCommitTestsUserObserver::resetting());

        $user1 = User::create(UserFactory::new()->raw());

        $this->assertTrue($user1->exists);
        $this->assertEquals(1, $observer::$calledTimes, 'Failed to assert the observer was called once.');
    }

    public function testObserverCalledWithAfterCommitWhenInsideTransaction()
    {
        User::observe($observer = EloquentTransactionWithAfterCommitTestsUserObserver::resetting());

        $user1 = DB::transaction(fn () => User::create(UserFactory::new()->raw()));

        $this->assertTrue($user1->exists);
        $this->assertEquals(1, $observer::$calledTimes, 'Failed to assert the observer was called once.');
    }

    public function testObserverCalledWithAfterCommitWhenInsideTransactionWithDispatchSync()
    {
        User::observe($observer = EloquentTransactionWithAfterCommitTestsUserObserverUsingDispatchSync::resetting());

        $user1 = DB::transaction(fn () => User::create(UserFactory::new()->raw()));

        $this->assertTrue($user1->exists);
        $this->assertEquals(1, $observer::$calledTimes, 'Failed to assert the observer was called once.');

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $user1->email,
            'token' => sha1($user1->email),
        ]);
    }

    public function testObserverIsCalledOnTestsWithAfterCommitWhenUsingSavepoint()
    {
        User::observe($observer = EloquentTransactionWithAfterCommitTestsUserObserver::resetting());

        $user1 = User::createOrFirst(UserFactory::new()->raw());

        $this->assertTrue($user1->exists);
        $this->assertEquals(1, $observer::$calledTimes, 'Failed to assert the observer was called once.');
    }

    public function testObserverIsCalledOnTestsWithAfterCommitWhenUsingSavepointAndInsideTransaction()
    {
        User::observe($observer = EloquentTransactionWithAfterCommitTestsUserObserver::resetting());

        $user1 = DB::transaction(fn () => User::createOrFirst(UserFactory::new()->raw()));

        $this->assertTrue($user1->exists);
        $this->assertEquals(1, $observer::$calledTimes, 'Failed to assert the observer was called once.');
    }

    public function testObserverIsCalledEvenWhenDeeplyNestingTransactions()
    {
        User::observe($observer = EloquentTransactionWithAfterCommitTestsUserObserver::resetting());

        $user1 = DB::transaction(function () use ($observer) {
            return tap(DB::transaction(function () use ($observer) {
                return tap(DB::transaction(function () use ($observer) {
                    return tap(User::createOrFirst(UserFactory::new()->raw()), function () use ($observer) {
                        $this->assertEquals(0, $observer::$calledTimes, 'Should not have been called');
                    });
                }), function () use ($observer) {
                    $this->assertEquals(0, $observer::$calledTimes, 'Should not have been called');
                });
            }), function () use ($observer) {
                $this->assertEquals(0, $observer::$calledTimes, 'Should not have been called');
            });
        });

        $this->assertTrue($user1->exists);
        $this->assertEquals(1, $observer::$calledTimes, 'Failed to assert the observer was called once.');
    }
}

class EloquentTransactionWithAfterCommitTestsUserObserver
{
    public static $calledTimes = 0;

    public $afterCommit = true;

    public static function resetting()
    {
        static::$calledTimes = 0;

        return new static();
    }

    public function created($user)
    {
        static::$calledTimes++;
    }
}

class EloquentTransactionWithAfterCommitTestsUserObserverUsingDispatchSync extends EloquentTransactionWithAfterCommitTestsUserObserver
{
    public function created($user)
    {
        dispatch_sync(new EloquentTransactionWithAfterCommitTestsJob($user->email));

        parent::created($user);
    }
}

class EloquentTransactionWithAfterCommitTestsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(public string $email)
    {
        // ...
    }

    public function handle(): void
    {
        DB::transaction(function () {
            DB::table('password_reset_tokens')->insert([
                ['email' => $this->email, 'token' => sha1($this->email), 'created_at' => now()],
            ]);
        });
    }
}
