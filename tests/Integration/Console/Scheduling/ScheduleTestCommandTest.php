<?php

namespace Illuminate\Tests\Integration\Console\Scheduling;

use Illuminate\Console\Application;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Console\Scheduling\ScheduleTestCommand;
use Illuminate\Support\Carbon;
use Orchestra\Testbench\TestCase;

class ScheduleTestCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(now()->startOfYear());

        $this->timestamp = now()->startOfYear()->format('c');
        $this->schedule = $this->app->make(Schedule::class);
    }

    public function testRunNoDefinedCommands()
    {
        $this->artisan(ScheduleTestCommand::class)
            ->assertSuccessful()
            ->expectsOutput('No scheduled commands have been defined.');
    }

    public function testRunNoMatchingCommand()
    {
        $this->schedule->command(BarCommandStub::class);

        $this->artisan(ScheduleTestCommand::class, ['--name' => 'missing:command'])
            ->assertSuccessful()
            ->expectsOutput('No matching scheduled command found.');
    }

    public function testRunUsingNameOption()
    {
        $this->schedule->command(BarCommandStub::class)->name('bar-command');
        $this->schedule->job(BarJobStub::class);
        $this->schedule->call(fn () => true)->name('callback');

        $this->artisan(ScheduleTestCommand::class, ['--name' => 'bar:command'])
            ->assertSuccessful()
            ->expectsOutput(sprintf('[%s] Running scheduled command: bar-command', $this->timestamp));

        $this->artisan(ScheduleTestCommand::class, ['--name' => BarJobStub::class])
            ->assertSuccessful()
            ->expectsOutput(sprintf('[%s] Running scheduled command: %s', $this->timestamp, BarJobStub::class));

        $this->artisan(ScheduleTestCommand::class, ['--name' => 'callback'])
            ->assertSuccessful()
            ->expectsOutput(sprintf('[%s] Running scheduled command: callback', $this->timestamp));
    }

    public function testRunUsingChoices()
    {
        $this->schedule->command(BarCommandStub::class)->name('bar-command');
        $this->schedule->job(BarJobStub::class);
        $this->schedule->call(fn () => true)->name('callback');

        $this->artisan(ScheduleTestCommand::class)
            ->assertSuccessful()
            ->expectsChoice(
                'Which command would you like to run?',
                'callback',
                [Application::formatCommandString('bar:command'), BarJobStub::class, 'callback'],
                true
            )
            ->expectsOutput(sprintf('[%s] Running scheduled command: callback', $this->timestamp));
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Carbon::setTestNow(null);
    }
}

class BarCommandStub extends Command
{
    protected $signature = 'bar:command';

    protected $description = 'This is the description of the command.';
}

class BarJobStub
{
    public function __invoke()
    {
        // ..
    }
}
