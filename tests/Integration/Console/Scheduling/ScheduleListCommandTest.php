<?php

namespace Illuminate\Tests\Integration\Console\Scheduling;

use Illuminate\Console\Application;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Console\Scheduling\ScheduleListCommand;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
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

    public function testDisplayEmptyScheduleAsJson()
    {
        $this->withoutMockingConsoleOutput()->artisan(ScheduleListCommand::class, ['--json' => true]);
        $output = Artisan::output();

        $this->assertJson($output);
        $this->assertJsonStringEqualsJsonString('[]', $output);
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

    public function testDisplayScheduleAsJson()
    {
        $this->schedule->command(FooCommand::class)->quarterly();
        $this->schedule->command('inspire')->twiceDaily(14, 18);
        $this->schedule->command('foobar', ['a' => 'b'])->everyMinute();
        $this->schedule->job(FooJob::class)->everyMinute();
        $this->schedule->job(new FooParamJob('test'))->everyMinute();
        $this->schedule->job(FooJob::class)->name('foo-named-job')->everyMinute();
        $this->schedule->job(new FooParamJob('test'))->name('foo-named-param-job')->everyMinute();
        $this->schedule->command('inspire')->cron('0 9,17 * * *');
        $this->schedule->call(fn () => '')->everyMinute();

        $this->withoutMockingConsoleOutput()->artisan(ScheduleListCommand::class, ['--json' => true]);
        $output = Artisan::output();

        $this->assertJson($output);
        $data = json_decode($output, true);

        $this->assertIsArray($data);
        $this->assertCount(9, $data);

        $this->assertSame('0 0 1 1-12/3 *', $data[0]['expression']);
        $this->assertNull($data[0]['repeat_seconds']);
        $this->assertSame('php artisan foo:command', $data[0]['command']);
        $this->assertSame('This is the description of the command.', $data[0]['description']);
        $this->assertStringContainsString('2023-04-01 00:00:00', $data[0]['next_due_date']);
        $this->assertSame('3 months from now', $data[0]['next_due_date_human']);
        $this->assertFalse($data[0]['has_mutex']);

        $this->assertSame('* * * * *', $data[2]['expression']);
        $this->assertSame('php artisan foobar a='.ProcessUtils::escapeArgument('b'), $data[2]['command']);
        $this->assertNull($data[2]['description']);
        $this->assertSame('1 minute from now', $data[2]['next_due_date_human']);

        $this->assertSame('Illuminate\Tests\Integration\Console\Scheduling\FooJob', $data[3]['command']);

        $this->assertSame('foo-named-job', $data[5]['command']);

        $this->assertStringContainsString('Closure at:', $data[8]['command']);
        $this->assertStringContainsString('ScheduleListCommandTest.php', $data[8]['command']);
    }

    public function testDisplayScheduleWithSortAsJson()
    {
        $this->schedule->command(FooCommand::class)->quarterly();
        $this->schedule->command('inspire')->twiceDaily(14, 18);
        $this->schedule->command('foobar', ['a' => 'b'])->everyMinute();

        $this->withoutMockingConsoleOutput()->artisan(ScheduleListCommand::class, [
            '--next' => true,
            '--json' => true,
        ]);
        $output = Artisan::output();

        $this->assertJson($output);
        $data = json_decode($output, true);

        $this->assertIsArray($data);
        $this->assertCount(3, $data);

        $this->assertSame('* * * * *', $data[0]['expression']);
        $this->assertSame('1 minute from now', $data[0]['next_due_date_human']);
        $this->assertSame('php artisan foobar a='.ProcessUtils::escapeArgument('b'), $data[0]['command']);

        $this->assertSame('0 14,18 * * *', $data[1]['expression']);
        $this->assertSame('14 hours from now', $data[1]['next_due_date_human']);
        $this->assertSame('php artisan inspire', $data[1]['command']);

        $this->assertSame('0 0 1 1-12/3 *', $data[2]['expression']);
        $this->assertSame('3 months from now', $data[2]['next_due_date_human']);
        $this->assertSame('php artisan foo:command', $data[2]['command']);
    }

    public function testDisplayScheduleAsJsonWithTimezone()
    {
        $this->schedule->command('inspire')->daily();

        $this->withoutMockingConsoleOutput()->artisan(ScheduleListCommand::class, [
            '--timezone' => 'America/Chicago',
            '--json' => true,
        ]);
        $output = Artisan::output();

        $this->assertJson($output);
        $data = json_decode($output, true);

        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertSame('America/Chicago', $data[0]['timezone']);
        $this->assertStringContainsString('-06:00', $data[0]['next_due_date']);
        $this->assertSame('php artisan inspire', $data[0]['command']);
    }

    public function testDisplayScheduleAsJsonInVerboseMode()
    {
        $this->schedule->command(FooCommand::class)->quarterly();
        $this->schedule->command('inspire')->everyMinute();
        $this->schedule->call(fn () => '')->everyMinute();

        $this->withoutMockingConsoleOutput()->artisan(ScheduleListCommand::class, [
            '--json' => true,
            '-v' => true,
        ]);
        $output = Artisan::output();

        $this->assertJson($output);
        $data = json_decode($output, true);

        $this->assertIsArray($data);
        $this->assertCount(3, $data);

        $this->assertSame('0 0 1 1-12/3 *', $data[0]['expression']);
        $this->assertSame(Application::phpBinary().' '.Application::artisanBinary().' foo:command', $data[0]['command']);
        $this->assertSame('This is the description of the command.', $data[0]['description']);

        $this->assertSame('* * * * *', $data[1]['expression']);
        $this->assertSame(Application::phpBinary().' '.Application::artisanBinary().' inspire', $data[1]['command']);

        $this->assertStringContainsString('Closure at:', $data[2]['command']);
        $this->assertStringContainsString('ScheduleListCommandTest.php', $data[2]['command']);
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

    public function testClosureCommandsMayBeScheduled()
    {
        $closure = function () {
        };

        Artisan::command('one', $closure)->weekly()->everySecond();
        Artisan::command('two', $closure)->everyTwoSeconds();
        Artisan::command('three', $closure)->everyFiveSeconds();
        Artisan::command('four', $closure)->everyTenSeconds();
        Artisan::command('five', $closure)->everyFifteenSeconds();
        Artisan::command('six', $closure)->everyTwentySeconds()->hourly();
        Artisan::command('six', $closure)->everyThreeHours()->everySecond();

        $this->artisan(ScheduleListCommand::class)
            ->assertSuccessful()
            ->expectsOutput('  * 0   * * 0 1s   php artisan one ............... Next Due: 1 second from now')
            ->expectsOutput('  * *   * * * 2s   php artisan two .............. Next Due: 2 seconds from now')
            ->expectsOutput('  * *   * * * 5s   php artisan three ............ Next Due: 5 seconds from now')
            ->expectsOutput('  * *   * * * 10s  php artisan four ............ Next Due: 10 seconds from now')
            ->expectsOutput('  * *   * * * 15s  php artisan five ............ Next Due: 15 seconds from now')
            ->expectsOutput('  0 *   * * * 20s  php artisan six ............. Next Due: 20 seconds from now')
            ->expectsOutput('  * */3 * * * 1s   php artisan six ............... Next Due: 1 second from now');
    }

    protected function tearDown(): void
    {
        putenv('SHELL_VERBOSITY');

        parent::tearDown();
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
