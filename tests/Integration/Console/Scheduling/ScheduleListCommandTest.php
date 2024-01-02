<?php

namespace Illuminate\Tests\Integration\Console\Scheduling;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Console\Scheduling\ScheduleListCommand;
use Illuminate\Support\Carbon;
use Illuminate\Support\ProcessUtils;
use Orchestra\Testbench\TestCase;

class ScheduleListCommandTest extends TestCase
{
    public $schedule;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2023-01-01');
        ScheduleListCommand::resolveTerminalWidthUsing(fn () => 80);

        $this->schedule = $this->app->make(Schedule::class);
    }

    public function testDisplayEmptySchedule()
    {
        $this->artisan(ScheduleListCommand::class)
            ->assertSuccessful()
            ->expectsOutputToContain('No scheduled tasks have been defined.');
    }

    public function testDisplaySchedule()
    {
        $this->schedule->command(FooCommand::class)->quarterly();
        $this->schedule->command('inspire')->twiceDaily(14, 18);
        $this->schedule->command('foobar', ['a' => 'b'])->everyMinute();
        $this->schedule->job(FooJob::class)->everyMinute();
        $this->schedule->job(new FooParamJob('test'))->everyMinute();
        $this->schedule->job(FooJob::class)->name('foo-named-job')->everyMinute();
        $this->schedule->job(new FooParamJob('test'))->name('foo-named-param-job')->everyMinute();
        $this->schedule->command('inspire')->cron('0 9,17 * * *');
        $this->schedule->command('inspire')->cron("0 10\t* * *");
        $this->schedule->call(FooCall::class)->everyMinute();
        $this->schedule->call([FooCall::class, 'fooFunction'])->everyMinute();

        $this->schedule->call(fn () => '')->everyMinute();
        $closureLineNumber = __LINE__ - 1;
        $closureFilePath = __FILE__;

        $this->artisan(ScheduleListCommand::class)
            ->assertSuccessful()
            ->expectsOutput('  0 0     1 1-12/3 *  php artisan foo:command .... Next Due: 3 months from now')
            ->expectsOutput('  0 14,18 * *      *  php artisan inspire ........ Next Due: 14 hours from now')
            ->expectsOutput('  * *     * *      *  php artisan foobar a='.ProcessUtils::escapeArgument('b').' ... Next Due: 1 minute from now')
            ->expectsOutput('  * *     * *      *  Illuminate\Tests\Integration\Console\Scheduling\FooJob  Next Due: 1 minute from now')
            ->expectsOutput('  * *     * *      *  Illuminate\Tests\Integration\Console\Scheduling\FooParamJob  Next Due: 1 minute from now')
            ->expectsOutput('  * *     * *      *  foo-named-job .............. Next Due: 1 minute from now')
            ->expectsOutput('  * *     * *      *  foo-named-param-job ........ Next Due: 1 minute from now')
            ->expectsOutput('  0 9,17  * *      *  php artisan inspire ......... Next Due: 9 hours from now')
            ->expectsOutput('  0 10    * *      *  php artisan inspire ........ Next Due: 10 hours from now')
            ->expectsOutput('  * *     * *      *  Illuminate\Tests\Integration\Console\Scheduling\FooCall  Next Due: 1 minute from now')
            ->expectsOutput('  * *     * *      *  Closure at: Illuminate\Tests\Integration\Console\Scheduling\FooCall::fooFunction  Next Due: 1 minute from now')
            ->expectsOutput('  * *     * *      *  Closure at: '.$closureFilePath.':'.$closureLineNumber.'  Next Due: 1 minute from now');
    }

    public function testDisplayScheduleWithSort()
    {
        $this->schedule->command(FooCommand::class)->quarterly();
        $this->schedule->command('inspire')->twiceDaily(14, 18);
        $this->schedule->command('foobar', ['a' => 'b'])->everyMinute();
        $this->schedule->job(FooJob::class)->everyMinute();
        $this->schedule->job(new FooParamJob('test'))->everyMinute();
        $this->schedule->job(FooJob::class)->name('foo-named-job')->everyMinute();
        $this->schedule->job(new FooParamJob('test'))->name('foo-named-param-job')->everyMinute();
        $this->schedule->command('inspire')->cron('0 9,17 * * *');
        $this->schedule->command('inspire')->cron("0 10\t* * *");
        $this->schedule->call(FooCall::class)->everyMinute();
        $this->schedule->call([FooCall::class, 'fooFunction'])->everyMinute();

        $this->schedule->call(fn () => '')->everyMinute();
        $closureLineNumber = __LINE__ - 1;
        $closureFilePath = __FILE__;

        $this->artisan(ScheduleListCommand::class, ['--next' => true])
            ->assertSuccessful()
            ->expectsOutput('  * *     * *      *  php artisan foobar a='.ProcessUtils::escapeArgument('b').' ... Next Due: 1 minute from now')
            ->expectsOutput('  * *     * *      *  Illuminate\Tests\Integration\Console\Scheduling\FooJob  Next Due: 1 minute from now')
            ->expectsOutput('  * *     * *      *  Illuminate\Tests\Integration\Console\Scheduling\FooParamJob  Next Due: 1 minute from now')
            ->expectsOutput('  * *     * *      *  foo-named-job .............. Next Due: 1 minute from now')
            ->expectsOutput('  * *     * *      *  foo-named-param-job ........ Next Due: 1 minute from now')
            ->expectsOutput('  * *     * *      *  Illuminate\Tests\Integration\Console\Scheduling\FooCall  Next Due: 1 minute from now')
            ->expectsOutput('  * *     * *      *  Closure at: Illuminate\Tests\Integration\Console\Scheduling\FooCall::fooFunction  Next Due: 1 minute from now')
            ->expectsOutput('  * *     * *      *  Closure at: '.$closureFilePath.':'.$closureLineNumber.'  Next Due: 1 minute from now')
            ->expectsOutput('  0 9,17  * *      *  php artisan inspire ......... Next Due: 9 hours from now')
            ->expectsOutput('  0 10    * *      *  php artisan inspire ........ Next Due: 10 hours from now')
            ->expectsOutput('  0 14,18 * *      *  php artisan inspire ........ Next Due: 14 hours from now')
            ->expectsOutput('  0 0     1 1-12/3 *  php artisan foo:command .... Next Due: 3 months from now');
    }

    public function testDisplayScheduleInVerboseMode()
    {
        $this->schedule->command(FooCommand::class)->everyMinute();

        $this->artisan(ScheduleListCommand::class, ['-v' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('Next Due: '.now()->setMinutes(1)->format('Y-m-d H:i:s P'))
            ->expectsOutput('             ⇁ This is the description of the command.');
    }

    public function testDisplayScheduleSubMinute()
    {
        $this->schedule->command('inspire')->weekly()->everySecond();
        $this->schedule->command('inspire')->everyTwoSeconds();
        $this->schedule->command('inspire')->everyFiveSeconds();
        $this->schedule->command('inspire')->everyTenSeconds();
        $this->schedule->command('inspire')->everyFifteenSeconds();
        $this->schedule->command('inspire')->everyTwentySeconds();
        $this->schedule->command('inspire')->everyThirtySeconds();

        $this->artisan(ScheduleListCommand::class)
            ->assertSuccessful()
            ->expectsOutput('  * 0 * * 0 1s   php artisan inspire ............. Next Due: 1 second from now')
            ->expectsOutput('  * * * * * 2s   php artisan inspire ............ Next Due: 2 seconds from now')
            ->expectsOutput('  * * * * * 5s   php artisan inspire ............ Next Due: 5 seconds from now')
            ->expectsOutput('  * * * * * 10s  php artisan inspire ........... Next Due: 10 seconds from now')
            ->expectsOutput('  * * * * * 15s  php artisan inspire ........... Next Due: 15 seconds from now')
            ->expectsOutput('  * * * * * 20s  php artisan inspire ........... Next Due: 20 seconds from now')
            ->expectsOutput('  * * * * * 30s  php artisan inspire ........... Next Due: 30 seconds from now');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        putenv('SHELL_VERBOSITY');

        ScheduleListCommand::resolveTerminalWidthUsing(null);
    }
}

class FooCommand extends Command
{
    protected $signature = 'foo:command';

    protected $description = 'This is the description of the command.';
}

class FooJob
{
}

class FooParamJob
{
    public function __construct($param)
    {
    }
}

class FooCall
{
    public function __invoke(): void
    {
    }

    public function fooFunction(): void
    {
    }
}
