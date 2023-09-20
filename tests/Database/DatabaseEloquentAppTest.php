<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\Concerns\CreatesApplication;

class DatabaseEloquentAppTest extends TestCase
{
    use RefreshDatabase;
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('database_eloquent_app_test_users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->timestamps();
        });
    }

    public function testObserverIsCalledOnTestsWithAfterCommit()
    {
        DatabaseEloquentAppTestUser::observe($observer = DatabaseEloquentAppTestUserObserver::reseting());

        $user1 = DatabaseEloquentAppTestUser::create([
            'email' => 'hello@example.com',
        ]);

        $this->assertTrue($user1->exists);
        $this->assertEquals(1, $observer::$calledTimes, 'Failed to assert the observer was called once.');
    }

    public function testObserverCalledWithAfterCommitWhenInsideTransaction()
    {
        DatabaseEloquentAppTestUser::observe($observer = DatabaseEloquentAppTestUserObserver::reseting());

        $user1 = DB::transaction(fn () => DatabaseEloquentAppTestUser::create([
            'email' => 'hello@example.com',
        ]));

        $this->assertTrue($user1->exists);
        $this->assertEquals(1, $observer::$calledTimes, 'Failed to assert the observer was called once.');
    }

    public function testObserverIsCalledOnTestsWithAfterCommitWhenUsingSavepoint()
    {
        DatabaseEloquentAppTestUser::observe($observer = DatabaseEloquentAppTestUserObserver::reseting());

        $user1 = DatabaseEloquentAppTestUser::createOrFirst([
            'email' => 'hello@example.com',
        ]);

        $this->assertTrue($user1->exists);
        $this->assertEquals(1, $observer::$calledTimes, 'Failed to assert the observer was called once.');
    }

    public function testObserverIsCalledOnTestsWithAfterCommitWhenUsingSavepointAndInsideTransaction()
    {
        DatabaseEloquentAppTestUser::observe($observer = DatabaseEloquentAppTestUserObserver::reseting());

        $user1 = DB::transaction(fn () => DatabaseEloquentAppTestUser::createOrFirst([
            'email' => 'hello@example.com',
        ]));

        $this->assertTrue($user1->exists);
        $this->assertEquals(1, $observer::$calledTimes, 'Failed to assert the observer was called once.');
    }
}

class DatabaseEloquentAppTestUser extends Model
{
    protected $guarded = [];
}

class DatabaseEloquentAppTestUserObserver
{
    public static $calledTimes = 0;

    public $afterCommit = true;

    public static function reseting()
    {
        static::$calledTimes = 0;

        return new static();
    }

    public function created($user)
    {
        static::$calledTimes++;
    }
}
