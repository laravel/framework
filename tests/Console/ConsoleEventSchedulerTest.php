<?php

namespace Illuminate\Tests\Console;

use Mockery as m;
use Illuminate\Console\Command;
use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Console\Scheduling\EventMutex;
use Illuminate\Console\Scheduling\CacheEventMutex;
use Illuminate\Console\Scheduling\SchedulingMutex;
use Illuminate\Console\Scheduling\CacheSchedulingMutex;

class ConsoleEventSchedulerTest extends TestCase
{
    /**
     * @var Schedule
     */
    private $schedule;

    public function setUp(): void
    {
        parent::setUp();

        $container = Container::getInstance();

        $container->instance(EventMutex::class, m::mock(CacheEventMutex::class));

        $container->instance(SchedulingMutex::class, m::mock(CacheSchedulingMutex::class));

        $container->instance(Schedule::class, $this->schedule = new Schedule(m::mock(EventMutex::class)));
    }

    public function tearDown(): void
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

        $events = $schedule->events();
        $this->assertEquals('path/to/command', $events[0]->command);
        $this->assertEquals('path/to/command -f --foo="bar"', $events[1]->command);
        $this->assertEquals('path/to/command -f', $events[2]->command);
        $this->assertEquals("path/to/command --foo={$escape}bar{$escape}", $events[3]->command);
        $this->assertEquals("path/to/command -f --foo={$escape}bar{$escape}", $events[4]->command);
        $this->assertEquals("path/to/command --title={$escape}A {$escapeReal}real{$escapeReal} test{$escape}", $events[5]->command);
        $this->assertEquals("path/to/command {$escape}one{$escape} {$escape}two{$escape}", $events[6]->command);
        $this->assertEquals("path/to/command {$escape}-1 minute{$escape}", $events[7]->command);
    }

    public function testExecCreatesNewCommandWithTimezone()
    {
        $schedule = new Schedule('UTC');
        $schedule->exec('path/to/command');
        $events = $schedule->events();
        $this->assertEquals('UTC', $events[0]->timezone);

        $schedule = new Schedule('Asia/Tokyo');
        $schedule->exec('path/to/command');
        $events = $schedule->events();
        $this->assertEquals('Asia/Tokyo', $events[0]->timezone);
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
        $this->assertEquals($binary.' artisan queue:listen', $events[0]->command);
        $this->assertEquals($binary.' artisan queue:listen --tries=3', $events[1]->command);
        $this->assertEquals($binary.' artisan queue:listen --tries=3', $events[2]->command);
    }

    public function testCreateNewArtisanCommandUsingCommandClass()
    {
        $escape = '\\' === DIRECTORY_SEPARATOR ? '"' : '\'';

        $schedule = $this->schedule;
        $schedule->command(ConsoleCommandStub::class, ['--force']);

        $events = $schedule->events();
        $binary = $escape.PHP_BINARY.$escape;
        $this->assertEquals($binary.' artisan foo:bar --force', $events[0]->command);
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

    protected $foo;

    public function __construct(FooClassStub $foo)
    {
        parent::__construct();

        $this->foo = $foo;
    }
}
