<?php

namespace Illuminate\Tests\Integration\Console;

use Illuminate\Console\Application as Artisan;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Console\QueuedCommand;
use Illuminate\Foundation\Console\QueuedUniqueCommand;
use Illuminate\Support\Facades\Queue;
use Orchestra\Testbench\TestCase;
use Symfony\Component\Console\Attribute\AsCommand;

class ConsoleApplicationTest extends TestCase
{
    protected function setUp(): void
    {
        Artisan::starting(function ($artisan) {
            $artisan->resolveCommands([
                FooCommandStub::class,
                ZondaCommandStub::class,
            ]);
        });

        parent::setUp();
    }

    public function testArtisanCallUsingCommandName()
    {
        $this->artisan('foo:bar', [
            'id' => 1,
        ])->assertExitCode(0);
    }

    public function testArtisanCallUsingCommandNameAliases()
    {
        $this->artisan('app:foobar', [
            'id' => 1,
        ])->assertExitCode(0);
    }

    public function testArtisanCallUsingCommandClass()
    {
        $this->artisan(FooCommandStub::class, [
            'id' => 1,
        ])->assertExitCode(0);
    }

    public function testArtisanCallUsingCommandNameUsingAsCommandAttribute()
    {
        $this->artisan('zonda', [
            'id' => 1,
        ])->assertExitCode(0);
    }

    public function testArtisanCallUsingCommandNameAliasesUsingAsCommandAttribute()
    {
        $this->artisan('app:zonda', [
            'id' => 1,
        ])->assertExitCode(0);
    }

    public function testArtisanCallNow()
    {
        $exitCode = $this->artisan('foo:bar', [
            'id' => 1,
        ])->run();

        $this->assertSame(0, $exitCode);
    }

    public function testArtisanWithMockCallAfterCallNow()
    {
        $exitCode = $this->artisan('foo:bar', [
            'id' => 1,
        ])->run();

        $mock = $this->artisan('foo:bar', [
            'id' => 1,
        ]);

        $this->assertSame(0, $exitCode);
        $mock->assertExitCode(0);
    }

    public function testArtisanInstantiateScheduleWhenNeed()
    {
        $this->assertFalse($this->app->resolved(Schedule::class));

        $this->app[Kernel::class]->registerCommand(new ScheduleCommandStub);

        $this->assertFalse($this->app->resolved(Schedule::class));

        $this->artisan('foo:schedule');

        $this->assertTrue($this->app->resolved(Schedule::class));
    }

    public function testArtisanQueue()
    {
        Queue::fake();

        $this->app[Kernel::class]->queue('foo:bar', [
            'id' => 1,
        ]);

        Queue::assertPushed(QueuedCommand::class, function ($job) {
            return $job->displayName() === 'foo:bar';
        });
    }

    public function testArtisanQueueUnique()
    {
        Queue::fake();

        $job = $this->app[Kernel::class]->queueUnique('foo:bar', [
            'id' => 1,
        ])->getJob();

        $job->setUniqueBy('test_id');
        $job->setUniqueVia('test_cache');
        $job->setUniqueFor(3000);

        Queue::assertPushed(QueuedCommand::class, function ($job) {
            return $job instanceof QueuedUniqueCommand
                && $job->displayName() === 'foo:bar'
                && $job->uniqueBy() === 'test_id'
                && $job->uniqueVia() === 'test_cache'
                && $job->uniqueFor() === 3000;
        });
    }
}

class FooCommandStub extends Command
{
    protected $signature = 'foo:bar {id}';

    protected $aliases = ['app:foobar'];

    public function handle()
    {
        //
    }
}

#[AsCommand(name: 'zonda', aliases: ['app:zonda'])]
class ZondaCommandStub extends Command
{
    protected $signature = 'zonda {id}';

    protected $aliases = ['app:zonda'];

    public function handle()
    {
        //
    }
}

class ScheduleCommandStub extends Command
{
    protected $signature = 'foo:schedule';

    public function handle(Schedule $schedule)
    {
        //
    }
}
