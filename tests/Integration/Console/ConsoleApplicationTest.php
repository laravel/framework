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
        $kernel = $this->app[Kernel::class];

        $kernel->registerCommand(new HyperlinkCommandStub);

        $kernel->call('foo:hyperlink');
        $this->assertEquals("Laravel\n", $kernel->output());

        $kernel->call('foo:hyperlink', ['--ansi' => true]);
        $this->assertEquals("\033]8;;https://laravel.com\033\\Laravel\033]8;;\033\\\n", $kernel->output());

        $kernel->call('foo:hyperlink', ['--no-ansi' => true]);
        $this->assertEquals("Laravel\n", $kernel->output());
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
        $this->hyperlink('https://laravel.com', 'Laravel');
    }
}
