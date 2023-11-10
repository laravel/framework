<?php

namespace Illuminate\Tests\Console;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\CacheEventMutex;
use Illuminate\Console\Scheduling\CacheSchedulingMutex;
use Illuminate\Console\Scheduling\EventMutex;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Console\Scheduling\SchedulingMutex;
use Illuminate\Container\Container;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class ConsoleEventSchedulerTest extends TestCase
{
    /**
     * @var \Illuminate\Console\Scheduling\Schedule
     */
    private $schedule;

    protected function setUp(): void
    {
        parent::setUp();

        $container = Container::getInstance();

        $container->instance(EventMutex::class, m::mock(CacheEventMutex::class));

        $container->instance(SchedulingMutex::class, m::mock(CacheSchedulingMutex::class));

        $container->instance(Schedule::class, $this->schedule = new Schedule(m::mock(EventMutex::class)));
    }

    protected function tearDown(): void
    {
        m::close();
    }

    public function testMutexCanReceiveCustomStore()
    {
        Container::getInstance()->make(EventMutex::class)->shouldReceive('useStore')->once()->with('test');
        Container::getInstance()->make(SchedulingMutex::class)->shouldReceive('useStore')->once()->with('test');

        $this->schedule->useCache('test');
    }

    public function testExecCreatesNewCommand()
    {
        $escape = '\\' === DIRECTORY_SEPARATOR ? '"' : '\'';
        $escapeReal = '\\' === DIRECTORY_SEPARATOR ? '\\"' : '"';

        $schedule = $this->schedule;
        $schedule->exec('path/to/command');
        $schedule->exec('path/to/command -f --foo="bar"');
        $schedule->exec('path/to/command', ['-f']);
        $schedule->exec('path/to/command', ['--foo' => 'bar']);
        $schedule->exec('path/to/command', ['-f', '--foo' => 'bar']);
        $schedule->exec('path/to/command', ['--title' => 'A "real" test']);
        $schedule->exec('path/to/command', [['one', 'two']]);
        $schedule->exec('path/to/command', ['-1 minute']);
        $schedule->exec('path/to/command', ['foo' => ['bar', 'baz']]);
        $schedule->exec('path/to/command', ['--foo' => ['bar', 'baz']]);
        $schedule->exec('path/to/command', ['-F' => ['bar', 'baz']]);

        $events = $schedule->events();
        $this->assertSame('path/to/command', $events[0]->command);
        $this->assertSame('path/to/command -f --foo="bar"', $events[1]->command);
        $this->assertSame('path/to/command -f', $events[2]->command);
        $this->assertSame("path/to/command --foo={$escape}bar{$escape}", $events[3]->command);
        $this->assertSame("path/to/command -f --foo={$escape}bar{$escape}", $events[4]->command);
        $this->assertSame("path/to/command --title={$escape}A {$escapeReal}real{$escapeReal} test{$escape}", $events[5]->command);
        $this->assertSame("path/to/command {$escape}one{$escape} {$escape}two{$escape}", $events[6]->command);
        $this->assertSame("path/to/command {$escape}-1 minute{$escape}", $events[7]->command);
        $this->assertSame("path/to/command {$escape}bar{$escape} {$escape}baz{$escape}", $events[8]->command);
        $this->assertSame("path/to/command --foo={$escape}bar{$escape} --foo={$escape}baz{$escape}", $events[9]->command);
        $this->assertSame("path/to/command -F {$escape}bar{$escape} -F {$escape}baz{$escape}", $events[10]->command);
    }

    public function testExecCreatesNewCommandWithTimezone()
    {
        $schedule = new Schedule('UTC');
        $schedule->exec('path/to/command');
        $events = $schedule->events();
        $this->assertSame('UTC', $events[0]->timezone);

        $schedule = new Schedule('Asia/Tokyo');
        $schedule->exec('path/to/command');
        $events = $schedule->events();
        $this->assertSame('Asia/Tokyo', $events[0]->timezone);
    }

    public function testCommandCreatesNewArtisanCommand()
    {
        $escape = '\\' === DIRECTORY_SEPARATOR ? '"' : '\'';

        $schedule = $this->schedule;
        $schedule->command('queue:listen');
        $schedule->command('queue:listen --tries=3');
        $schedule->command('queue:listen', ['--tries' => 3]);

        $events = $schedule->events();
        $binary = $escape.PHP_BINARY.$escape;
        $artisan = $escape.'artisan'.$escape;
        $this->assertEquals($binary.' '.$artisan.' queue:listen', $events[0]->command);
        $this->assertEquals($binary.' '.$artisan.' queue:listen --tries=3', $events[1]->command);
        $this->assertEquals($binary.' '.$artisan.' queue:listen --tries=3', $events[2]->command);
    }

    public function testCreateNewArtisanCommandUsingCommandClass()
    {
        $escape = '\\' === DIRECTORY_SEPARATOR ? '"' : '\'';

        $schedule = $this->schedule;
        $schedule->command(ConsoleCommandStub::class, ['--force']);

        $events = $schedule->events();
        $binary = $escape.PHP_BINARY.$escape;
        $artisan = $escape.'artisan'.$escape;
        $this->assertEquals($binary.' '.$artisan.' foo:bar --force', $events[0]->command);
    }

    public function testItUsesCommandDescriptionAsEventDescription()
    {
        $schedule = $this->schedule;
        $event = $schedule->command(ConsoleCommandStub::class);
        $this->assertSame('This is a description about the command', $event->description);
    }

    public function testItShouldBePossibleToOverwriteTheDescription()
    {
        $schedule = $this->schedule;
        $event = $schedule->command(ConsoleCommandStub::class)
            ->description('This is an alternative description');
        $this->assertSame('This is an alternative description', $event->description);
    }

    public function testCallCreatesNewJobWithTimezone()
    {
        $schedule = new Schedule('UTC');
        $schedule->call('path/to/command');
        $events = $schedule->events();
        $this->assertSame('UTC', $events[0]->timezone);

        $schedule = new Schedule('Asia/Tokyo');
        $schedule->call('path/to/command');
        $events = $schedule->events();
        $this->assertSame('Asia/Tokyo', $events[0]->timezone);
    }
}

class FooClassStub
{
    protected $schedule;

    public function __construct(Schedule $schedule)
    {
        $this->schedule = $schedule;
    }
}

class ConsoleCommandStub extends Command
{
    protected $signature = 'foo:bar';

    protected $description = 'This is a description about the command';

    protected $foo;

    public function __construct(FooClassStub $foo)
    {
        parent::__construct();

        $this->foo = $foo;
    }
}
