<?php

namespace Illuminate\Tests\Integration\Queue;

use Exception;
use Illuminate\Bus\Dispatcher;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Database\Middleware\Transactional;
use Illuminate\Queue\CallQueuedHandler;
use Illuminate\Support\Facades\DB;
use Mockery as m;

class TransactionalTest extends QueueTestCase
{
    protected function defineEnvironment($app)
    {
        $app['db.schema']->create('users', static function ($table) {
            $table->id();
            $table->string('name');
        });
    }

    public function testJobsThatThrowAreRolledBackAutomatically()
    {
        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);
        $job = m::mock(Job::class);
        $job->shouldReceive('hasFailed')->andReturn(false);
        $job->shouldReceive('isReleased')->andReturn(false);
        $job->shouldReceive('isDeletedOrReleased')->andReturn(false);

        $this->assertDatabaseEmpty('users');

        try {
            $instance->call($job, ['command' => serialize(new SomeJobThatHasDatabaseOperationsAndThrows())]);
        } catch (Exception) {
            //
        }

        $this->assertDatabaseEmpty('users');
    }
}

class SomeJobThatHasDatabaseOperationsAndThrows
{
    public function handle()
    {
        DB::table('users')->insert(['name' => 'Taylor Otwell']);

        throw new Exception('Uh-oh!');
    }

    public function middleware()
    {
        return [new Transactional];
    }
}
