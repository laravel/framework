<?php

namespace Illuminate\Tests\Integration\Console;

use Illuminate\Console\Application as Artisan;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Console\QueuedCommand;
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

    public function testArtisanCallUsingCommandName(): void
    {
        $this->artisan('foo:bar', [
            'id' => 1,
        ])->assertExitCode(0);
    }

    public function testArtisanCallUsingCommandNameAliases(): void
    {
        $this->artisan('app:foobar', [
            'id' => 1,
        ])->assertExitCode(0);
    }

    public function testArtisanCallUsingCommandClass(): void
    {
        $this->artisan(FooCommandStub::class, [
            'id' => 1,
        ])->assertExitCode(0);
    }

    public function testArtisanCallUsingCommandNameUsingAsCommandAttribute(): void
    {
        $this->artisan('zonda', [
            'id' => 1,
        ])->assertExitCode(0);
    }

    public function testArtisanCallUsingCommandNameAliasesUsingAsCommandAttribute(): void
    {
        $this->artisan('app:zonda', [
            'id' => 1,
        ])->assertExitCode(0);
    }

    public function testArtisanCallNow(): void
    {
        $exitCode = $this->artisan('foo:bar', [
            'id' => 1,
        ])->run();

        $this->assertSame(0, $exitCode);
    }

    public function testArtisanWithMockCallAfterCallNow(): void
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

    public function testArtisanInstantiateScheduleWhenNeed(): void
    {
        $this->assertFalse($this->app->resolved(Schedule::class));

        $this->app[Kernel::class]->registerCommand(new ScheduleCommandStub);

        $this->assertFalse($this->app->resolved(Schedule::class));

        $this->artisan('foo:schedule');

        $this->assertTrue($this->app->resolved(Schedule::class));
    }

    public function testArtisanQueue(): void
    {
        Queue::fake();

        $this->app[Kernel::class]->queue('foo:bar', [
            'id' => 1,
        ]);

        Queue::assertPushed(QueuedCommand::class, function ($job) {
            return $job->displayName() === 'foo:bar';
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
