<?php

namespace Illuminate\Tests\Integration\Console;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel;
use Orchestra\Testbench\TestCase;

class ConsoleApplicationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app[Kernel::class]->registerCommand(new FooCommandStub);
    }

    public function testArtisanCallUsingCommandName()
    {
        $this->artisan('foo:bar', [
            'id' => 1,
        ])->assertExitCode(0);
    }

    public function testArtisanCallUsingCommandClass()
    {
        $this->artisan(FooCommandStub::class, [
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

    public function testArtisanWithHyperlink()
    {
        $this->app[Kernel::class]->registerCommand(new HyperlinkCommandStub);

        $mock = $this->artisan('foo:hyperlink');
        $mock->expectsOutput('Laravel');

        $mock = $this->artisan('foo:hyperlink', ['--ansi' => true]);
        $mock->expectsOutput("\033]8;;https://laravel.com\033\\Laravel\033]8;;\033\\");

        $mock = $this->artisan('foo:hyperlink', ['--no-ansi' => true]);
        $mock->expectsOutput('Laravel');
    }
}

class FooCommandStub extends Command
{
    protected $signature = 'foo:bar {id}';

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

class HyperlinkCommandStub extends Command
{
    protected $signature = 'foo:hyperlink';

    public function handle()
    {
        if ($this->option('ansi')) {
            $this->output->setDecorated(true);
        } elseif ($this->option('no-ansi')) {
            $this->output->setDecorated(false);
        }

        $this->hyperlink('https://laravel.com', 'Laravel');
    }
}
