<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Bus\Dispatchable;
use Orchestra\Testbench\TestCase;
use Queue;

/**
 * @group integration
 */
class WorkCommandTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.debug', 'true');

        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['db']->connection()->getSchemaBuilder()->create('jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('queue');
            $table->longText('payload');
            $table->tinyInteger('attempts')->unsigned();
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
            $table->index(['queue', 'reserved_at']);
        });
    }

    protected function tearDown(): void
    {
        $this->app['db']->connection()->getSchemaBuilder()->drop('jobs');

        parent::tearDown();

        FirstJob::$ran = false;
        SecondJob::$ran = false;
    }

    public function testRunningOneJob()
    {
        Queue::connection('database')->push(new FirstJob);
        Queue::connection('database')->push(new SecondJob);

        $this->artisan('queue:work', [
            'connection' => 'database',
            '--once' => true,
            '--memory' => 1024,
        ])->assertExitCode(0);

        $this->assertSame(1, Queue::connection('database')->size());
        $this->assertTrue(FirstJob::$ran);
        $this->assertFalse(SecondJob::$ran);
    }

    public function testDaemon()
    {
        Queue::connection('database')->push(new FirstJob);
        Queue::connection('database')->push(new SecondJob);

        $this->artisan('queue:work', [
            'connection' => 'database',
            '--daemon' => true,
            '--stop-when-empty' => true,
            '--memory' => 1024,
        ])->assertExitCode(0);

        $this->assertSame(0, Queue::connection('database')->size());
        $this->assertTrue(FirstJob::$ran);
        $this->assertTrue(SecondJob::$ran);
    }

    public function testMemoryExceeded()
    {
        Queue::connection('database')->push(new FirstJob);
        Queue::connection('database')->push(new SecondJob);

        $this->artisan('queue:work', [
            'connection' => 'database',
            '--daemon' => true,
            '--stop-when-empty' => true,
            '--memory' => 0.1,
        ])->assertExitCode(12);

        // Memory limit isn't checked until after the first job is attempted.
        $this->assertSame(1, Queue::connection('database')->size());
        $this->assertTrue(FirstJob::$ran);
        $this->assertFalse(SecondJob::$ran);
    }
}

class FirstJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public static $ran = false;

    public function handle()
    {
        static::$ran = true;
    }
}

class SecondJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public static $ran = false;

    public function handle()
    {
        static::$ran = true;
    }
}
