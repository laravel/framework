<?php

use Mockery as m;
use Illuminate\Console\Scheduling\Schedule;

class ConsoleEventSchedulerTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testExecCreatesNewCommand()
    {
        $schedule = new Schedule;
        $schedule->exec('path/to/command');
        $schedule->exec('path/to/command -f --foo="bar"');
        $schedule->exec('path/to/command', ['-f']);
        $schedule->exec('path/to/command', ['--foo' => 'bar']);
        $schedule->exec('path/to/command', ['-f', '--foo' => 'bar']);
        $schedule->exec('path/to/command', ['--title' => 'A "real" test']);

        $events = $schedule->events();
        $this->assertEquals('path/to/command', $events[0]->command);
        $this->assertEquals('path/to/command -f --foo="bar"', $events[1]->command);
        $this->assertEquals('path/to/command -f', $events[2]->command);
        $this->assertEquals('path/to/command --foo="bar"', $events[3]->command);
        $this->assertEquals('path/to/command -f --foo="bar"', $events[4]->command);
        $this->assertEquals('path/to/command --title="A \"real\" test"', $events[5]->command);
    }

    public function testCommandCreatesNewArtisanCommand()
    {
        $schedule = new Schedule;
        $schedule->command('queue:listen');
        $schedule->command('queue:listen --tries="3"');
        $schedule->command('queue:listen', ['--tries' => 3]);

        $events = $schedule->events();
        $this->assertEquals('"'.PHP_BINARY.'" "artisan" queue:listen', $events[0]->command);
        $this->assertEquals('"'.PHP_BINARY.'" "artisan" queue:listen --tries="3"', $events[1]->command);
        $this->assertEquals('"'.PHP_BINARY.'" "artisan" queue:listen --tries="3"', $events[2]->command);
    }
}
