<?php

namespace Illuminate\Tests\Integration\Console\Scheduling;

use Illuminate\Console\Application;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Console\Scheduling\ScheduleListCommand;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ProcessUtils;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

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
        $this->assertIsArray($data[0]['environments']);
        $this->assertEmpty($data[0]['environments']);

        $this->assertSame('* * * * *', $data[2]['expression']);
        $this->assertSame('php artisan foobar a='.ProcessUtils::escapeArgument('b'), $data[2]['command']);
        $this->assertNull($data[2]['description']);
        $this->assertSame('1 minute from now', $data[2]['next_due_date_human']);

        $this->assertSame('Illuminate\Tests\Integration\Console\Scheduling\FooJob', $data[3]['command']);

        $this->assertSame('foo-named-job', $data[5]['command']);

        $this->assertStringContainsString('Closure at:', $data[8]['command']);
        $this->assertStringContainsString('ScheduleListCommandTest.php', $data[8]['command']);
    }

    public function testDisplayScheduleAsJsonWithSpecificEnvironment()
    {
        $environment = 'production';
        $this->schedule->command(FooCommand::class)->quarterly()->environments($environment);

        $this->withoutMockingConsoleOutput()->artisan(ScheduleListCommand::class, ['--json' => true]);
        $output = Artisan::output();

        $this->assertJson($output);
        $data = json_decode($output, true);
        $this->assertIsArray($data);
        $this->assertCount(1, $data);

        $this->assertIsArray($data[0]['environments']);
        $this->assertNotEmpty($data[0]['environments']);
        $this->assertContains($environment, $data[0]['environments']);
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

    public static function expressionTimezoneConversionProvider()
    {
        return [
            // [cron expression, event timezone, display timezone, expected expressions]

            // No conversion needed — same timezone
            'same timezone' => ['0 8 * * *', 'UTC', null, ['0 8 * * *']],

            // Wildcards/steps — pass through unchanged
            'every minute' => ['* * * * *', 'America/New_York', null, ['* * * * *']],
            'every five minutes' => ['*/5 * * * *', 'Asia/Tokyo', null, ['*/5 * * * *']],
            'every two hours' => ['0 */2 * * *', 'Asia/Tokyo', null, ['0 */2 * * *']],
            'every odd hour' => ['0 1-23/2 * * *', 'Asia/Tokyo', null, ['0 1-23/2 * * *']],
            'quarterly step month' => ['0 0 1 1-12/3 *', 'Asia/Tokyo', null, ['0 15 31 1-12/3 *']],
            'weekdays range' => ['0 8 * * 1-5', 'Asia/Tokyo', null, ['0 23 * * 1-5']],

            // Simple hour shift (no day boundary)
            'daily LA to UTC' => ['0 8 * * *', 'America/Los_Angeles', null, ['0 16 * * *']],
            'daily Tokyo to UTC' => ['0 14 * * *', 'Asia/Tokyo', null, ['0 5 * * *']],
            '--timezone flag' => ['0 0 * * *', 'UTC', 'Asia/Tokyo', ['0 9 * * *']],

            // Hour wraparound (crosses midnight, no fixed day)
            'hour wraps forward' => ['0 23 * * *', 'Asia/Tokyo', null, ['0 14 * * *']],
            'hour wraps backward' => ['0 2 * * *', 'America/Los_Angeles', null, ['0 10 * * *']],

            // Half-hour timezone offset
            'Kolkata +5:30' => ['0 8 * * *', 'Asia/Kolkata', null, ['30 2 * * *']],
            'Kathmandu +5:45' => ['0 8 * * *', 'Asia/Kathmandu', null, ['15 2 * * *']],

            // Comma-separated hours — same carry direction
            'twice daily Tokyo' => ['0 9,17 * * *', 'Asia/Tokyo', null, ['0 0,8 * * *']],
            'twice daily Tokyo wrapping' => ['0 13,22 * * *', 'Asia/Tokyo', null, ['0 4,13 * * *']],

            // Comma-separated hours — mixed carries (splits into two entries)
            'twice daily LA mixed carry' => ['0 13,17 * * *', 'America/Los_Angeles', null, ['0 21 * * *', '0 1 * * *']],
            'twice daily Tokyo mixed carry' => ['0 3,20 * * *', 'Asia/Tokyo', null, ['0 18 * * *', '0 11 * * *']],

            // Day-of-week shifts
            'weekly Monday night LA' => ['0 22 * * 1', 'America/Los_Angeles', null, ['0 6 * * 2']],
            'weekly Wednesday morning Tokyo' => ['0 3 * * 3', 'Asia/Tokyo', null, ['0 18 * * 2']],
            'weekly Sunday night LA' => ['0 22 * * 0', 'America/Los_Angeles', null, ['0 6 * * 1']],
            'weekly Saturday morning Tokyo' => ['0 3 * * 6', 'Asia/Tokyo', null, ['0 18 * * 5']],

            // Day-of-month shifts
            'monthly 15th Tokyo (no day wrap)' => ['0 12 15 * *', 'Asia/Tokyo', null, ['0 3 15 * *']],
            'monthly 15th Tokyo (day wraps back)' => ['0 8 15 * *', 'Asia/Tokyo', null, ['0 23 14 * *']],
            'monthly 1st Tokyo (day wraps to 31)' => ['0 1 1 * *', 'Asia/Tokyo', null, ['0 16 31 * *']],
            'monthly 1st LA (day wraps forward)' => ['0 22 1 * *', 'America/Los_Angeles', null, ['0 6 2 * *']],

            // Month shifts (day-of-month carry propagates to month)
            'yearly Jan 1 Tokyo' => ['0 1 1 1 *', 'Asia/Tokyo', null, ['0 16 31 12 *']],
            'yearly Jul 1 Tokyo' => ['0 1 1 7 *', 'Asia/Tokyo', null, ['0 16 31 6 *']],
            'yearly Dec 31 LA' => ['0 22 31 12 *', 'America/Los_Angeles', null, ['0 6 1 1 *']],

            // Comma day-of-month
            'twice monthly Tokyo (no wrap)' => ['0 12 1,16 * *', 'Asia/Tokyo', null, ['0 3 1,16 * *']],
            // day 1→31 (carry -1 to month) and 16→15 (no carry) — splits
            'twice monthly Tokyo (wraps)' => ['0 1 1,16 * *', 'Asia/Tokyo', null, ['0 16 31 * *', '0 16 15 * *']],

            // Comma day-of-week
            'weekends LA night' => ['0 22 * * 0,6', 'America/Los_Angeles', null, ['0 6 * * 0,1']],

            // Hourly with minute offset (half-hour timezone)
            'hourly at 30 Kolkata' => ['30 * * * *', 'Asia/Kolkata', null, ['0 * * * *']],
            'hourly at 0 Kolkata' => ['0 * * * *', 'Asia/Kolkata', null, ['30 * * * *']],

            // Comma minutes with half-hour timezone — mixed minute carries (splits)
            // 15+(-30)=-15→45 (carry -1) and 45+(-30)=15 (no carry)
            'comma minutes Kolkata mixed carry' => ['15,45 8 * * *', 'Asia/Kolkata', null, ['45 2 * * *', '15 3 * * *']],
        ];
    }

    #[DataProvider('expressionTimezoneConversionProvider')]
    public function testExpressionTimezoneConversion($expression, $eventTimezone, $displayTimezone, $expectedExpressions)
    {
        $this->schedule->command('inspire')->cron($expression)->timezone($eventTimezone);

        $options = ['--json' => true];

        if ($displayTimezone) {
            $options['--timezone'] = $displayTimezone;
        }

        $this->withoutMockingConsoleOutput()->artisan(ScheduleListCommand::class, $options);
        $output = Artisan::output();

        $data = json_decode($output, true);

        $this->assertCount(count($expectedExpressions), $data);

        foreach ($expectedExpressions as $index => $expected) {
            $this->assertSame($expected, $data[$index]['expression']);
        }
    }

    public function testDisplayScheduleCliSplitsExpressionWhenMixedCarry()
    {
        // 13+8=21 (no carry), 17+8=1 (carry +1) — splits into two CLI rows
        $this->schedule->command('inspire')->twiceDaily(13, 17)->timezone('America/Los_Angeles');

        $this->artisan(ScheduleListCommand::class)
            ->assertSuccessful()
            ->expectsOutputToContain('0 21 * * *')
            ->expectsOutputToContain('0 1  * * *');
    }

    public function testDisplayScheduleCliConvertsExpression()
    {
        // 8:00 AM LA (UTC-8 in January) = 16:00 UTC
        $this->schedule->command('inspire')->dailyAt('08:00')->timezone('America/Los_Angeles');

        $this->artisan(ScheduleListCommand::class)
            ->assertSuccessful()
            ->expectsOutputToContain('0 16 * * *');
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
            ->expectsOutputToContain('Next Due: '.Carbon::now()->setMinutes(1)->format('Y-m-d H:i:s P'))
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

    public function testDisplaysPausedWarningWhenSchedulerIsPaused()
    {
        $this->schedule->call(fn () => '')->daily();

        Cache::forever('illuminate:schedule:paused', true);

        $this->artisan(ScheduleListCommand::class)
            ->assertSuccessful()
            ->expectsOutputToContain('The scheduler is currently paused.');

        Cache::forget('illuminate:schedule:paused');
    }

    public function testDoesNotDisplayPausedWarningWhenSchedulerIsRunning()
    {
        $this->schedule->call(fn () => '')->daily();

        $this->artisan(ScheduleListCommand::class)
            ->assertSuccessful()
            ->doesntExpectOutputToContain('paused');
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
